<?php

declare(strict_types=1);

namespace App\Enums;

enum GoodsIssueStatus: string
{
    case DRAFT = 'draft';
    case PACKED = 'packed';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PACKED => 'Dikemas (Packed)',
            self::SHIPPED => 'Dikirim (Shipped)',
            self::DELIVERED => 'Terkirim (Delivered)',
            self::VOID => 'Void',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PACKED => 'info',
            self::SHIPPED => 'warning',
            self::DELIVERED => 'success',
            self::VOID => 'danger',
        };
    }
}
