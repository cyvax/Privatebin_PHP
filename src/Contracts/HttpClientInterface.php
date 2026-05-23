<?php

declare(strict_types=1);

namespace Cyvax\Contracts;

use Cyvax\Exceptions\PrivatebinException;

interface HttpClientInterface
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
    public function request(string $url, string $method, array $headers, bool $sslVerify, string $body = ""): string;
}
