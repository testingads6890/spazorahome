<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use PHPUnit\Framework\TestCase;

class EmailVerificationContractTest extends TestCase
{
    public function test_user_supports_laravel_email_verification_contract(): void
    {
        $this->assertTrue(is_subclass_of(User::class, MustVerifyEmail::class));
    }
}
