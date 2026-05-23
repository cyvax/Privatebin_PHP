<?php

declare(strict_types=1);

namespace Cyvax\Contracts;

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
        array   $pasteData,
        string  $compression,
        string  $formatter,
        bool    $discussion,
        bool    $burn,
        string  $expire,
        ?string $userPassword = null,
        bool    $debug = false
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
