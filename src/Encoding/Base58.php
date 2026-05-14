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

use Cyvax\Exceptions\PrivatebinException;

/**
 * Base58 encoder/decoder
 *
 * Uses GMP when available for faster conversion and falls back to a native
 * pure-PHP implementation otherwise.
 */
class Base58
{
    const ALPHABET = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

    /** @var string */
    private $alphabet;

    /**
     * @param string $alphabet
     * @throws PrivatebinException
     */
    public function __construct(string $alphabet = self::ALPHABET)
    {
        if (strlen($alphabet) !== 58) {
            throw new PrivatebinException('Base58 alphabet must be exactly 58 characters.');
        }
        if (count(array_unique(str_split($alphabet))) !== 58) {
            throw new PrivatebinException('Base58 alphabet must contain 58 unique characters.');
        }

        $this->alphabet = $alphabet;
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

        $result = extension_loaded('gmp')
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

        $result = extension_loaded('gmp')
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
            $pos = strpos($this->alphabet, $data[$i]);
            if ($pos === false) {
                throw new PrivatebinException("Invalid Base58 character: '{$data[$i]}'");
            }
            $n = gmp_add(gmp_mul($n, $base), gmp_init($pos));
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

        return implode('', array_map(function ($d) {
            return $this->alphabet[$d];
        }, $digits));
    }

    /**
     * @param string $data
     * @return string
     * @throws PrivatebinException
     */
    private function decodeNative(string $data): string
    {
        $map = array_flip(str_split($this->alphabet));
        $bytes = [0];

        for ($i = 0; $i < strlen($data); $i++) {
            if (!isset($map[$data[$i]])) {
                throw new PrivatebinException("Invalid Base58 character: '{$data[$i]}'");
            }
            $carry = $map[$data[$i]];
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

        return implode('', array_map('chr', $bytes));
    }
}