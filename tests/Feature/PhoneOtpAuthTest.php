<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Support\FirebaseIdToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhoneOtpAuthTest extends TestCase
{
    use RefreshDatabase;

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

        config(['firebase.project_id' => self::PROJECT, 'firebase.api_key' => 'test-key', 'firebase.app_id' => '1:1:web:1']);
        $this->app->instance(FirebaseIdToken::class, new FirebaseIdToken(self::PROJECT, fn () => ['k1' => $this->pem], 60));
        Storage::fake('local');
    }

    private function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private function idToken(string $phone = '+919876543210'): string
    {
        $now = time();
        $h = $this->b64(json_encode(['alg' => 'RS256', 'kid' => 'k1']));
        $p = $this->b64(json_encode([
            'iss' => 'https://securetoken.google.com/' . self::PROJECT, 'aud' => self::PROJECT,
            'iat' => $now, 'auth_time' => $now, 'exp' => $now + 3600, 'sub' => 'uid-' . $phone,
            'phone_number' => $phone, 'firebase' => ['sign_in_provider' => 'phone'],
        ]));
        openssl_sign("$h.$p", $sig, $this->key, OPENSSL_ALGO_SHA256);

        return "$h.$p." . $this->b64($sig);
    }

    private function role(string $name): void
    {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    public function test_login_page_offers_mobile_otp_when_firebase_is_configured(): void
    {
        $this->get(route('customer.login'))->assertOk()->assertSee('Mobile OTP')->assertSee('otp-recaptcha', false);
        $this->get(route('vendor.login'))->assertOk()->assertSee('Mobile OTP');
    }

    public function test_otp_tab_is_hidden_when_firebase_is_not_configured(): void
    {
        config(['firebase.api_key' => null]);
        $this->get(route('customer.login'))->assertOk()->assertDontSee('data-auth-tabs', false);
    }

    public function test_a_customer_can_sign_up_with_a_verified_mobile_and_no_email(): void
    {
        $this->role('customer');
        $this->post(route('otp.customer.register'), ['id_token' => $this->idToken(), 'name' => 'Meera'])
            ->assertRedirect(route('customer.dashboard'));

        $user = User::where('phone', '+919876543210')->firstOrFail();
        $this->assertNull($user->email);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue($user->hasRole('customer'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_mobile_already_on_an_account_cannot_sign_up_again(): void
    {
        $this->role('customer');
        User::create(['name' => 'Old', 'email' => 'old@example.com', 'password' => bcrypt('x'), 'phone' => '+919876543210', 'phone_verified_at' => now()]);
        $this->post(route('otp.customer.register'), ['id_token' => $this->idToken(), 'name' => 'Meera'])
            ->assertSessionHasErrors('id_token');
        $this->assertSame(1, User::count());
    }

    public function test_a_verified_customer_signs_in_with_mobile_otp(): void
    {
        $this->role('customer');
        $user = User::create(['name' => 'Asha', 'email' => 'asha@example.com', 'password' => bcrypt('x'), 'phone' => '+919876543210', 'phone_verified_at' => now()]);
        $user->assignRole('customer');

        $this->post(route('otp.customer.login'), ['id_token' => $this->idToken()])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_number_that_was_never_verified_cannot_sign_in(): void
    {
        $this->role('customer');
        $user = User::create(['name' => 'Asha', 'email' => 'asha@example.com', 'password' => bcrypt('x'), 'phone' => '+919876543210']);
        $user->assignRole('customer');

        $this->post(route('otp.customer.login'), ['id_token' => $this->idToken()])->assertSessionHasErrors('id_token');
        $this->assertGuest();
    }

    public function test_a_customer_number_cannot_open_the_seller_panel(): void
    {
        $this->role('customer');
        $user = User::create(['name' => 'Asha', 'email' => 'asha@example.com', 'password' => bcrypt('x'), 'phone' => '+919876543210', 'phone_verified_at' => now()]);
        $user->assignRole('customer');

        $this->post(route('otp.vendor.login'), ['id_token' => $this->idToken()])->assertSessionHasErrors('id_token');
        $this->assertGuest();
    }

    public function test_a_forged_or_missing_token_is_refused(): void
    {
        $this->post(route('otp.customer.login'), ['id_token' => 'not-a-token'])->assertSessionHasErrors('id_token');
        $this->post(route('otp.customer.login'), [])->assertSessionHasErrors('id_token');
        $this->assertGuest();
    }

    private function sellerPayload(array $override = []): array
    {
        $category = \App\Models\Category::create(['name' => 'Sofas', 'slug' => 'sofas']);

        return $override + [
            'name' => 'Ravi', 'email' => 'ravi@shop.com', 'password' => 'Str0ng!Pass1', 'password_confirmation' => 'Str0ng!Pass1',
            'company_name' => 'Ravi Woods', 'phone' => '9999999999', 'business_type' => 'manufacturer', 'seller_type' => 'manufacturer',
            'categories' => [$category->id], 'description' => 'Solid wood furniture made in our Jaipur workshop since 2005.',
            'address' => '5 Market Rd', 'country' => 'India', 'state' => 'Rajasthan', 'city' => 'Jaipur', 'postal_code' => '302001',
            'registration_number' => 'UDYAM-RJ-17-0000001', 'gst_registered' => 0,
            'doc_registration' => UploadedFile::fake()->create('registration.pdf', 120, 'application/pdf'),
            'doc_aadhaar' => UploadedFile::fake()->create('aadhaar.pdf', 90, 'application/pdf'),
            'bank_holder' => 'Ravi Woods', 'bank_name' => 'HDFC Bank', 'bank_account' => '50100012345678', 'bank_account_confirmation' => '50100012345678', 'bank_ifsc' => 'HDFC0001234',
            'terms' => 1,
        ];
    }

    public function test_seller_application_needs_seller_type_and_aadhaar(): void
    {
        $payload = $this->sellerPayload();
        unset($payload['seller_type'], $payload['doc_aadhaar']);
        $this->post(route('vendor.register.post'), $payload)->assertSessionHasErrors(['seller_type', 'doc_aadhaar']);
        $this->assertSame(0, Vendor::count());
    }

    public function test_seller_application_stores_seller_type_and_aadhaar_document(): void
    {
        $this->post(route('vendor.register.post'), $this->sellerPayload())->assertRedirect(route('vendor.login'));

        $vendor = Vendor::where('email', 'ravi@shop.com')->firstOrFail();
        $this->assertSame('manufacturer', $vendor->seller_type);
        $this->assertTrue($vendor->documents()->where('type', VendorDocument::AADHAAR)->exists());
        $this->assertContains(VendorDocument::AADHAAR, $vendor->requiredDocumentTypes());
    }

    public function test_seller_can_verify_the_mobile_during_application(): void
    {
        $this->post(route('vendor.register.post'), $this->sellerPayload(['phone_token' => $this->idToken('+919999999999')]))
            ->assertRedirect(route('vendor.login'));

        $user = User::where('email', 'ravi@shop.com')->firstOrFail();
        $this->assertSame('+919999999999', $user->phone);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_a_verified_mobile_that_differs_from_the_application_is_refused(): void
    {
        $this->post(route('vendor.register.post'), $this->sellerPayload(['phone_token' => $this->idToken('+918888888888')]))
            ->assertSessionHasErrors('phone_token');
        $this->assertSame(0, Vendor::count());
    }

    public function test_a_verified_seller_signs_in_with_mobile_otp(): void
    {
        $this->role('vendor');
        $user = User::create(['name' => 'Ravi', 'email' => 'ravi@shop.com', 'password' => bcrypt('x'), 'phone' => '+919999999999', 'phone_verified_at' => now()]);
        $user->assignRole('vendor');

        $this->post(route('otp.vendor.login'), ['id_token' => $this->idToken('+919999999999')])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
}
