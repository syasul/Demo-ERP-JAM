<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;

final readonly class StockChange
{
    public function __construct(
        public int $productId,
        public int $warehouseId,
        public float $qty,
        public int $unitCost = 0,
        public MovementType $type = MovementType::IN_PURCHASE,
        public ?int $locationId = null,
        public ?int $batchId = null,
        public ?Model $reference = null,
        public ?string $note = null,
        public ?int $qrScanId = null,
        public ?int $userId = null,
    ) {}
}
