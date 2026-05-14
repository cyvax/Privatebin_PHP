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

use Cyvax\Contracts\PrivatebinSnakeCaseClientInterface;
use Cyvax\Exceptions\PrivatebinException;
use Cyvax\Internal\PrivatebinPHPBase;

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
class PrivatebinSnakeCase extends PrivatebinPHPBase implements PrivatebinSnakeCaseClientInterface
{
    /**
     * @param string $url
     * @return PrivatebinSnakeCase
     */
    public function set_url(string $url): PrivatebinSnakeCase
    {
        return $this->setUrl($url);
    }

    /**
     * @param string $text
     * @return PrivatebinSnakeCase
     */
    public function set_text(string $text): PrivatebinSnakeCase
    {
        return $this->setText($text);
    }

    /**
     * @param string $password
     * @return PrivatebinSnakeCase
     */
    public function set_password(string $password): PrivatebinSnakeCase
    {
        return $this->setPassword($password);
    }

    /**
     * @param string $expire
     * @param bool $bypass
     * @return PrivatebinSnakeCase
     * @throws PrivatebinException
     */
    public function set_expire(string $expire, bool $bypass = false): PrivatebinSnakeCase
    {
        return $this->setExpire($expire, $bypass);
    }

    /**
     * @param string $formatter
     * @param bool $bypass
     * @return PrivatebinSnakeCase
     * @throws PrivatebinException
     */
    public function set_formatter(string $formatter, bool $bypass = false): PrivatebinSnakeCase
    {
        return $this->setFormatter($formatter, $bypass);
    }

    /**
     * @param string $compression
     * @return PrivatebinSnakeCase
     * @throws PrivatebinException
     */
    public function set_compression(string $compression): PrivatebinSnakeCase
    {
        return $this->setCompression($compression);
    }

    /**
     * @param bool $discussion
     * @return PrivatebinSnakeCase
     */
    public function set_discussion(bool $discussion): PrivatebinSnakeCase
    {
        return $this->setDiscussion($discussion);
    }

    /**
     * @param bool $burn
     * @return PrivatebinSnakeCase
     */
    public function set_burn(bool $burn): PrivatebinSnakeCase
    {
        return $this->setBurn($burn);
    }

    /**
     * @param string $file_location
     * @param string|null $filename
     * @return PrivatebinSnakeCase
     */
    public function set_attachment(string $file_location, ?string $filename = null): PrivatebinSnakeCase
    {
        return $this->setAttachment($file_location, $filename);
    }

    /**
     * @param bool $debug
     * @return PrivatebinSnakeCase
     */
    public function set_debug(bool $debug): PrivatebinSnakeCase
    {
        return $this->setDebug($debug);
    }

    /**
     * @param bool $ssl_verify
     * @return PrivatebinSnakeCase
     */
    public function set_ssl_verify(bool $ssl_verify): PrivatebinSnakeCase
    {
        return $this->setSslVerify($ssl_verify);
    }

    /**
     * @return array
     * @throws PrivatebinException
     */
    public function encode_and_post(): array
    {
        return $this->encodeAndPost();
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
        return $this->fetchAndDecrypt($id, $b58, $password);
    }
}
