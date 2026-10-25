<?php

declare(strict_types=1);

namespace App\Enums;

enum MovementType: string
{
    case IN_PURCHASE = 'IN_PURCHASE';
    case IN_RETURN_SALE = 'IN_RETURN_SALE';
    case IN_TRANSFER = 'IN_TRANSFER';
    case IN_ADJUSTMENT = 'IN_ADJUSTMENT';
    case IN_OPENING = 'IN_OPENING';

    case OUT_SALE = 'OUT_SALE';
    case OUT_TRANSFER = 'OUT_TRANSFER';
    case OUT_ADJUSTMENT = 'OUT_ADJUSTMENT';
    case OUT_DAMAGED = 'OUT_DAMAGED';
    case OUT_RETURN_SUPPLIER = 'OUT_RETURN_SUPPLIER';

    public function direction(): string
    {
        return match ($this) {
            self::IN_PURCHASE,
            self::IN_RETURN_SALE,
            self::IN_TRANSFER,
            self::IN_ADJUSTMENT,
            self::IN_OPENING => 'in',

            self::OUT_SALE,
            self::OUT_TRANSFER,
            self::OUT_ADJUSTMENT,
            self::OUT_DAMAGED,
            self::OUT_RETURN_SUPPLIER => 'out',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::IN_PURCHASE => 'Penerimaan Pembelian',
            self::IN_RETURN_SALE => 'Retur Penjualan',
            self::IN_TRANSFER => 'Transfer Masuk',
            self::IN_ADJUSTMENT => 'Penyesuaian Masuk (Opname)',
            self::IN_OPENING => 'Saldo Awal',

            self::OUT_SALE => 'Pengeluaran Penjualan',
            self::OUT_TRANSFER => 'Transfer Keluar',
            self::OUT_ADJUSTMENT => 'Penyesuaian Keluar (Opname)',
            self::OUT_DAMAGED => 'Barang Rusak / Hilang',
            self::OUT_RETURN_SUPPLIER => 'Retur ke Supplier',
        };
    }
}
