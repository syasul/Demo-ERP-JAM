<?php

declare(strict_types=1);

namespace App\Enums;

enum QrMode: string
{
    case IN = 'in';
    case OUT = 'out';
    case OPNAME = 'opname';
    case INFO = 'info';

    public function label(): string
    {
        return match ($this) {
            self::IN => 'Barang Masuk',
            self::OUT => 'Barang Keluar',
            self::OPNAME => 'Stock Opname',
            self::INFO => 'Informasi Produk',
        };
    }
}
