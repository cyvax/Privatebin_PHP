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

use Cyvax\Encoding\Base58;
use Cyvax\Exceptions\PrivatebinException;
use Exception;

/**
 * @internal
 */
abstract class PrivatebinPHPBase
{
    const COMPRESSION_VALUES = [
        "zlib",
        "none",
    ];

    const EXPIRE_VALUES = [
        "5min",
        "10min",
        "1hour",
        "1day",
        "1week",
        "1month",
        "1year",
        "never",
    ];

    const FORMATTER_VALUES = [
        "plaintext",
        "syntaxhighlighting",
        "markdown",
    ];

    protected $options = [
        "url" => "https://paste.i2pd.xyz/",
        "compression" => "zlib",
        "formatter" => "plaintext",
        "attachment" => null,
        "attachment_name" => null,
        "password" => null,
        "expire" => "1day",
        "discussion" => false,
        "burn" => false,
        "text" => "",
        "debug" => false,
        "ssl_verify" => true,
    ];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): PrivatebinPHPBase
    {
        $this->options["url"] = $url;
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText(string $text): PrivatebinPHPBase
    {
        $this->options["text"] = $text;
        return $this;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword(string $password): PrivatebinPHPBase
    {
        $this->options["password"] = $password;
        return $this;
    }

    /**
     * @param string $expire
     * @param bool $bypass
     * @return $this
     * @throws PrivatebinException
     */
    public function setExpire(string $expire, bool $bypass = false): PrivatebinPHPBase
    {
        if (!in_array($expire, self::EXPIRE_VALUES, true) && !$bypass) {
            throw new PrivatebinException('$expire not in default value and $bypass is false');
        }

        $this->options["expire"] = $expire;
        return $this;
    }

    /**
     * @param string $formatter
     * @param bool $bypass
     * @return $this
     * @throws PrivatebinException
     */
    public function setFormatter(string $formatter, bool $bypass = false): PrivatebinPHPBase
    {
        if (!in_array($formatter, self::FORMATTER_VALUES, true) && !$bypass) {
            throw new PrivatebinException('$formatter not in default value and $bypass is false');
        }

        $this->options["formatter"] = $formatter;
        return $this;
    }

    /**
     * @param string $compression
     * @return $this
     * @throws PrivatebinException
     */
    public function setCompression(string $compression): PrivatebinPHPBase
    {
        if (!in_array($compression, self::COMPRESSION_VALUES, true)) {
            throw new PrivatebinException('Unknown compression type, expected zlib or none.');
        }

        $this->options["compression"] = $compression;
        return $this;
    }

    /**
     * @param bool $discussion
     * @return $this
     */
    public function setDiscussion(bool $discussion): PrivatebinPHPBase
    {
        if ($discussion && $this->options["burn"]) {
            $this->options["burn"] = false;
        }

        $this->options["discussion"] = $discussion;
        return $this;
    }

    /**
     * @param bool $burn
     * @return $this
     */
    public function setBurn(bool $burn): PrivatebinPHPBase
    {
        if ($burn && $this->options["discussion"]) {
            $this->options["discussion"] = false;
        }

        $this->options["burn"] = $burn;
        return $this;
    }

    /**
     * @param string $fileLocation
     * @param string|null $filename
     * @return $this
     * @throws PrivatebinException
     */
    public function setAttachment(string $fileLocation, ?string $filename = null): PrivatebinPHPBase
    {
        $file = file_get_contents($fileLocation);

        if ($file === false) {
            throw new PrivatebinException("Unable to read attachment: " . $fileLocation);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_buffer($finfo, $file) : false;

        if ($finfo) {
            finfo_close($finfo);
        }

        $data = "data:" . ($mime ?: "application/octet-stream") . ";base64," . base64_encode($file);

        $this->options["attachment"] = $data;
        $this->options["attachment_name"] = $filename !== null ? $filename : basename($fileLocation);

        return $this;
    }

    /**
     * @param bool $debug
     * @return $this
     */
    public function setDebug(bool $debug): PrivatebinPHPBase
    {
        $this->options["debug"] = $debug;
        return $this;
    }

    /**
     * @param bool $sslVerify
     * @return $this
     */
    public function setSslVerify(bool $sslVerify): PrivatebinPHPBase
    {
        $this->options["ssl_verify"] = $sslVerify;
        return $this;
    }

    /**
     * @return array
     */
    private function getPasteData(): array
    {
        $pasteData = [
            "paste" => $this->options["text"],
        ];

        if ($this->options["attachment"] !== null) {
            $pasteData["attachment"] = $this->options["attachment"];
            $pasteData["attachment_name"] = $this->options["attachment_name"];
        }

        return $pasteData;
    }

    /**
     * @return array
     * @throws PrivatebinException
     */
    public function encode(): array
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
                $this->options["compression"],
            ],
            $this->options["formatter"],
            (int)$this->options["discussion"],
            (int)$this->options["burn"],
        ];

        $pass = $this->options["password"]
            ? ($password . $this->options["password"])
            : $password;

        $key = openssl_pbkdf2($pass, $salt, 32, 100000, "sha256");

        if ($key === false) {
            throw new PrivatebinException("Unable to generate encryption key.");
        }

        $pasteData = json_encode($this->getPasteData(), JSON_UNESCAPED_SLASHES);

        if ($pasteData === false) {
            throw new PrivatebinException("Unable to encode paste data.");
        }

        if ($this->options["text"] === "" && $this->options["attachment"] === null) {
            throw new PrivatebinException("Empty paste. Use setAttachment() or setText() before post.");
        }

        if ($this->options["burn"] && $this->options["discussion"]) {
            throw new PrivatebinException("Burn and discussion cannot both be enabled.");
        }

        if ($this->options["compression"] === "zlib") {
            $zlibDef = deflate_init(ZLIB_ENCODING_RAW);
            $paste = deflate_add($zlibDef, $pasteData, ZLIB_FINISH);

            if ($paste === false) {
                throw new PrivatebinException("Compression failed.");
            }
        } else {
            $paste = $pasteData;
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
                "expire" => $this->options["expire"],
            ],
        ];

        if ($this->options["debug"]) {
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
                $pasteData,
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
     * @param array $data
     * @return array
     * @throws PrivatebinException
     */
    public function post(array $data): array
    {
        if (!array_key_exists("data", $data) || !array_key_exists("b58", $data)) {
            throw new PrivatebinException("Wrong data provided.");
        }

        $curl = curl_init($this->options["url"]);
        if ($curl === false) {
            throw new PrivatebinException("Unable to initialize cURL.");
        }

        $postFields = json_encode($data["data"], JSON_UNESCAPED_SLASHES);
        if ($postFields === false) {
            throw new PrivatebinException("Unable to encode post data.");
        }

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->options["ssl_verify"] ? 2 : 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->options["ssl_verify"] ? 1 : 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "X-Requested-With: JSONHttpRequest",
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new PrivatebinException("cURL error: " . $error);
        }

        curl_close($curl);

        $result = json_decode($response);

        if (!is_object($result)) {
            throw new PrivatebinException("Invalid server response: " . $response);
        }

        if ($this->options["debug"]) {
            echo sprintf("Response: <pre>%s</pre>", print_r($result, true));
        }

        return [
            "requests_result" => $result,
            "b58" => $data["b58"],
        ];
    }

    /**
     * @return array
     * @throws PrivatebinException
     */
    public function encodeAndPost(): array
    {
        return $this->post($this->encode());
    }

    /**
     * @param string $id
     * @return array
     * @throws PrivatebinException
     */
    public function fetch(string $id): array
    {
        $url = rtrim($this->options["url"], "/") . "/?pasteid=" . urlencode($id);

        $curl = curl_init($url);
        if ($curl === false) {
            throw new PrivatebinException("Unable to initialize cURL.");
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "X-Requested-With: JSONHttpRequest",
        ]);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->options["ssl_verify"] ? 2 : 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->options["ssl_verify"] ? 1 : 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new PrivatebinException("cURL error: " . $error);
        }

        curl_close($curl);

        $result = json_decode($response, true);

        if (!is_array($result) || !isset($result['status']) || $result['status'] !== 0) {
            throw new PrivatebinException("Fetch failed: " . $response);
        }

        return $result;
    }

    /**
     * @param array $fetchResult
     * @param string $b58
     * @param string|null $password
     * @return array{paste: string, attachment: string|null, attachment_name: string|null}
     * @throws PrivatebinException
     */
    public function decrypt(array $fetchResult, string $b58, ?string $password = null): array
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

        $pass = $password ? ($rawKey . $password) : $rawKey;

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

    /**
     * @param string $id
     * @param string $b58
     * @param string|null $password
     * @return array{paste: string, attachment: string|null, attachment_name: string|null}
     * @throws PrivatebinException
     */
    public function fetchAndDecrypt(string $id, string $b58, ?string $password = null): array
    {
        return $this->decrypt($this->fetch($id), $b58, $password);
    }
}
