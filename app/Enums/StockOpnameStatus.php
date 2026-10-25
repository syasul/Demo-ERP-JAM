<?php

declare(strict_types=1);

namespace App\Enums;

enum StockOpnameStatus: string
{
    case DRAFT = 'draft';
    case COUNTING = 'counting';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::COUNTING => 'Sedang Dihitung',
            self::SUBMITTED => 'Menunggu Approval',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::COUNTING => 'info',
            self::SUBMITTED => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
