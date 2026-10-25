<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::query()->with('defaultSales.user');

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('master.customers.index', compact('customers'));
    }

    public function create(): View
    {
        $salesProfiles = SalesProfile::with('user')->where('is_active', true)->get();

        return view('master.customers.create', compact('salesProfiles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'payment_term_days' => ['required', 'integer', 'min:0'],
            'credit_limit' => ['required', 'integer', 'min:0'],
            'default_sales_id' => ['nullable', 'integer', 'exists:sales_profiles,id'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (empty($validated['code'])) {
            $next = Customer::withTrashed()->count() + 1;
            $validated['code'] = sprintf('CUST-%04d', $next);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Customer::create($validated);

        return redirect()->route('master.customers.index')
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function edit(Customer $customer): View
    {
        $salesProfiles = SalesProfile::with('user')->where('is_active', true)->get();

        return view('master.customers.edit', compact('customer', 'salesProfiles'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('customers', 'code')->ignore($customer->id)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'payment_term_days' => ['required', 'integer', 'min:0'],
            'credit_limit' => ['required', 'integer', 'min:0'],
            'default_sales_id' => ['nullable', 'integer', 'exists:sales_profiles,id'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $customer->update($validated);

        return redirect()->route('master.customers.index')
            ->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('master.customers.index')
            ->with('success', 'Pelanggan berhasil dihapus.');
    }
}
