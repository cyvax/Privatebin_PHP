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

namespace Cyvax\Internal\Http;

use Cyvax\Exceptions\PrivatebinException;

/**
 * @internal
 */
class CurlHttpClient implements HttpClientInterface
{
    /**
     * @param string $url
     * @param string $method
     * @param string[] $headers
     * @param bool $sslVerify
     * @param string $body
     * @return string
     * @throws PrivatebinException
     */
    public function request(string $url, string $method, array $headers, bool $sslVerify, string $body = ""): string
    {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new PrivatebinException("Unable to initialize cURL.");
        }

        if ($method === "POST") {
            curl_setopt($curl, CURLOPT_POST, 1);
        } elseif ($method !== "GET") {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($body !== "") {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $sslVerify ? 1 : 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new PrivatebinException("cURL error: " . $error);
        }

        curl_close($curl);

        return $response;
    }
}