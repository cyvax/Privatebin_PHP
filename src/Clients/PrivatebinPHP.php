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

use Cyvax\Contracts\PrivatebinClientInterface;
use Cyvax\Internal\PrivatebinPHPBase;

/**
 * Modern camelCase PrivateBin client.
 *
 * All core implementation logic lives in PrivatebinPHPBase.
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
class PrivatebinPHP extends PrivatebinPHPBase implements PrivatebinClientInterface
{
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
     * @return PrivatebinPHP
     */
    public static function init(
        string $url = "https://paste.i2pd.xyz/",
        string $text = "",
        ?string $password = null,
        string $expire = "1day",
        string $formatter = "plaintext",
        string $compression = "zlib",
        bool   $discussion = false,
        bool   $burn = false,
        bool   $debug = false,
        bool   $sslVerify = true,
        array  $options = []
    ): PrivatebinPHP
    {
        return new PrivatebinPHP(array_merge([
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
}
