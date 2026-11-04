<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Checks a Firebase Authentication ID token without the Admin SDK: the RS256 signature against Google's published
 * certificates, then the issuer, audience, expiry and the verified phone number. Returns the uid and the E.164 phone.
 */
class FirebaseIdToken
{
    private const CACHE_KEY = 'firebase.securetoken.certs';

    /** @param (callable(): array<string,string>)|null $certificates kid => PEM; injected in tests */
    public function __construct(
        private readonly ?string $projectId = null,
        private $certificates = null,
        private readonly ?int $skewSeconds = null,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(config('firebase.project_id') ?: null);
    }

    public function configured(): bool
    {
        return filled($this->projectId ?? config('firebase.project_id'));
    }

    /** @return array{uid:string, phone:string} */
    public function verify(string $jwt): array
    {
        $projectId = $this->projectId ?? config('firebase.project_id');
        if (! filled($projectId)) {
            throw new InvalidFirebaseToken(__('Mobile sign-in is not set up yet.'));
        }

        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new InvalidFirebaseToken(__('That code could not be verified. Please try again.'));
        }
        [$h64, $p64, $s64] = $parts;

        $header = $this->decode($h64);
        $claims = $this->decode($p64);
        $signature = $this->b64url($s64);

        if (($header['alg'] ?? null) !== 'RS256' || empty($header['kid'])) {
            throw new InvalidFirebaseToken(__('That code could not be verified. Please try again.'));
        }

        $pem = $this->certificates()[$header['kid']] ?? null;
        $key = $pem ? openssl_pkey_get_public($pem) : false;
        if ($key === false || openssl_verify($h64 . '.' . $p64, $signature, $key, OPENSSL_ALGO_SHA256) !== 1) {
            throw new InvalidFirebaseToken(__('That code could not be verified. Please try again.'));
        }

        $now = time();
        $skew = $this->skewSeconds ?? (int) config('firebase.clock_skew_seconds', 60);

        if (($claims['iss'] ?? null) !== 'https://securetoken.google.com/' . $projectId
            || ($claims['aud'] ?? null) !== $projectId) {
            throw new InvalidFirebaseToken(__('That code belongs to a different app.'));
        }
        if (! isset($claims['exp']) || (int) $claims['exp'] + $skew < $now) {
            throw new InvalidFirebaseToken(__('That code has expired. Please request a new one.'));
        }
        if (isset($claims['iat']) && (int) $claims['iat'] - $skew > $now) {
            throw new InvalidFirebaseToken(__('That code could not be verified. Please try again.'));
        }
        if (! isset($claims['sub']) || ! is_string($claims['sub']) || $claims['sub'] === '' || strlen($claims['sub']) > 128) {
            throw new InvalidFirebaseToken(__('That code could not be verified. Please try again.'));
        }

        $provider = $claims['firebase']['sign_in_provider'] ?? null;
        $phone = $claims['phone_number'] ?? null;
        if ($provider !== 'phone' || ! is_string($phone) || ! preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            throw new InvalidFirebaseToken(__('Verify with your mobile number to continue.'));
        }

        return ['uid' => $claims['sub'], 'phone' => $phone];
    }

    /** @return array<string,string> */
    private function certificates(): array
    {
        if ($this->certificates !== null) {
            return ($this->certificates)();
        }

        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $response = Http::timeout(10)->get(config('firebase.certs_url'));

            return $response->successful() ? (array) $response->json() : [];
        });
    }

    private function decode(string $segment): array
    {
        $data = json_decode($this->b64url($segment), true);
        if (! is_array($data)) {
            throw new InvalidFirebaseToken(__('That code could not be verified. Please try again.'));
        }

        return $data;
    }

    private function b64url(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true) ?: '';
    }
}
