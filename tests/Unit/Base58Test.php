<?php

declare(strict_types=1);

namespace Cyvax\Tests\Unit;

use Cyvax\Encoding\Base58;
use Cyvax\Exceptions\PrivatebinException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class Base58Test extends TestCase
{
    /** @var Base58 $b58 */
    private $b58;

    protected function setUp(): void
    {
        $this->b58 = new Base58();
    }

    public function testConstructorDefaultAlphabet(): void
    {
        $this->assertInstanceOf(Base58::class, new Base58());
    }

    public function testConstructorCustomAlphabet(): void
    {
        $custom = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUV';

        $this->assertInstanceOf(Base58::class, new Base58($custom));
    }

    public function testConstructorThrowsOnTooShortAlphabet(): void
    {
        $this->expectException(PrivatebinException::class);

        new Base58('short');
    }

    public function testConstructorThrowsOnTooLongAlphabet(): void
    {
        $this->expectException(PrivatebinException::class);

        new Base58(str_repeat('a', 59));
    }

    public function testConstructorThrowsOnDuplicateCharacters(): void
    {
        $dupe = substr(Base58::ALPHABET, 0, 57) . '1';

        $this->expectException(PrivatebinException::class);

        new Base58($dupe);
    }

    public function testAlphabetLength(): void
    {
        $this->assertSame(58, strlen(Base58::ALPHABET));
    }

    public function testAlphabetIsUnique(): void
    {
        $this->assertSame(58, count(array_unique(str_split(Base58::ALPHABET))));
    }

    public function testAlphabetExcludesAmbiguousChars(): void
    {
        $this->assertStringNotContainsString('0', Base58::ALPHABET);
        $this->assertStringNotContainsString('O', Base58::ALPHABET);
        $this->assertStringNotContainsString('I', Base58::ALPHABET);
        $this->assertStringNotContainsString('l', Base58::ALPHABET);
    }

    #[DataProvider('vectorProvider')]
    public function testEncodeMatchesTuupola(string $raw, string $encoded): void
    {
        $this->assertSame($encoded, $this->b58->encode($raw));
    }

    #[DataProvider('vectorProvider')]
    public function testDecodeMatchesTuupola(string $raw, string $encoded): void
    {
        $this->assertSame($raw, $this->b58->decode($encoded));
    }

    #[DataProvider('vectorProvider')]
    public function testRoundTrip(string $raw, string $encoded): void
    {
        $this->assertSame($raw, $this->b58->decode($this->b58->encode($raw)));
        $this->assertSame($encoded, $this->b58->encode($this->b58->decode($encoded)));
    }

    public static function vectorProvider(): array
    {
        return [
            'empty string' => ['', ''],
            'single null byte' => ["\x00", '1'],
            'two null bytes' => ["\x00\x00", '11'],
            'three null bytes' => ["\x00\x00\x00", '111'],
            'two nulls + data' => ["\x00\x00\x01\x02\x03", '11Ldp'],
            'null + ff pattern' => ["\x00\xff\x00\xff", '12Uesp'],
            'Hello World' => ['Hello World', 'JxF12TrwUP45BMd'],
            'quick brown fox' => [
                'The quick brown fox jumped over the lazy dog.',
                '368hQnLns1bXgT1KttS6Y5WzHJdeTik6aFv6VvPtHo9yeADRhQTiBUdEtwmwKb',
            ],
            'single 0x01' => ["\x01", '2'],
            'single 0xff' => ["\xff", '5Q'],
            'three 0xff bytes' => ["\xff\xff\xff", '2UzHL'],
            'sequential 8 bytes' => ["\x01\x02\x03\x04\x05\x06\x07\x08", 'An6UebxCZd'],
            'deadbeef' => ["\xde\xad\xbe\xef", '6h8cQN'],
            'cafebabe' => ["\xca\xfe\xba\xbe", '6Bx4TP'],
            '0x00-0xff pattern' => [
                "\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff",
                '1UoWww8DGaVGLtea7zU7p',
            ],
            'reverse hex pattern' => [
                "\x01\x23\x45\x67\x89\xab\xcd\xef\xfe\xdc\xba\x98\x76\x54\x32\x10",
                '99dn6s7bZpEGBoKc7YnPh',
            ],
            '0x00-0x1f range' => [
                implode('', array_map('chr', range(0, 31))),
                '1thX6LZfHDZZKUs92febYZhYRcXddmzfzF2NvTkPNE',
            ],
            '0x80-0x9f range' => [
                implode('', array_map('chr', range(128, 159))),
                '9edj24heFqJDR9LShNnGteLCwsMNjmWjWuJEV2f9P2ei',
            ],
        ];
    }

    #[DataProvider('invalidCharProvider')]
    public function testDecodeThrowsOnInvalidChar(string $input): void
    {
        $this->expectException(PrivatebinException::class);

        $this->b58->decode($input);
    }

    public static function invalidCharProvider(): array
    {
        return [
            'zero digit "0"' => ['0'],
            'uppercase O' => ['O'],
            'uppercase I' => ['I'],
            'lowercase l' => ['l'],
            'space' => [' '],
            'plus sign' => ['+'],
            'slash' => ['/'],
        ];
    }

    // --- explicit path coverage via reflection ---

    private function callPrivate(string $method, string $arg): string
    {
        $ref = new ReflectionMethod(Base58::class, $method);
        return (string) $ref->invoke($this->b58, $arg);
    }

    #[DataProvider('pathVectorProvider')]
    public function testNativeEncode(string $raw, string $encoded): void
    {
        $this->assertSame($encoded, $this->callPrivate('encodeNative', $raw));
    }

    #[DataProvider('pathVectorProvider')]
    public function testNativeDecode(string $raw, string $encoded): void
    {
        $this->assertSame($raw, $this->callPrivate('decodeNative', $encoded));
    }

    #[DataProvider('pathVectorProvider')]
    public function testNativeRoundTrip(string $raw, string $encoded): void
    {
        $this->assertSame($raw, $this->callPrivate('decodeNative', $this->callPrivate('encodeNative', $raw)));
        $this->assertSame($encoded, $this->callPrivate('encodeNative', $this->callPrivate('decodeNative', $encoded)));
    }

    #[DataProvider('pathVectorProvider')]
    public function testGmpEncode(string $raw, string $encoded): void
    {
        if (!extension_loaded('gmp')) {
            $this->markTestSkipped('ext-gmp not available');
        }
        $this->assertSame($encoded, $this->callPrivate('encodeGmp', $raw));
    }

    #[DataProvider('pathVectorProvider')]
    public function testGmpDecode(string $raw, string $encoded): void
    {
        if (!extension_loaded('gmp')) {
            $this->markTestSkipped('ext-gmp not available');
        }
        $this->assertSame($raw, $this->callPrivate('decodeGmp', $encoded));
    }

    #[DataProvider('pathVectorProvider')]
    public function testGmpRoundTrip(string $raw, string $encoded): void
    {
        if (!extension_loaded('gmp')) {
            $this->markTestSkipped('ext-gmp not available');
        }
        $this->assertSame($raw, $this->callPrivate('decodeGmp', $this->callPrivate('encodeGmp', $raw)));
        $this->assertSame($encoded, $this->callPrivate('encodeGmp', $this->callPrivate('decodeGmp', $encoded)));
    }

    public static function pathVectorProvider(): array
    {
        return [
            'Hello World' => ['Hello World', 'JxF12TrwUP45BMd'],
            'deadbeef' => ["\xde\xad\xbe\xef", '6h8cQN'],
            'single 0xff' => ["\xff", '5Q'],
            'sequential 8 bytes' => ["\x01\x02\x03\x04\x05\x06\x07\x08", 'An6UebxCZd'],
            'quick brown fox' => [
                'The quick brown fox jumped over the lazy dog.',
                '368hQnLns1bXgT1KttS6Y5WzHJdeTik6aFv6VvPtHo9yeADRhQTiBUdEtwmwKb',
            ],
        ];
    }

    public function testNativeDecodeThrowsOnInvalidChar(): void
    {
        $this->expectException(PrivatebinException::class);

        $this->callPrivate('decodeNative', '0');
    }

    public function testGmpDecodeThrowsOnInvalidChar(): void
    {
        if (!extension_loaded('gmp')) {
            $this->markTestSkipped('ext-gmp not available');
        }
        $this->expectException(PrivatebinException::class);

        $this->callPrivate('decodeGmp', '0');
    }
}
