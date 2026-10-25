<x-app-layout>
    <x-slot name="title">Tambah Pelanggan / Mitra</x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Tambah Pelanggan Baru</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Data kontak mitra, plafon kredit, dan penugasan sales.</p>
            </div>
            <a href="{{ route('master.customers.index') }}" class="text-xs text-gray-500 hover:text-gray-900 dark:hover:text-white">
                ← Kembali
            </a>
        </div>

        <form method="POST" action="{{ route('master.customers.store') }}" class="space-y-6">
            @csrf

            <x-ui.card title="Data Mitra Toko">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" value="Nama Toko / Perusahaan *" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full text-sm" :value="old('name')" required placeholder="Contoh: Toko Jam Abadi" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="code" value="Kode Pelanggan (Opsional)" />
                        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full text-sm font-mono" :value="old('code')" placeholder="Otomatis jika kosong (CUST-0021)" />
                        <x-input-error :messages="$errors->get('code')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="phone" value="Nomor Telepon / WhatsApp" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full text-sm" :value="old('phone')" placeholder="Contoh: 08123456789" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full text-sm" :value="old('email')" placeholder="toko@example.com" />
                    </div>

                    <div>
                        <x-input-label for="city" value="Kota" />
                        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full text-sm" :value="old('city', 'Jakarta')" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="address" value="Alamat Lengkap" />
                        <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">{{ old('address') }}</textarea>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Termin Pembayaran & Penugasan Sales">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="payment_term_days" value="Termin Pembayaran (Hari) *" />
                        <x-text-input id="payment_term_days" name="payment_term_days" type="number" min="0" class="mt-1 block w-full text-sm" :value="old('payment_term_days', 30)" required />
                        <p class="text-[11px] text-gray-400 mt-1">0 = Pembayaran Tunai / Cash on Delivery.</p>
                    </div>

                    <div>
                        <x-input-label for="credit_limit" value="Limit Kredit (Maksimal Piutang) *" />
                        <div class="mt-1">
                            <x-ui.money-input name="credit_limit" :value="old('credit_limit', 50000000)" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="default_sales_id" value="Sales Representative PIC" />
                        <select id="default_sales_id" name="default_sales_id" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">Pilih Sales PIC...</option>
                            @foreach ($salesProfiles as $sp)
                                <option value="{{ $sp->id }}" @selected(old('default_sales_id') == $sp->id)>
                                    {{ $sp->user->name }} ({{ $sp->employee_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="npwp" value="Nomor NPWP" />
                        <x-text-input id="npwp" name="npwp" type="text" class="mt-1 block w-full text-sm font-mono" :value="old('npwp')" placeholder="01.234.567.8-012.000" />
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60">
                    <label class="inline-flex items-center text-xs text-gray-700 dark:text-gray-300 font-medium">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500 mr-2">
                        Status Pelanggan Aktif
                    </label>
                </div>
            </x-ui.card>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('master.customers.index') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold shadow-md shadow-primary-500/20 transition">
                    Simpan Pelanggan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
