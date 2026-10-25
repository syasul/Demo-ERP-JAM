<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly float $requestedQty,
        public readonly float $availableQty,
        string $productName = '',
        ?int $locationId = null
    ) {
        $locText = $locationId ? " pada rak ID {$locationId}" : '';
        $nameText = $productName !== '' ? " ({$productName})" : " ID {$productId}";
        $message = "Stok produk{$nameText} di gudang ID {$warehouseId}{$locText} tidak mencukupi! Diminta: {$requestedQty}, Tersedia: {$availableQty}.";

        parent::__construct($message);
    }
}
