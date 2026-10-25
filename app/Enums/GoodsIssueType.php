<?php

declare(strict_types=1);

namespace App\Enums;

enum GoodsIssueType: string
{
    case SALE = 'sale';
    case TRANSFER = 'transfer';
    case ADJUSTMENT = 'adjustment';
    case DAMAGED = 'damaged';
    case RETURN_SUPPLIER = 'return_supplier';

    public function label(): string
    {
        return match ($this) {
            self::SALE => 'Penjualan',
            self::TRANSFER => 'Transfer Gudang',
            self::ADJUSTMENT => 'Penyesuaian (Opname)',
            self::DAMAGED => 'Barang Rusak / Hilang',
            self::RETURN_SUPPLIER => 'Retur ke Supplier',
        };
    }
}
