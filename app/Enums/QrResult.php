<?php

declare(strict_types=1);

namespace App\Enums;

enum QrResult: string
{
    case OK = 'ok';
    case INVALID = 'invalid';
    case DUPLICATE = 'duplicate';
    case NOT_FOUND = 'not_found';
    case FORBIDDEN = 'forbidden';

    public function label(): string
    {
        return match ($this) {
            self::OK => 'Berhasil',
            self::INVALID => 'Format QR Tidak Valid',
            self::DUPLICATE => 'Duplikat (Diabaikan)',
            self::NOT_FOUND => 'Data Tidak Ditemukan',
            self::FORBIDDEN => 'Akses Ditolak',
        };
    }
}
