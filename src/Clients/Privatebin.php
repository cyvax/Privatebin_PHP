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

namespace Cyvax\Clients;

use Cyvax\Contracts\HttpClientInterface;
use Cyvax\Contracts\PasteEncryptorInterface;
use Cyvax\Contracts\PrivatebinClientInterface;
use Cyvax\Exceptions\PrivatebinException;
use Cyvax\Internal\PrivatebinCore;

/**
 * camelCase PrivateBin client.
 *
 * All core implementation logic lives in PrivatebinCore.
 *
 * This class provides the primary public API for interacting with
 * PrivateBin instances using camelCase method names.
 *
 * Usage:
 *   $result = PrivatebinPHP::init(url: "https://privatebin.net/")
 *       ->setText("Hello!")
 *       ->setExpire("1hour")
 *       ->encodeAndPost();
 *
 *  Without init():
 *    $result = (new PrivatebinPHP())
 *        ->setUrl("https://privatebin.net/")
 *        ->setText("Hello!")
 *        ->setExpire("1hour")
 *        ->encodeAndPost();
 */
class Privatebin implements PrivatebinClientInterface
{
    /** @var PrivatebinCore */
    private $core;

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
        $this->core = new PrivatebinCore($options, $httpClient, $encryptor);
    }

    /**
     * Create a new instance with named parameters - convenient on PHP 8+.
     *
     * @param string $url
     * @param string $text
     * @param string|null $password
     * @param string $expire
     * @param string $formatter
     * @param string $compression
     * @param bool $discussion
     * @param bool $burn
     * @param bool $debug
     * @param bool $sslVerify
     * @param array $options Any extra options to merge
     * @return Privatebin
     */
    public static function init(
        string $url = "https://paste.i2pd.xyz/",
        string $text = "",
        ?string $password = null,
        string $expire = "1day",
        string $formatter = "plaintext",
        string $compression = "zlib",
        bool $discussion = false,
        bool $burn = false,
        bool $debug = false,
        bool $sslVerify = true,
        array $options = []
    ): PrivatebinClientInterface
    {
        return new Privatebin(array_merge([
            "url" => $url,
            "text" => $text,
            "password" => $password,
            "expire" => $expire,
            "formatter" => $formatter,
            "compression" => $compression,
            "discussion" => $discussion,
            "burn" => $burn,
            "debug" => $debug,
            "ssl_verify" => $sslVerify,
        ], $options));
    }

    /**
     * @param string $url
     * @return Privatebin
     */
    public function setUrl(string $url): PrivatebinClientInterface
    {
        $this->core->setUrl($url);
        return $this;
    }

    /**
     * @param string $text
     * @return Privatebin
     */
    public function setText(string $text): PrivatebinClientInterface
    {
        $this->core->setText($text);
        return $this;
    }

    /**
     * @param string $password
     * @return Privatebin
     */
    public function setPassword(string $password): PrivatebinClientInterface
    {
        $this->core->setPassword($password);
        return $this;
    }

    /**
     * @param string $expire
     * @param bool $bypass
     * @return Privatebin
     * @throws PrivatebinException
     */
    public function setExpire(string $expire, bool $bypass = false): PrivatebinClientInterface
    {
        $this->core->setExpire($expire, $bypass);
        return $this;
    }

    /**
     * @param string $formatter
     * @param bool $bypass Allow values outside FORMATTER_VALUES
     * @return Privatebin
     * @throws PrivatebinException
     */
    public function setFormatter(string $formatter, bool $bypass = false): PrivatebinClientInterface
    {
        $this->core->setFormatter($formatter, $bypass);
        return $this;
    }

    /**
     * @param string $compression "zlib" or "none"
     * @return Privatebin
     * @throws PrivatebinException
     */
    public function setCompression(string $compression): PrivatebinClientInterface
    {
        $this->core->setCompression($compression);
        return $this;
    }

    /**
     * @param bool $discussion
     * @return Privatebin
     */
    public function setDiscussion(bool $discussion): PrivatebinClientInterface
    {
        $this->core->setDiscussion($discussion);
        return $this;
    }

    /**
     * @param bool $burn
     * @return Privatebin
     */
    public function setBurn(bool $burn): PrivatebinClientInterface
    {
        $this->core->setBurn($burn);
        return $this;
    }

    /**
     * @param string $fileLocation
     * @param string|null $filename
     * @return Privatebin
     * @throws PrivatebinException
     */
    public function setAttachment(string $fileLocation, ?string $filename = null): PrivatebinClientInterface
    {
        $this->core->setAttachment($fileLocation, $filename);
        return $this;
    }

    /**
     * @param bool $debug
     * @return Privatebin
     */
    public function setDebug(bool $debug): PrivatebinClientInterface
    {
        $this->core->setDebug($debug);
        return $this;
    }

    /**
     * @param bool $sslVerify
     * @return Privatebin
     */
    public function setSslVerify(bool $sslVerify): PrivatebinClientInterface
    {
        $this->core->setSslVerify($sslVerify);
        return $this;
    }

    /**
     * @return array
     * @throws PrivatebinException
     */
    public function encode(): array
    {
        return $this->core->encode();
    }

    /**
     * @param array $data
     * @return array
     * @throws PrivatebinException
     */
    public function post(array $data): array
    {
        return $this->core->post($data);
    }

    /**
     * @return array
     * @throws PrivatebinException
     */
    public function encodeAndPost(): array
    {
        return $this->core->encodeAndPost();
    }

    /**
     * @param string $id
     * @return array
     * @throws PrivatebinException
     */
    public function fetch(string $id): array
    {
        return $this->core->fetch($id);
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
        return $this->core->decrypt($fetchResult, $b58, $password);
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
        return $this->core->fetchAndDecrypt($id, $b58, $password);
    }

    /**
     * @param string $pasteId
     * @param string $deleteToken
     * @return array
     * @throws PrivatebinException
     */
    public function delete(string $pasteId, string $deleteToken): array
    {
        return $this->core->delete($pasteId, $deleteToken);
    }
}
