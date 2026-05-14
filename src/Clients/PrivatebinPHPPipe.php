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

/**
 * Pipe-style helper closures for PrivatebinPHP.
 *
 * Usage on PHP versions with pipe support:
 *
 *   $result = PrivatebinPHPPipe::init("https://privatebin.net/")
 *       |> PrivatebinPHPPipe::text("Hello!")
 *       |> PrivatebinPHPPipe::expire("1hour")
 *       |> PrivatebinPHPPipe::encodeAndPost();
 *
 * On PHP versions without pipe support, it is recommended to use either
 * the fluent PrivatebinPHP API directly or the snake_case PrivatebinSnakeCase API.
 *
 * If you still want to use this helper without pipe support, the closures
 * can be invoked manually:
 *
 *   $client = PrivatebinPHPPipe::init("https://privatebin.net/");
 *   $client = PrivatebinPHPPipe::text("Hello!")($client);
 *   $client = PrivatebinPHPPipe::expire("1hour")($client);
 *   $result = PrivatebinPHPPipe::encodeAndPost()($client);
 *
 * Each method except init() returns a Closure that accepts a PrivatebinPHP
 * instance and calls the corresponding setter on it.
 */
class PrivatebinPHPPipe
{
    /**
     * Create a new PrivatebinPHP instance.
     * Use as the first stage of a |> pipe chain.
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
     * @return PrivatebinPHP
     */
    public static function init(
        string  $url = "https://paste.i2pd.xyz/",
        string  $text = "",
        ?string $password = null,
        string  $expire = "1day",
        string  $formatter = "plaintext",
        string  $compression = "zlib",
        bool    $discussion = false,
        bool    $burn = false,
        bool    $debug = false,
        bool    $sslVerify = true,
        array   $options = []
    ): PrivatebinPHP
    {
        return PrivatebinPHP::init(
            $url,
            $text,
            $password,
            $expire,
            $formatter,
            $compression,
            $discussion,
            $burn,
            $debug,
            $sslVerify,
            $options
        );
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function url(string $url): Closure
    {
        return function (PrivatebinPHP $client) use ($url): PrivatebinPHP {
            return $client->setUrl($url);
        };
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function text(string $text): Closure
    {
        return function (PrivatebinPHP $client) use ($text): PrivatebinPHP {
            return $client->setText($text);
        };
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function password(string $password): Closure
    {
        return function (PrivatebinPHP $client) use ($password): PrivatebinPHP {
            return $client->setPassword($password);
        };
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function expire(string $expire, bool $bypass = false): Closure
    {
        return function (PrivatebinPHP $client) use ($expire, $bypass): PrivatebinPHP {
            return $client->setExpire($expire, $bypass);
        };
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function formatter(string $formatter, bool $bypass = false): Closure
    {
        return function (PrivatebinPHP $client) use ($formatter, $bypass): PrivatebinPHP {
            return $client->setFormatter($formatter, $bypass);
        };
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function compression(string $compression): Closure
    {
        return function (PrivatebinPHP $client) use ($compression): PrivatebinPHP {
            return $client->setCompression($compression);
        };
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function discussion(bool $discussion): Closure
    {
        return function (PrivatebinPHP $client) use ($discussion): PrivatebinPHP {
            return $client->setDiscussion($discussion);
        };
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function burn(bool $burn): Closure
    {
        return function (PrivatebinPHP $client) use ($burn): PrivatebinPHP {
            return $client->setBurn($burn);
        };
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function attachment(string $fileLocation, ?string $filename = null): Closure
    {
        return function (PrivatebinPHP $client) use ($fileLocation, $filename): PrivatebinPHP {
            return $client->setAttachment($fileLocation, $filename);
        };
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function debug(bool $debug): Closure
    {
        return function (PrivatebinPHP $client) use ($debug): PrivatebinPHP {
            return $client->setDebug($debug);
        };
    }

    /** @return Closure(PrivatebinPHP): PrivatebinPHP */
    public static function sslVerify(bool $sslVerify): Closure
    {
        return function (PrivatebinPHP $client) use ($sslVerify): PrivatebinPHP {
            return $client->setSslVerify($sslVerify);
        };
    }

    /**
     * Encode and post - use as the final stage of a |> pipe chain.
     * @return Closure(PrivatebinPHP): array
     */
    public static function encodeAndPost(): Closure
    {
        return function (PrivatebinPHP $client): array {
            return $client->encodeAndPost();
        };
    }

    /**
     * Encode only - use when you want to call post() separately.
     * @return Closure(PrivatebinPHP): array
     */
    public static function encode(): Closure
    {
        return function (PrivatebinPHP $client): array {
            return $client->encode();
        };
    }

    /**
     * Fetch a paste's encrypted payload - use as a terminal stage.
     * @return Closure(PrivatebinPHP): array
     */
    public static function fetch(string $id): Closure
    {
        return function (PrivatebinPHP $client) use ($id): array {
            return $client->fetch($id);
        };
    }

    /**
     * Fetch and decrypt a paste - use as a terminal stage.
     * @return Closure(PrivatebinPHP): array
     */
    public static function fetchAndDecrypt(string $id, string $b58, ?string $password = null): Closure
    {
        return function (PrivatebinPHP $client) use ($id, $b58, $password): array {
            return $client->fetchAndDecrypt($id, $b58, $password);
        };
    }

    /**
     * Decrypt a fetched payload - use as a terminal stage.
     * @return Closure(PrivatebinPHP): array
     */
    public static function decrypt(array $fetchResult, string $b58, ?string $password = null): Closure
    {
        return function (PrivatebinPHP $client) use ($fetchResult, $b58, $password): array {
            return $client->decrypt($fetchResult, $b58, $password);
        };
    }
}
