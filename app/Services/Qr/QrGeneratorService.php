<?php

declare(strict_types=1);

namespace App\Services\Qr;

use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

final class QrGeneratorService
{
    /**
     * Generate an SVG QR code string.
     */
    public function svg(string $payload, int $size = 200): string
    {
        $cacheKey = 'qr_svg_'.md5("{$payload}_{$size}");

        return (string) Cache::remember($cacheKey, 86400, function () use ($payload, $size): string {
            return (string) QrCode::format('svg')
                ->size($size)
                ->errorCorrection('M')
                ->generate($payload);
        });
    }

    /**
     * Generate a base64 encoded PNG or SVG data URI for inline embedding in PDF or HTML.
     */
    public function dataUri(string $payload, int $size = 200): string
    {
        $svg = $this->svg($payload, $size);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
