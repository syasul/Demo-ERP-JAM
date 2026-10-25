<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $warehouses = Warehouse::with('locations')->orderBy('name')->get();

        return view('master.warehouses.index', compact('warehouses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Warehouse::create($validated);

        return redirect()->route('master.warehouses.index')
            ->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function storeLocation(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'regex:/^[A-Z0-9]+(-[A-Z0-9]+){0,3}$/',
                Rule::unique('locations', 'code')->where('warehouse_id', $warehouse->id),
            ],
            'name' => ['nullable', 'string', 'max:255'],
        ], [
            'code.regex' => 'Format kode rak harus pola zona-rak-level seperti A-01-03.',
        ]);

        $warehouse->locations()->create($validated);

        return redirect()->route('master.warehouses.index')
            ->with('success', 'Lokasi rak berhasil ditambahkan.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->delete();

        return redirect()->route('master.warehouses.index')
            ->with('success', 'Gudang berhasil dihapus.');
    }
}
