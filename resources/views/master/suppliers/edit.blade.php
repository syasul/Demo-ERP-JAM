<x-app-layout>
    <x-slot name="title">Edit Supplier — {{ $supplier->name }}</x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Edit Supplier</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Perbarui data kontak dan syarat pembayaran distributor.</p>
            </div>
            <a href="{{ route('master.suppliers.index') }}" class="text-xs text-gray-500 hover:text-gray-900 dark:hover:text-white">
                ← Kembali
            </a>
        </div>

        <form method="POST" action="{{ route('master.suppliers.update', $supplier) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-ui.card title="Data Pemasok">
                <div class="space-y-4">
                    <div>
                        <x-input-label for="name" value="Nama Perusahaan Supplier *" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full text-sm" :value="old('name', $supplier->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="code" value="Kode Supplier *" />
                            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full text-sm font-mono" :value="old('code', $supplier->code)" required />
                            <x-input-error :messages="$errors->get('code')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="payment_term_days" value="Termin Pembayaran (Hari) *" />
                            <x-text-input id="payment_term_days" name="payment_term_days" type="number" min="0" class="mt-1 block w-full text-sm" :value="old('payment_term_days', $supplier->payment_term_days)" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="phone" value="Telepon Kantor" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full text-sm" :value="old('phone', $supplier->phone)" />
                        </div>
                        <div>
                            <x-input-label for="email" value="Email Kontak" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full text-sm" :value="old('email', $supplier->email)" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="address" value="Alamat Kantor / Gudang Supplier" />
                        <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">{{ old('address', $supplier->address) }}</textarea>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center text-xs text-gray-700 dark:text-gray-300 font-medium">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active)) class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500 mr-2">
                            Supplier Aktif
                        </label>
                    </div>
                </div>
            </x-ui.card>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('master.suppliers.index') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold shadow-md shadow-primary-500/20 transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
