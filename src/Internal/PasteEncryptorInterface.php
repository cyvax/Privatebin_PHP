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

use Cyvax\Exceptions\PrivatebinException;

interface PasteEncryptorInterface
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
        array $pasteData,
        string $compression,
        string $formatter,
        bool $discussion,
        bool $burn,
        string $expire,
        ?string $userPassword = null,
        bool $debug = false
    ): array;

    /**
     * @param array $fetchResult
     * @param string $b58
     * @param string|null $userPassword
     * @return array{paste: string, attachment: string|null, attachment_name: string|null}
     * @throws PrivatebinException
     */
    public function decrypt(array $fetchResult, string $b58, ?string $userPassword = null): array;
}