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

use Closure;
use Cyvax\Contracts\HttpClientInterface;
use Cyvax\Contracts\PasteEncryptorInterface;
use Cyvax\Internal\PrivatebinCore;

/**
 * Pipe-style PrivateBin client.
 *
 * Holds a PrivatebinCore instance internally. Each setter method returns a
 * Closure(PrivatebinPipe): PrivatebinPipe, allowing natural composition with
 * PHP 8.5's |> pipe operator. Terminal operations return result arrays.
 *
 * Usage:
 *   $result = PrivatebinPipe::init("https://privatebin.net/")
 *       |> PrivatebinPipe::text("Hello!")
 *       |> PrivatebinPipe::expire("1hour")
 *       |> PrivatebinPipe::encodeAndPost();
 *
 * Without the pipe operator the closures can still be invoked manually:
 *   $pipe = PrivatebinPipe::init("https://privatebin.net/");
 *   $pipe = PrivatebinPipe::text("Hello!")($pipe);
 *   $result = PrivatebinPipe::encodeAndPost()($pipe);
 *
 * but is discouraged in favor of other clients.
 */
class PrivatebinPipe
{
    /** @var PrivatebinCore */
    private $core;

    private function __construct(PrivatebinCore $core)
    {
        $this->core = $core;
    }

    /**
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
     * @param HttpClientInterface|null $httpClient
     * @param PasteEncryptorInterface|null $encryptor
     * @return PrivatebinPipe
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
        array $options = [],
        ?HttpClientInterface $httpClient = null,
        ?PasteEncryptorInterface $encryptor = null
    ): PrivatebinPipe
    {
        return new self(new PrivatebinCore(array_merge([
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
        ], $options), $httpClient, $encryptor));
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function url(string $url): Closure
    {
        return function (PrivatebinPipe $pipe) use ($url): PrivatebinPipe {
            $pipe->core->setUrl($url);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function text(string $text): Closure
    {
        return function (PrivatebinPipe $pipe) use ($text): PrivatebinPipe {
            $pipe->core->setText($text);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function password(string $password): Closure
    {
        return function (PrivatebinPipe $pipe) use ($password): PrivatebinPipe {
            $pipe->core->setPassword($password);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function expire(string $expire, bool $bypass = false): Closure
    {
        return function (PrivatebinPipe $pipe) use ($expire, $bypass): PrivatebinPipe {
            $pipe->core->setExpire($expire, $bypass);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function formatter(string $formatter, bool $bypass = false): Closure
    {
        return function (PrivatebinPipe $pipe) use ($formatter, $bypass): PrivatebinPipe {
            $pipe->core->setFormatter($formatter, $bypass);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function compression(string $compression): Closure
    {
        return function (PrivatebinPipe $pipe) use ($compression): PrivatebinPipe {
            $pipe->core->setCompression($compression);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function discussion(bool $discussion): Closure
    {
        return function (PrivatebinPipe $pipe) use ($discussion): PrivatebinPipe {
            $pipe->core->setDiscussion($discussion);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function burn(bool $burn): Closure
    {
        return function (PrivatebinPipe $pipe) use ($burn): PrivatebinPipe {
            $pipe->core->setBurn($burn);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function attachment(string $fileLocation, ?string $filename = null): Closure
    {
        return function (PrivatebinPipe $pipe) use ($fileLocation, $filename): PrivatebinPipe {
            $pipe->core->setAttachment($fileLocation, $filename);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function debug(bool $debug): Closure
    {
        return function (PrivatebinPipe $pipe) use ($debug): PrivatebinPipe {
            $pipe->core->setDebug($debug);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /** @return Closure(PrivatebinPipe): PrivatebinPipe */
    public static function sslVerify(bool $sslVerify): Closure
    {
        return function (PrivatebinPipe $pipe) use ($sslVerify): PrivatebinPipe {
            $pipe->core->setSslVerify($sslVerify);
            return new PrivatebinPipe($pipe->core);
        };
    }

    /**
     * Encode and post - use as the final stage of a |> pipe chain.
     * @return Closure(PrivatebinPipe): array
     */
    public static function encode(): Closure
    {
        return function (PrivatebinPipe $pipe): array {
            return $pipe->core->encode();
        };
    }

    /**
     * Encode only - use when you want to call post() separately.
     * @return Closure(PrivatebinPipe): array
     */
    public static function encodeAndPost(): Closure
    {
        return function (PrivatebinPipe $pipe): array {
            return $pipe->core->encodeAndPost();
        };
    }

    /**
     * Fetch a paste's encrypted payload - use as a terminal stage.
     * @return Closure(PrivatebinPipe): array
     */
    public static function fetch(string $id): Closure
    {
        return function (PrivatebinPipe $pipe) use ($id): array {
            return $pipe->core->fetch($id);
        };
    }

    /**
     * Fetch and decrypt a paste - use as a terminal stage.
     * @return Closure(PrivatebinPipe): array
     */
    public static function fetchAndDecrypt(string $id, string $b58, ?string $password = null): Closure
    {
        return function (PrivatebinPipe $pipe) use ($id, $b58, $password): array {
            return $pipe->core->fetchAndDecrypt($id, $b58, $password);
        };
    }

    /**
     * Decrypt a fetched payload - use as a terminal stage.
     * @return Closure(PrivatebinPipe): array
     */
    public static function decrypt(array $fetchResult, string $b58, ?string $password = null): Closure
    {
        return function (PrivatebinPipe $pipe) use ($fetchResult, $b58, $password): array {
            return $pipe->core->decrypt($fetchResult, $b58, $password);
        };
    }

    /**
     * Delete a paste - use as a terminal stage.
     * @return Closure(PrivatebinPipe): array
     */
    public static function delete(string $pasteId, string $deleteToken): Closure
    {
        return function (PrivatebinPipe $pipe) use ($pasteId, $deleteToken): array {
            return $pipe->core->delete($pasteId, $deleteToken);
        };
    }
}
