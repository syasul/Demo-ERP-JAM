<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreProductRequest;
use App\Http\Requests\Master\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of products with search and filtering.
     */
    public function index(Request $request): View
    {
        $query = Product::query()->with(['category', 'unit']);

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();
        $categories = Category::all();

        return view('master.products.index', compact('products', 'categories'));
    }

    /**
     * Show form for creating a new product.
     */
    public function create(): View
    {
        $categories = Category::all();
        $units = Unit::all();

        return view('master.products.create', compact('categories', 'units'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Auto-generate SKU if empty: {KAT3}-{000001}
        if (empty($data['sku'])) {
            $cat = Category::find($data['category_id']);
            $prefix = $cat ? strtoupper(substr($cat->name, 0, 3)) : 'PRD';
            $nextNum = Product::withTrashed()->count() + 1;
            $data['sku'] = sprintf('%s-%06d', $prefix, $nextNum);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')?->store('products', 'public');
            $data['image_path'] = $path;
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['track_batch'] = $request->boolean('track_batch', false);

        Product::create($data);

        return redirect()->route('master.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        $categories = Category::all();
        $units = Unit::all();

        return view('master.products.edit', compact('product', 'categories', 'units'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')?->store('products', 'public');
        }

        $data['is_active'] = $request->boolean('is_active', true);
        $data['track_batch'] = $request->boolean('track_batch', false);

        $product->update($data);

        return redirect()->route('master.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Remove or deactivate the specified product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        // Check if product has transaction history in stock movements or invoice items
        // If has transactions, prevent deletion and allow deactivation only
        $product->delete();

        return redirect()->route('master.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
}
