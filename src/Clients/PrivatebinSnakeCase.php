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
use Cyvax\Contracts\PrivatebinSnakeCaseClientInterface;
use Cyvax\Exceptions\PrivatebinException;
use Cyvax\Internal\PrivatebinCore;

/**
 * Snake_case PrivateBin client.
 *
 * This class provides the original snake_case API while delegating all
 * implementation logic to PrivatebinPHPBase.
 *
 * Usage:
 *   $result = (new PrivatebinSnakeCase())
 *       ->set_url("https://privatebin.net/")
 *       ->set_text("Hello!")
 *       ->set_expire("1hour")
 *       ->encode_and_post();
 */
class PrivatebinSnakeCase implements PrivatebinSnakeCaseClientInterface
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
     * @param string $url
     * @return PrivatebinSnakeCaseClientInterface
     */
    public function set_url(string $url): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setUrl($url);
        return $this;
    }

    /**
     * @param string $text
     * @return PrivatebinSnakeCaseClientInterface
     */
    public function set_text(string $text): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setText($text);
        return $this;
    }

    /**
     * @param string $password
     * @return PrivatebinSnakeCaseClientInterface
     */
    public function set_password(string $password): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setPassword($password);
        return $this;
    }

    /**
     * @param string $expire
     * @param bool $bypass
     * @return PrivatebinSnakeCaseClientInterface
     * @throws PrivatebinException
     */
    public function set_expire(string $expire, bool $bypass = false): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setExpire($expire, $bypass);
        return $this;
    }

    /**
     * @param string $formatter
     * @param bool $bypass
     * @return PrivatebinSnakeCaseClientInterface
     * @throws PrivatebinException
     */
    public function set_formatter(string $formatter, bool $bypass = false): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setFormatter($formatter, $bypass);
        return $this;
    }

    /**
     * @param string $compression
     * @return PrivatebinSnakeCaseClientInterface
     * @throws PrivatebinException
     */
    public function set_compression(string $compression): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setCompression($compression);
        return $this;
    }

    /**
     * @param bool $discussion
     * @return PrivatebinSnakeCaseClientInterface
     */
    public function set_discussion(bool $discussion): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setDiscussion($discussion);
        return $this;
    }

    /**
     * @param bool $burn
     * @return PrivatebinSnakeCaseClientInterface
     */
    public function set_burn(bool $burn): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setBurn($burn);
        return $this;
    }

    /**
     * @param string $file_location
     * @param string|null $filename
     * @return PrivatebinSnakeCaseClientInterface
     * @throws PrivatebinException
     */
    public function set_attachment(string $file_location, ?string $filename = null): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setAttachment($file_location, $filename);
        return $this;
    }

    /**
     * @param bool $debug
     * @return PrivatebinSnakeCaseClientInterface
     */
    public function set_debug(bool $debug): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setDebug($debug);
        return $this;
    }

    /**
     * @param bool $ssl_verify
     * @return PrivatebinSnakeCaseClientInterface
     */
    public function set_ssl_verify(bool $ssl_verify): PrivatebinSnakeCaseClientInterface
    {
        $this->core->setSslVerify($ssl_verify);
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
    public function encode_and_post(): array
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
     * @return array
     * @throws PrivatebinException
     */
    public function fetch_and_decrypt(string $id, string $b58, ?string $password = null): array
    {
        return $this->core->fetchAndDecrypt($id, $b58, $password);
    }

    /**
     * @param string $paste_id
     * @param string $delete_token
     * @return array
     * @throws PrivatebinException
     */
    public function delete(string $paste_id, string $delete_token): array
    {
        return $this->core->delete($paste_id, $delete_token);
    }
}
