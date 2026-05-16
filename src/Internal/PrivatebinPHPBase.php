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

use Cyvax\Enums\Compression;
use Cyvax\Enums\Expire;
use Cyvax\Enums\Formatter;
use Cyvax\Exceptions\PrivatebinException;
use Cyvax\Internal\Http\CurlHttpClient;
use Cyvax\Internal\Http\HttpClientInterface;

/**
 * @internal
 */
abstract class PrivatebinPHPBase
{
    const COMPRESSION_VALUES = [
        Compression::Zlib,
        Compression::None,
    ];

    const EXPIRE_VALUES = [
        Expire::FiveMin,
        Expire::TenMin,
        Expire::OneHour,
        Expire::OneDay,
        Expire::OneWeek,
        Expire::OneMonth,
        Expire::OneYear,
        Expire::Never,
    ];

    const FORMATTER_VALUES = [
        Formatter::Plaintext,
        Formatter::SyntaxHighlighting,
        Formatter::Markdown,
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

    /** @var HttpClientInterface $httpClient */
    private $httpClient;

    /** @var PasteEncryptorInterface $encryptor */
    private $encryptor;

    /**
     * @param array $options
     * @param HttpClientInterface|null $httpClient
     * @param PasteEncryptorInterface|null $encryptor
     */
    public function __construct(
        array $options = [],
        ?HttpClientInterface $httpClient = null,
        ?PasteEncryptorInterface $encryptor = null
    ) {
        $this->options = array_merge($this->options, $options);
        $this->httpClient = $httpClient ?? new CurlHttpClient();
        $this->encryptor = $encryptor ?? new AesEncryptor();
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
        if ($this->options["text"] === "" && $this->options["attachment"] === null) {
            throw new PrivatebinException("Empty paste. Use setAttachment() or setText() before post.");
        }

        if ($this->options["burn"] && $this->options["discussion"]) {
            throw new PrivatebinException("Burn and discussion cannot both be enabled.");
        }

        return $this->encryptor->encrypt(
            $this->getPasteData(),
            $this->options["compression"],
            $this->options["formatter"],
            $this->options["discussion"],
            $this->options["burn"],
            $this->options["expire"],
            $this->options["password"],
            $this->options["debug"]
        );
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

        $body = json_encode($data["data"], JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new PrivatebinException("Unable to encode post data.");
        }

        $response = $this->httpClient->request(
            $this->options["url"],
            "POST",
            ["Content-Type: application/json", "X-Requested-With: JSONHttpRequest"],
            $this->options["ssl_verify"],
            $body
        );

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
     * @param string $pasteId
     * @param string $deleteToken
     * @return array
     * @throws PrivatebinException
     */
    public function delete(string $pasteId, string $deleteToken): array
    {
        $body = json_encode(["pasteid" => $pasteId, "deletetoken" => $deleteToken], JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new PrivatebinException("Unable to encode delete data.");
        }

        $response = $this->httpClient->request(
            $this->options["url"],
            "DELETE",
            ["Content-Type: application/json", "X-Requested-With: JSONHttpRequest"],
            $this->options["ssl_verify"],
            $body
        );

        $result = json_decode($response);

        if (!is_object($result)) {
            throw new PrivatebinException("Invalid server response: " . $response);
        }

        if (isset($result->status) && $result->status !== 0) {
            throw new PrivatebinException("Server error: " . ($result->message ?? "Unknown error"), 0, null, $result->status, $result);
        }

        return ["id" => $pasteId];
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
        $url = rtrim($this->options["url"], "/") . "/?" . urlencode($id);

        $response = $this->httpClient->request(
            $url,
            "GET",
            ["X-Requested-With: JSONHttpRequest"],
            $this->options["ssl_verify"]
        );

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
        return $this->encryptor->decrypt($fetchResult, $b58, $password);
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
