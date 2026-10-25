<?php

declare(strict_types=1);

use App\Exceptions\InvalidQrException;
use App\Services\Qr\QrGeneratorService;
use App\Services\Qr\QrPayloadService;

beforeEach(function () {
    config()->set('app.key', 'base64:3lMvH8rVq6Uj9kP0t5bF2wA7xC4dE1yZ8mN3sO6pQ0i=');
    $this->payloadService = new QrPayloadService;
});

test('encodes and decodes product QR', function () {
    $encoded = $this->payloadService->encode('P', ['sku' => 'SKU-ROLEX-001']);
    expect($encoded)->toBe('ERP1|P|SKU-ROLEX-001');

    $decoded = $this->payloadService->decode($encoded);
    expect($decoded->type)->toBe('P')
        ->and($decoded->data['sku'])->toBe('SKU-ROLEX-001');
});

test('encodes and decodes batch QR', function () {
    $encoded = $this->payloadService->encode('B', [
        'sku' => 'SKU-SEIKO-002',
        'batch_no' => 'LOT-2026-X',
    ]);
    expect($encoded)->toBe('ERP1|B|SKU-SEIKO-002|LOT-2026-X');

    $decoded = $this->payloadService->decode($encoded);
    expect($decoded->type)->toBe('B')
        ->and($decoded->data['sku'])->toBe('SKU-SEIKO-002')
        ->and($decoded->data['batch_no'])->toBe('LOT-2026-X');
});

test('encodes and decodes location QR', function () {
    $encoded = $this->payloadService->encode('L', [
        'warehouse_code' => 'GUD-UTM',
        'loc_code' => 'A-01-03',
    ]);
    expect($encoded)->toBe('ERP1|L|GUD-UTM|A-01-03');

    $decoded = $this->payloadService->decode($encoded);
    expect($decoded->type)->toBe('L')
        ->and($decoded->data['warehouse_code'])->toBe('GUD-UTM')
        ->and($decoded->data['loc_code'])->toBe('A-01-03');
});

test('encodes and decodes document QR with valid HMAC signature', function () {
    $encoded = $this->payloadService->encode('D', [
        'doc_type' => 'GR',
        'number' => 'GR/2026/10/0001',
    ]);

    expect($encoded)->toStartWith('ERP1|D|GR|GR/2026/10/0001|');

    $decoded = $this->payloadService->decode($encoded);
    expect($decoded->type)->toBe('D')
        ->and($decoded->data['doc_type'])->toBe('GR')
        ->and($decoded->data['number'])->toBe('GR/2026/10/0001');
});

test('Q1: rejecting document QR when signature is tampered by 1 character', function () {
    $encoded = $this->payloadService->encode('D', [
        'doc_type' => 'INV',
        'number' => 'INV/2026/10/0042',
    ]);

    $tampered = substr($encoded, 0, -1).($encoded[strlen($encoded) - 1] === 'X' ? 'Y' : 'X');

    expect(function () use ($tampered) {
        $this->payloadService->decode($tampered);
    })->toThrow(InvalidQrException::class, 'Tanda tangan digital QR Dokumen tidak valid atau telah dimodifikasi.');
});

test('rejects unsupported schema version', function () {
    expect(function () {
        $this->payloadService->decode('ERP2|P|SKU-001');
    })->toThrow(InvalidQrException::class, 'Versi skema QR [ERP2] tidak didukung.');
});

test('handles extra CRLF and leading/trailing whitespace cleanly from hardware scanners', function () {
    $raw = "\r\n  ERP1|P|SEIKO-DIVER-01 \n\r";
    $decoded = $this->payloadService->decode($raw);

    expect($decoded->type)->toBe('P')
        ->and($decoded->data['sku'])->toBe('SEIKO-DIVER-01');
});

test('QrGeneratorService produces valid SVG QR content', function () {
    $generator = new QrGeneratorService;
    $svg = $generator->svg('ERP1|P|TEST-001');

    expect($svg)->toContain('<svg')
        ->and($svg)->toContain('</svg>');

    $uri = $generator->dataUri('ERP1|P|TEST-001');
    expect($uri)->toStartWith('data:image/svg+xml;base64,');
});
