<x-app-layout>
    <x-slot name="title">Pengaturan Sistem ERP</x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Pengaturan Sistem & Bisnis</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Konfigurasi profil perusahaan, tarif pajak PPN, basis komisi, dan kebijakan persediaan.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Profil Perusahaan -->
            <x-ui.card title="Profil Toko & Perusahaan" description="Informasi yang dicetak pada faktur penjualan, tanda terima barang, dan laporan">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="company_name" value="Nama Perusahaan / Toko *" />
                        <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full text-sm" :value="old('company_name', $settings['company_name'])" required />
                        <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="company_phone" value="Telepon / WhatsApp" />
                        <x-text-input id="company_phone" name="company_phone" type="text" class="mt-1 block w-full text-sm" :value="old('company_phone', $settings['company_phone'])" />
                    </div>

                    <div>
                        <x-input-label for="company_npwp" value="Nomor NPWP" />
                        <x-text-input id="company_npwp" name="company_npwp" type="text" class="mt-1 block w-full text-sm font-mono" :value="old('company_npwp', $settings['company_npwp'])" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="company_address" value="Alamat Kantor Pusat" />
                        <textarea id="company_address" name="company_address" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">{{ old('company_address', $settings['company_address']) }}</textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="bank_account" value="Informasi Rekening Bank Pembayaran (Invoice)" />
                        <x-text-input id="bank_account" name="bank_account" type="text" class="mt-1 block w-full text-sm" :value="old('bank_account', $settings['bank_account'])" placeholder="Contoh: BCA 123-456-7890 a.n. Toko Jam Indah" />
                    </div>
                </div>
            </x-ui.card>

            <!-- Section 2: Kebijakan Operasional ERP -->
            <x-ui.card title="Kebijakan Operasional ERP" description="Parameter hitungan perpajakan, komisi, dan sistem persediaan">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tax_rate_bp" value="Tarif PPN (Basis Points) *" />
                        <x-text-input id="tax_rate_bp" name="tax_rate_bp" type="number" min="0" max="5000" class="mt-1 block w-full text-sm font-mono" :value="old('tax_rate_bp', $settings['tax_rate_bp'])" required />
                        <p class="text-[11px] text-gray-400 mt-1">1100 = 11.00% (100 bp = 1%). Berlaku default pada invoice.</p>
                    </div>

                    <div>
                        <x-input-label for="commission_basis" value="Basis Pengakuan Komisi Sales *" />
                        <select id="commission_basis" name="commission_basis" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="payment_received" @selected(old('commission_basis', $settings['commission_basis']) === 'payment_received')>
                                Cash Basis (Saat Pembayaran Diterima)
                            </option>
                            <option value="invoice_posted" @selected(old('commission_basis', $settings['commission_basis']) === 'invoice_posted')>
                                Accrual Basis (Saat Invoice Diposting)
                            </option>
                        </select>
                        <p class="text-[11px] text-gray-400 mt-1">Cash basis mengakui komisi proporsional terhadap pembayaran pelanggan.</p>
                    </div>

                    <div>
                        <x-input-label for="valuation_method" value="Metode Valuasi Persediaan (HPP) *" />
                        <select id="valuation_method" name="valuation_method" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="moving_average" @selected(old('valuation_method', $settings['valuation_method']) === 'moving_average')>
                                Moving Average (Rata-rata Bergerak)
                            </option>
                            <option value="fifo" @selected(old('valuation_method', $settings['valuation_method']) === 'fifo')>
                                FIFO (First In, First Out)
                            </option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="doc_number_format" value="Format Pola Nomor Dokumen *" />
                        <x-text-input id="doc_number_format" name="doc_number_format" type="text" class="mt-1 block w-full text-sm font-mono" :value="old('doc_number_format', $settings['doc_number_format'])" required />
                        <p class="text-[11px] text-gray-400 mt-1">Tag: {PREFIX}, {YYYY}, {MM}, {SEQ:4}</p>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/60">
                    <label class="inline-flex items-center text-xs text-gray-700 dark:text-gray-300 font-medium">
                        <input type="checkbox" name="allow_negative_stock" value="1" @checked(old('allow_negative_stock', $settings['allow_negative_stock'])) class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500 mr-2">
                        Izinkan Kuantitas Stok Minus (Tidak disarankan: Standar audit ERP menolak stok minus)
                    </label>
                </div>
            </x-ui.card>

            <div class="flex items-center justify-end space-x-3">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold shadow-md shadow-primary-500/20 transition">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
