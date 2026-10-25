<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidQrException extends Exception
{
    public function __construct(
        public readonly string $errorType, // format, version, signature, unknown_type
        string $message
    ) {
        parent::__construct($message);
    }
}
