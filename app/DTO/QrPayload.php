<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class QrPayload
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $version,
        public string $type, // P, B, L, D
        public array $data,
        public string $raw,
        public ?string $signature = null,
    ) {}
}
