<?php

declare(strict_types=1);

namespace Cyvax\Exceptions;

use Exception;
use Throwable;

class PrivatebinException extends Exception
{
    private $serverStatus;
    private $responseData;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        ?int $serverStatus = null,
        $responseData = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->serverStatus = $serverStatus;
        $this->responseData = $responseData;
    }

    public function getServerStatus(): ?int
    {
        return $this->serverStatus;
    }

    public function getResponseData()
    {
        return $this->responseData;
    }
}

// Backward-compatibility alias.
class_alias(PrivatebinException::class, \Cyvax\PrivatebinException::class);
