<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Database\Eloquent\Model;

final readonly class TransferChange
{
    public function __construct(
        public int $productId,
        public int $fromWarehouseId,
        public int $toWarehouseId,
        public float $qty,
        public ?int $fromLocationId = null,
        public ?int $toLocationId = null,
        public ?int $batchId = null,
        public ?Model $reference = null,
        public ?string $note = null,
        public ?int $qrScanId = null,
        public ?int $userId = null,
    ) {}
}
