<?php

declare(strict_types=1);

return [
    'doc_prefix' => [
        'goods_receipt' => 'GR',
        'goods_issue' => 'GI',
        'transfer' => 'TR',
        'opname' => 'SO',
        'invoice' => 'INV',
        'payment' => 'PAY',
        'credit_note' => 'CN',
        'journal' => 'JV',
        'payout' => 'KOM',
        'expense' => 'EXP',
        'bill' => 'BILL',
    ],
    'doc_number_format' => '{PREFIX}/{YYYY}/{MM}/{SEQ:4}',
    'sequence_reset' => 'monthly', // monthly|yearly
    'qr' => [
        'version' => 'ERP1',
        'error_correction' => 'M',
        'debounce_ms' => 1500,
        'labels' => [
            'a4_3x8' => ['page' => 'A4', 'cols' => 3, 'rows' => 8, 'w_mm' => 63.5, 'h_mm' => 33.9, 'qr_mm' => 22],
            'a4_4x10' => ['page' => 'A4', 'cols' => 4, 'rows' => 10, 'w_mm' => 48.5, 'h_mm' => 25.4, 'qr_mm' => 18],
            'thermal_38x25' => ['page' => [38, 25], 'cols' => 1, 'rows' => 1, 'qr_mm' => 20],
        ],
    ],
    'defaults' => [
        'tax_rate_bp' => (int) env('ERP_TAX_RATE_BP', 1100),
        'commission_basis' => (string) env('ERP_COMMISSION_BASIS', 'payment_received'),
        'valuation_method' => (string) env('ERP_VALUATION_METHOD', 'moving_average'),
        'allow_negative_stock' => (bool) env('ERP_ALLOW_NEGATIVE_STOCK', false),
        'aging_buckets' => [30, 60, 90],
        'dead_stock_days' => 90,
        'expiry_warning_days' => [30, 60, 90],
    ],
    'cache' => [
        'dashboard_ttl' => 300,
    ],
];
