<?php

namespace Tests\Unit\Support;

use App\Support\IndianPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IndianPhoneTest extends TestCase
{
    public static function validNumbers(): array
    {
        return [
            'ten digits' => ['9876543210', '+919876543210'],
            'spaces and dashes' => ['98765-43210', '+919876543210'],
            'with 91 prefix' => ['919876543210', '+919876543210'],
            'with +91' => ['+91 98765 43210', '+919876543210'],
            'leading zero' => ['09876543210', '+919876543210'],
            'starts with 6' => ['6000000000', '+916000000000'],
            'foreign E.164' => ['+1 (415) 555-2671', '+14155552671'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_normalizes_valid_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, IndianPhone::normalize($input));
    }

    public static function invalidNumbers(): array
    {
        return [
            'null' => [null], 'empty' => [''], 'spaces' => ['   '],
            'starts with 5' => ['5876543210'], 'too short' => ['98765'], 'too long' => ['98765432101'],
            'letters' => ['abcdefghij'], 'plus zero' => ['+0123456789'], 'plus too short' => ['+1234'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_rejects_invalid_numbers(?string $input): void
    {
        $this->assertNull(IndianPhone::normalize($input));
    }

    public function test_local_digits_are_the_last_ten(): void
    {
        $this->assertSame('9876543210', IndianPhone::localDigits('+919876543210'));
    }
}
