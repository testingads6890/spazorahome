<?php

namespace Tests\Unit;

use App\Support\FirebaseIdToken;
use App\Support\IndianPhone;
use App\Support\InvalidFirebaseToken;
use Tests\TestCase;

class FirebaseIdTokenTest extends TestCase
{
    private const PROJECT = 'demo-shop';
    private $key;
    private string $pem = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => 'securetoken'], $this->key);
        $cert = openssl_csr_sign($csr, null, $this->key, 1);
        openssl_x509_export($cert, $this->pem);
    }

    private function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private function token(array $claims, $signWith = null, array $header = []): string
    {
        $h = $this->b64(json_encode($header + ['alg' => 'RS256', 'kid' => 'k1', 'typ' => 'JWT']));
        $p = $this->b64(json_encode($claims));
        openssl_sign("$h.$p", $sig, $signWith ?? $this->key, OPENSSL_ALGO_SHA256);

        return "$h.$p." . $this->b64($sig);
    }

    private function claims(array $override = []): array
    {
        $now = time();

        return array_replace_recursive([
            'iss' => 'https://securetoken.google.com/' . self::PROJECT,
            'aud' => self::PROJECT,
            'iat' => $now,
            'auth_time' => $now,
            'exp' => $now + 3600,
            'sub' => 'uid-123',
            'phone_number' => '+919876543210',
            'firebase' => ['sign_in_provider' => 'phone'],
        ], $override);
    }

    private function verifier(): FirebaseIdToken
    {
        return new FirebaseIdToken(self::PROJECT, fn () => ['k1' => $this->pem], 60);
    }

    public function test_a_valid_phone_token_gives_the_uid_and_number(): void
    {
        $result = $this->verifier()->verify($this->token($this->claims()));
        $this->assertSame(['uid' => 'uid-123', 'phone' => '+919876543210'], $result);
    }

    public function test_a_token_signed_by_someone_else_is_rejected(): void
    {
        $other = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->expectException(InvalidFirebaseToken::class);
        $this->verifier()->verify($this->token($this->claims(), $other));
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $this->expectExceptionMessage('expired');
        $this->verifier()->verify($this->token($this->claims(['exp' => time() - 600])));
    }

    public function test_tokens_for_another_app_are_rejected(): void
    {
        $this->expectExceptionMessage('different app');
        $this->verifier()->verify($this->token($this->claims(['aud' => 'other-app', 'iss' => 'https://securetoken.google.com/other-app'])));
    }

    public function test_email_sign_in_tokens_are_not_accepted_as_mobile_proof(): void
    {
        $this->expectExceptionMessage('mobile number');
        $this->verifier()->verify($this->token($this->claims(['firebase' => ['sign_in_provider' => 'password'], 'phone_number' => null])));
    }

    public function test_unsigned_or_non_rs256_tokens_are_rejected(): void
    {
        $this->expectException(InvalidFirebaseToken::class);
        $h = $this->b64(json_encode(['alg' => 'none', 'kid' => 'k1']));
        $p = $this->b64(json_encode($this->claims()));
        $this->verifier()->verify("$h.$p.");
    }

    public function test_indian_numbers_are_normalised_to_e164(): void
    {
        $this->assertSame('+919876543210', IndianPhone::normalize('98765 43210'));
        $this->assertSame('+919876543210', IndianPhone::normalize('09876543210'));
        $this->assertSame('+919876543210', IndianPhone::normalize('+91-98765-43210'));
        $this->assertNull(IndianPhone::normalize('12345'));
        $this->assertNull(IndianPhone::normalize('1234567890'));
    }
}
