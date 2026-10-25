<?php

declare(strict_types=1);

namespace App\Services\Qr;

use App\DTO\QrPayload;
use App\Exceptions\InvalidQrException;

final class QrPayloadService
{
    public const CURRENT_VERSION = 'ERP1';

    /**
     * Encode a QR payload into a raw string according to ERP specification.
     *
     * @param  array<string, mixed>  $data
     */
    public function encode(string $type, array $data): string
    {
        $version = self::CURRENT_VERSION;

        return match ($type) {
            'P' => "{$version}|P|".($data['sku'] ?? ''),
            'B' => "{$version}|B|".($data['sku'] ?? '').'|'.($data['batch_no'] ?? ''),
            'L' => "{$version}|L|".($data['warehouse_code'] ?? '').'|'.($data['loc_code'] ?? ''),
            'D' => $this->encodeDocument($data['doc_type'] ?? '', $data['number'] ?? ''),
            default => throw new InvalidQrException('unknown_type', "Tipe QR tidak dikenal: {$type}"),
        };
    }

    /**
     * Decode a raw QR string into a structured QrPayload DTO.
     *
     * @throws InvalidQrException
     */
    public function decode(string $raw): QrPayload
    {
        $cleaned = trim($raw);

        if ($cleaned === '') {
            throw new InvalidQrException('format', 'QR payload kosong.');
        }

        $parts = explode('|', $cleaned);

        if (count($parts) < 2) {
            throw new InvalidQrException('format', 'Format QR tidak valid. Minimal memiliki prefix versi dan tipe.');
        }

        $version = $parts[0];
        if ($version !== self::CURRENT_VERSION) {
            throw new InvalidQrException('version', "Versi skema QR [{$version}] tidak didukung. Perbarui label Anda.");
        }

        $type = $parts[1];

        return match ($type) {
            'P' => $this->decodeProduct($parts, $cleaned),
            'B' => $this->decodeBatch($parts, $cleaned),
            'L' => $this->decodeLocation($parts, $cleaned),
            'D' => $this->decodeDocument($parts, $cleaned),
            default => throw new InvalidQrException('unknown_type', "Tipe payload QR [{$type}] tidak dikenal."),
        };
    }

    /**
     * Generate document signature.
     */
    public function signDocument(string $docType, string $number): string
    {
        $appKey = (string) config('app.key');
        $payloadToSign = self::CURRENT_VERSION."|D|{$docType}|{$number}";

        $rawHmac = hash_hmac('sha256', $payloadToSign, $appKey, true);
        $base64Url = rtrim(strtr(base64_encode($rawHmac), '+/', '-_'), '=');

        return substr($base64Url, 0, 16);
    }

    private function encodeDocument(string $docType, string $number): string
    {
        $sig = $this->signDocument($docType, $number);

        return self::CURRENT_VERSION."|D|{$docType}|{$number}|{$sig}";
    }

    /**
     * @param  list<string>  $parts
     */
    private function decodeProduct(array $parts, string $raw): QrPayload
    {
        if (count($parts) < 3 || trim($parts[2]) === '') {
            throw new InvalidQrException('format', 'Format QR Produk harus: ERP1|P|{SKU}');
        }

        return new QrPayload(
            version: $parts[0],
            type: 'P',
            data: ['sku' => trim($parts[2])],
            raw: $raw
        );
    }

    /**
     * @param  list<string>  $parts
     */
    private function decodeBatch(array $parts, string $raw): QrPayload
    {
        if (count($parts) < 4 || trim($parts[2]) === '' || trim($parts[3]) === '') {
            throw new InvalidQrException('format', 'Format QR Batch harus: ERP1|B|{SKU}|{BATCH_NO}');
        }

        return new QrPayload(
            version: $parts[0],
            type: 'B',
            data: [
                'sku' => trim($parts[2]),
                'batch_no' => trim($parts[3]),
            ],
            raw: $raw
        );
    }

    /**
     * @param  list<string>  $parts
     */
    private function decodeLocation(array $parts, string $raw): QrPayload
    {
        if (count($parts) < 4 || trim($parts[2]) === '' || trim($parts[3]) === '') {
            throw new InvalidQrException('format', 'Format QR Lokasi harus: ERP1|L|{WH_CODE}|{LOC_CODE}');
        }

        return new QrPayload(
            version: $parts[0],
            type: 'L',
            data: [
                'warehouse_code' => trim($parts[2]),
                'loc_code' => trim($parts[3]),
            ],
            raw: $raw
        );
    }

    /**
     * @param  list<string>  $parts
     */
    private function decodeDocument(array $parts, string $raw): QrPayload
    {
        if (count($parts) < 5 || trim($parts[2]) === '' || trim($parts[3]) === '' || trim($parts[4]) === '') {
            throw new InvalidQrException('format', 'Format QR Dokumen harus: ERP1|D|{DOC_TYPE}|{NUMBER}|{SIGNATURE}');
        }

        $docType = trim($parts[2]);
        $number = trim($parts[3]);
        $providedSig = trim($parts[4]);

        $expectedSig = $this->signDocument($docType, $number);

        if (! hash_equals($expectedSig, $providedSig)) {
            throw new InvalidQrException('signature', 'Tanda tangan digital QR Dokumen tidak valid atau telah dimodifikasi.');
        }

        return new QrPayload(
            version: $parts[0],
            type: 'D',
            data: [
                'doc_type' => $docType,
                'number' => $number,
            ],
            raw: $raw,
            signature: $providedSig
        );
    }
}
