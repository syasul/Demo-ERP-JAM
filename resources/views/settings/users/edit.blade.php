<x-app-layout>
    <x-slot name="title">Edit Pengguna — {{ $user->name }}</x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Edit Akun Pengguna</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Perbarui profil atau role pengguna.</p>
            </div>
            <a href="{{ route('settings.users.index') }}" class="text-xs text-gray-500 hover:text-gray-900 dark:hover:text-white">
                ← Kembali
            </a>
        </div>

        <form method="POST" action="{{ route('settings.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-ui.card title="Data Akun Pengguna">
                <div class="space-y-4">
                    <div>
                        <x-input-label for="name" value="Nama Lengkap *" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full text-sm" :value="old('name', $user->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="username" value="Username *" />
                            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full text-sm font-mono" :value="old('username', $user->username)" required />
                            <x-input-error :messages="$errors->get('username')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="email" value="Email Login *" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full text-sm" :value="old('email', $user->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="phone" value="Nomor Telepon / WhatsApp" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full text-sm" :value="old('phone', $user->phone)" />
                        </div>
                        <div>
                            <x-input-label for="role" value="Role / Peran *" />
                            <select id="role" name="role" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach ($roles as $r)
                                    <option value="{{ $r->name }}" @selected(old('role', $user->roles->first()?->name) === $r->name)>
                                        {{ ucfirst($r->name) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="password" value="Reset Password Baru (Opsional)" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full text-sm font-mono" placeholder="Biarkan kosong jika tidak ingin mengganti password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center text-xs text-gray-700 dark:text-gray-300 font-medium">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500 mr-2">
                            Akun Aktif (Dapat Login ke Sistem)
                        </label>
                        <x-input-error :messages="$errors->get('is_active')" class="mt-1" />
                    </div>
                </div>
            </x-ui.card>

            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('settings.users.index') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold shadow-md shadow-primary-500/20 transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
