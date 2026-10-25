<?php

declare(strict_types=1);

namespace App\Services\Qr;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class LabelSheetService
{
    public function __construct(
        private readonly QrGeneratorService $qrGenerator,
        private readonly QrPayloadService $payloadService,
    ) {}

    /**
     * Generate PDF stream for batch labels.
     *
     * @param  list<array{type: string, sku: string, name: string, price?: int, batch_no?: string, expired_at?: string, warehouse_code?: string, loc_code?: string, copies: int}>  $items
     */
    public function generatePdf(array $items, string $template = 'a4_3x8'): Response
    {
        $templateConfig = config("erp.qr.labels.{$template}", config('erp.qr.labels.a4_3x8'));

        $labelData = [];
        foreach ($items as $item) {
            $copies = max(1, $item['copies']);

            $payloadRaw = match ($item['type']) {
                'P' => $this->payloadService->encode('P', ['sku' => $item['sku']]),
                'B' => $this->payloadService->encode('B', ['sku' => $item['sku'], 'batch_no' => $item['batch_no'] ?? '']),
                'L' => $this->payloadService->encode('L', ['warehouse_code' => $item['warehouse_code'] ?? '', 'loc_code' => $item['loc_code'] ?? '']),
                default => $this->payloadService->encode('P', ['sku' => $item['sku']]),
            };

            $qrDataUri = $this->qrGenerator->dataUri($payloadRaw, (int) ($templateConfig['qr_mm'] * 4));

            for ($i = 0; $i < $copies; $i++) {
                $labelData[] = [
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'price' => $item['price'] ?? null,
                    'batch_no' => $item['batch_no'] ?? null,
                    'expired_at' => $item['expired_at'] ?? null,
                    'loc_code' => $item['loc_code'] ?? null,
                    'qr_data_uri' => $qrDataUri,
                ];
            }
        }

        $pdf = Pdf::loadView('inventory.qr.pdf-labels', [
            'labels' => $labelData,
            'config' => $templateConfig,
            'template' => $template,
        ]);

        if (is_array($templateConfig['page'])) {
            // Thermal paper: [width_mm, height_mm] in points (1 mm = 2.83465 pt)
            $wPt = $templateConfig['page'][0] * 2.83465;
            $hPt = $templateConfig['page'][1] * 2.83465;
            $pdf->setPaper([0, 0, $wPt, $hPt], 'portrait');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        /** @var Response $response */
        $response = $pdf->stream('labels-'.date('Ymd-His').'.pdf');

        return $response;
    }
}
