<?php

namespace Tests\Unit\Support;

use App\Rules\LinkOrPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LinkOrPathTest extends TestCase
{
    public static function cases(): array
    {
        return [
            'https' => ['https://example.com/a?b=1', true],
            'http' => ['http://example.com', true],
            'site path' => ['/search?category=3', true],
            'root' => ['/', true],
            'protocol relative' => ['//evil.com', false],
            'backslash host' => ['/\\evil.com', false],
            'javascript' => ['javascript:alert(1)', false],
            'bare word' => ['products', false],
            'empty' => ['', false],
            'space' => ['/a b', false],
            'quote' => ['/a"b', false],
            'angle' => ['/<script>', false],
            'ftp' => ['ftp://example.com', false],
            'non string' => [123, false],
            'null' => [null, false],
        ];
    }

    #[DataProvider('cases')]
    public function test_passes(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, LinkOrPath::passes($value));
    }

    public function test_validate_calls_fail_only_for_bad_values(): void
    {
        $failed = 0;
        $fail = function () use (&$failed) { $failed++; };
        (new LinkOrPath)->validate('link', '/ok', $fail);
        $this->assertSame(0, $failed);
        (new LinkOrPath)->validate('link', 'javascript:x', $fail);
        $this->assertSame(1, $failed);
    }
}
