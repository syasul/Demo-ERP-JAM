<?php

declare(strict_types=1);

namespace App\Enums;

enum StockTransferStatus: string
{
    case DRAFT = 'draft';
    case IN_TRANSIT = 'in_transit';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_TRANSIT => 'Dalam Pengiriman',
            self::RECEIVED => 'Diterima',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::IN_TRANSIT => 'warning',
            self::RECEIVED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
