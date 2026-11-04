<?php

namespace Tests\Unit\Support;

use App\Support\GstState;
use PHPUnit\Framework\TestCase;

class GstStateTest extends TestCase
{
    public function test_code_accepts_name_abbreviation_or_code_case_insensitively(): void
    {
        $this->assertSame('27', GstState::code('Maharashtra'));
        $this->assertSame('27', GstState::code('  maharashtra '));
        $this->assertSame('27', GstState::code('MH'));
        $this->assertSame('27', GstState::code('27'));
        $this->assertSame('07', GstState::code('delhi'));
    }

    public function test_code_is_null_for_unknown_or_empty(): void
    {
        $this->assertNull(GstState::code('Atlantis'));
        $this->assertNull(GstState::code(''));
        $this->assertNull(GstState::code(null));
        $this->assertNull(GstState::code('25')); // retired code
    }

    public function test_name_resolves_canonical_name(): void
    {
        $this->assertSame('Karnataka', GstState::name('ka'));
        $this->assertNull(GstState::name('xx'));
    }

    public function test_options_and_names(): void
    {
        $options = GstState::options();
        $this->assertSame('Jammu and Kashmir', $options['01']);
        $this->assertCount(count($options), GstState::names());
        $this->assertContains('Ladakh', GstState::names());
    }

    public function test_gstin_validation(): void
    {
        $this->assertTrue(GstState::isValidGstin('27AAPFU0939F1ZV'));
        $this->assertFalse(GstState::isValidGstin('27aapfu0939f1zv'));
        $this->assertFalse(GstState::isValidGstin('27AAPFU0939F1Z'));
        $this->assertFalse(GstState::isValidGstin('27AAPFU0939F0ZV'));
        $this->assertFalse(GstState::isValidGstin(null));
    }

    public function test_code_from_gstin(): void
    {
        $this->assertSame('27', GstState::codeFromGstin('27AAPFU0939F1ZV'));
        $this->assertNull(GstState::codeFromGstin('99AAPFU0939F1ZV'));
        $this->assertNull(GstState::codeFromGstin(null));
    }
}
