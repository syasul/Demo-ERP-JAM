<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = [
            'company_name' => Setting::get('company_name', 'Toko Jam Indah & Elegan'),
            'company_address' => Setting::get('company_address', 'Jl. Thamrin No. 88, Jakarta Pusat'),
            'company_phone' => Setting::get('company_phone', '021-3908888'),
            'company_npwp' => Setting::get('company_npwp', '01.888.999.1-012.000'),
            'bank_account' => Setting::get('bank_account', 'BCA 123-456-7890 a.n. Toko Jam Indah'),
            'tax_rate_bp' => Setting::get('tax_rate_bp', 1100),
            'commission_basis' => Setting::get('commission_basis', 'payment_received'),
            'valuation_method' => Setting::get('valuation_method', 'moving_average'),
            'allow_negative_stock' => Setting::get('allow_negative_stock', false),
            'doc_number_format' => Setting::get('doc_number_format', '{PREFIX}/{YYYY}/{MM}/{SEQ:4}'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_npwp' => ['nullable', 'string', 'max:50'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'tax_rate_bp' => ['required', 'integer', 'min:0', 'max:5000'],
            'commission_basis' => ['required', 'string', 'in:invoice_posted,payment_received'],
            'valuation_method' => ['required', 'string', 'in:moving_average,fifo'],
            'allow_negative_stock' => ['nullable', 'boolean'],
            'doc_number_format' => ['required', 'string', 'max:50'],
        ]);

        Setting::set('company_name', $validated['company_name']);
        Setting::set('company_address', $validated['company_address'] ?? '');
        Setting::set('company_phone', $validated['company_phone'] ?? '');
        Setting::set('company_npwp', $validated['company_npwp'] ?? '');
        Setting::set('bank_account', $validated['bank_account'] ?? '');
        Setting::set('tax_rate_bp', (int) $validated['tax_rate_bp'], 'int');
        Setting::set('commission_basis', $validated['commission_basis']);
        Setting::set('valuation_method', $validated['valuation_method']);
        Setting::set('allow_negative_stock', $request->boolean('allow_negative_stock'), 'bool');
        Setting::set('doc_number_format', $validated['doc_number_format']);

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
