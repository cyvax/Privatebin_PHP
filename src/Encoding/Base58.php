<?php

declare(strict_types=1);

/*

Copyright (c) 2026 CyVaX

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

namespace Cyvax\Encoding;

use Cyvax\Contracts\Base58Interface;
use Cyvax\Exceptions\PrivatebinException;

/**
 * Base58 encoder/decoder
 *
 * Uses GMP when available for faster conversion and falls back to a native
 * pure-PHP implementation otherwise.
 */
class Base58 implements Base58Interface
{
    public const ALPHABET = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

    /** @var string */
    private $alphabet;

    /** @var array<string,int> */
    private $map;

    /** @var bool */
    private $useGmp;

    /**
     * @param string $alphabet
     * @throws PrivatebinException
     */
    public function __construct(string $alphabet = self::ALPHABET)
    {
        if (strlen($alphabet) !== 58) {
            throw new PrivatebinException('Base58 alphabet must be exactly 58 characters.');
        }

        $chars = str_split($alphabet);
        if (count(array_unique($chars)) !== 58) {
            throw new PrivatebinException('Base58 alphabet must contain 58 unique characters.');
        }

        $this->alphabet = $alphabet;
        $this->map = array_flip($chars);
        $this->useGmp = extension_loaded('gmp');
    }

    /**
     * Encode binary data to a Base58 string.
     *
     * @param string $data
     * @return string
     */
    public function encode(string $data): string
    {
        $leadingZeros = 0;
        while ($leadingZeros < strlen($data) && $data[$leadingZeros] === "\x00") {
            $leadingZeros++;
        }

        $payload = substr($data, $leadingZeros);

        if ($payload === '' || $payload === false) {
            return str_repeat($this->alphabet[0], $leadingZeros);
        }

        $result = $this->useGmp
            ? $this->encodeGmp($payload)
            : $this->encodeNative($payload);

        return str_repeat($this->alphabet[0], $leadingZeros) . $result;
    }

    /**
     * Decode a Base58 string back to binary data.
     *
     * @param string $data
     * @return string
     * @throws PrivatebinException
     */
    public function decode(string $data): string
    {
        $leadingZeros = 0;
        while ($leadingZeros < strlen($data) && $data[$leadingZeros] === $this->alphabet[0]) {
            $leadingZeros++;
        }

        $payload = substr($data, $leadingZeros);

        if ($payload === '' || $payload === false) {
            return str_repeat("\x00", $leadingZeros);
        }

        $result = $this->useGmp
            ? $this->decodeGmp($payload)
            : $this->decodeNative($payload);

        return str_repeat("\x00", $leadingZeros) . $result;
    }

    /**
     * @param string $data
     * @return string
     */
    private function encodeGmp(string $data): string
    {
        $n = gmp_import($data, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);
        $base = gmp_init(58);
        $out = '';

        while (gmp_cmp($n, 0) > 0) {
            [$n, $rem] = gmp_div_qr($n, $base);
            $out = $this->alphabet[gmp_intval($rem)] . $out;
        }

        return $out;
    }

    /**
     * @param string $data
     * @return string
     * @throws PrivatebinException
     */
    private function decodeGmp(string $data): string
    {
        $n = gmp_init(0);
        $base = gmp_init(58);

        for ($i = 0; $i < strlen($data); $i++) {
            if (!isset($this->map[$data[$i]])) {
                throw $this->invalidCharacterException($data[$i], $i);
            }
            $n = gmp_add(gmp_mul($n, $base), gmp_init($this->map[$data[$i]]));
        }

        return gmp_cmp($n, 0) === 0 ? '' : gmp_export($n, 1, GMP_MSW_FIRST | GMP_BIG_ENDIAN);
    }

    /**
     * @param string $data
     * @return string
     */
    private function encodeNative(string $data): string
    {
        $digits = [0];

        for ($i = 0; $i < strlen($data); $i++) {
            $carry = ord($data[$i]);
            for ($j = count($digits) - 1; $j >= 0; $j--) {
                $carry += $digits[$j] << 8;
                $digits[$j] = $carry % 58;
                $carry = (int)($carry / 58);
            }
            while ($carry > 0) {
                array_unshift($digits, $carry % 58);
                $carry = (int)($carry / 58);
            }
        }

        while (count($digits) > 1 && $digits[0] === 0) {
            array_shift($digits);
        }

        $out = '';
        foreach ($digits as $digit) {
            $out .= $this->alphabet[$digit];
        }

        return $out;
    }

    /**
     * @param string $data
     * @return string
     * @throws PrivatebinException
     */
    private function decodeNative(string $data): string
    {
        $bytes = [0];

        for ($i = 0; $i < strlen($data); $i++) {
            if (!isset($this->map[$data[$i]])) {
                throw $this->invalidCharacterException($data[$i], $i);
            }
            $carry = $this->map[$data[$i]];
            for ($j = count($bytes) - 1; $j >= 0; $j--) {
                $carry += $bytes[$j] * 58;
                $bytes[$j] = $carry & 0xff;
                $carry >>= 8;
            }
            while ($carry > 0) {
                array_unshift($bytes, $carry & 0xff);
                $carry >>= 8;
            }
        }

        while (count($bytes) > 1 && $bytes[0] === 0) {
            array_shift($bytes);
        }

        $out = '';
        foreach ($bytes as $byte) {
            $out .= chr($byte);
        }

        return $out;
    }

    /**
     * @param string $char
     * @param int $offset
     * @return PrivatebinException
     */
    private function invalidCharacterException(string $char, int $offset): PrivatebinException
    {
        return new PrivatebinException(sprintf(
            'Invalid Base58 character at offset %d: 0x%02X',
            $offset,
            ord($char)
        ));
    }
}
