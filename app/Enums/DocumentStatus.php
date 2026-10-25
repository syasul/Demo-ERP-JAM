<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::POSTED => 'Posted',
            self::VOID => 'Void',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::POSTED => 'success',
            self::VOID => 'danger',
        };
    }
}
