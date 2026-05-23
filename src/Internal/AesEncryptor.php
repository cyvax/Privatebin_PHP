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

namespace Cyvax\Internal;

use Cyvax\Contracts\PasteEncryptorInterface;
use Cyvax\Encoding\Base58;
use Cyvax\Exceptions\PrivatebinException;
use Exception;

/**
 * @internal
 */
class AesEncryptor implements PasteEncryptorInterface
{
    /**
     * @param array $pasteData
     * @param string $compression
     * @param string $formatter
     * @param bool $discussion
     * @param bool $burn
     * @param string $expire
     * @param string|null $userPassword
     * @param bool $debug
     * @return array{data: array, b58: string}
     * @throws PrivatebinException
     */
    public function encrypt(
        array   $pasteData,
        string  $compression,
        string  $formatter,
        bool    $discussion,
        bool    $burn,
        string  $expire,
        ?string $userPassword = null,
        bool    $debug = false
    ): array
    {
        $base58 = new Base58();

        try {
            $nonce = random_bytes(16);
            $salt = random_bytes(8);
            $password = random_bytes(32);
        } catch (Exception $e) {
            throw new PrivatebinException("Unable to generate random encryption material.", 0, $e);
        }

        $b58 = $base58->encode($password);

        $authData = [
            [
                base64_encode($nonce),
                base64_encode($salt),
                100000,
                256,
                128,
                "aes",
                "gcm",
                $compression,
            ],
            $formatter,
            (int) $discussion,
            (int) $burn,
        ];

        $pass = $userPassword ? ($password . $userPassword) : $password;

        $key = openssl_pbkdf2($pass, $salt, 32, 100000, "sha256");
        if ($key === false) {
            throw new PrivatebinException("Unable to generate encryption key.");
        }

        $encodedPasteData = json_encode($pasteData, JSON_UNESCAPED_SLASHES);
        if ($encodedPasteData === false) {
            throw new PrivatebinException("Unable to encode paste data.");
        }

        if ($compression === "zlib") {
            if (!extension_loaded('zlib')) {
                throw new PrivatebinException("ext-zlib is required for zlib compression.");
            }
            $zlibDef = deflate_init(ZLIB_ENCODING_RAW);
            $paste = deflate_add($zlibDef, $encodedPasteData, ZLIB_FINISH);
            if ($paste === false) {
                throw new PrivatebinException("Compression failed.");
            }
        } else {
            $paste = $encodedPasteData;
        }

        $aad = json_encode($authData, JSON_UNESCAPED_SLASHES);
        if ($aad === false) {
            throw new PrivatebinException("Unable to encode authentication data.");
        }

        $crypt = openssl_encrypt(
            $paste,
            "aes-256-gcm",
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            $aad,
            16
        );

        if ($crypt === false) {
            throw new PrivatebinException("Encryption failed.");
        }

        $data = [
            "v" => 2,
            "adata" => $authData,
            "ct" => base64_encode($crypt . $tag),
            "meta" => [
                "expire" => $expire,
            ],
        ];

        if ($debug) {
            echo sprintf(
                "Base58 Hash: %s<br>" .
                "PBKDF2: %s<br>" .
                "Paste Data: %s<br>" .
                "Auth Data: <pre>%s</pre><br>" .
                "CipherText: %s<br>" .
                "CipherTag: %s<br>" .
                "Post Data: <pre>%s</pre><br>",
                $b58,
                base64_encode($key),
                $encodedPasteData,
                print_r($authData, true),
                base64_encode($crypt),
                base64_encode($tag),
                print_r($data, true)
            );
        }

        return [
            "data" => $data,
            "b58" => $b58,
        ];
    }

    /**
     * @param array $fetchResult
     * @param string $b58
     * @param string|null $userPassword
     * @return array{paste: string, attachment: string|null, attachment_name: string|null}
     * @throws PrivatebinException
     */
    public function decrypt(array $fetchResult, string $b58, ?string $userPassword = null): array
    {
        $base58 = new Base58();
        $rawKey = $base58->decode($b58);

        if (!isset($fetchResult["adata"], $fetchResult["ct"])) {
            throw new PrivatebinException("Invalid fetch result.");
        }

        $adata = $fetchResult['adata'];
        if (!is_array($adata) || !isset($adata[0][0], $adata[0][1], $adata[0][7])) {
            throw new PrivatebinException("Invalid authentication data.");
        }

        $nonce = base64_decode($adata[0][0], true);
        $salt = base64_decode($adata[0][1], true);
        $compression = $adata[0][7];

        if (!is_string($compression)) {
            throw new PrivatebinException("Invalid compression type.");
        }

        if ($nonce === false || $salt === false) {
            throw new PrivatebinException("Invalid adata: base64 decode failed.");
        }

        $pass = $userPassword ? ($rawKey . $userPassword) : $rawKey;

        $key = openssl_pbkdf2($pass, $salt, 32, 100000, "sha256");
        if ($key === false) {
            throw new PrivatebinException("Unable to derive decryption key.");
        }

        if (!is_string($fetchResult["ct"])) {
            throw new PrivatebinException("Invalid ciphertext.");
        }

        $ctRaw = base64_decode($fetchResult["ct"], true);
        if ($ctRaw === false || strlen($ctRaw) < 17) {
            throw new PrivatebinException("Invalid ciphertext.");
        }

        $crypt = substr($ctRaw, 0, -16);
        $tag = substr($ctRaw, -16);

        $aad = json_encode($adata, JSON_UNESCAPED_SLASHES);
        if ($aad === false) {
            throw new PrivatebinException("Failed to encode authentication data.");
        }

        $plain = openssl_decrypt($crypt, "aes-256-gcm", $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad);
        if ($plain === false) {
            throw new PrivatebinException("Decryption failed.");
        }

        if ($compression === "zlib") {
            if (!extension_loaded('zlib')) {
                throw new PrivatebinException("ext-zlib is required for zlib decompression.");
            }
            $ctx = inflate_init(ZLIB_ENCODING_RAW);
            $plain = inflate_add($ctx, $plain, ZLIB_FINISH);
            if ($plain === false) {
                throw new PrivatebinException("Decompression failed.");
            }
        } elseif ($compression !== "none") {
            throw new PrivatebinException("Unsupported compression type.");
        }

        $data = json_decode($plain, true);
        if (!is_array($data)) {
            throw new PrivatebinException("Failed to decode decrypted payload.");
        }

        return [
            'paste' => $data['paste'] ?? '',
            'attachment' => $data['attachment'] ?? null,
            'attachment_name' => $data['attachment_name'] ?? null,
        ];
    }
}