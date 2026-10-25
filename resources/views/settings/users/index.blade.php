<x-app-layout>
    <x-slot name="title">Manajemen Pengguna & Role</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Pengguna & Hak Akses (RBAC)</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kelola staf, sales, gudang, keuangan, dan admin sistem.</p>
            </div>

            @can('user.manage')
                <a href="{{ route('settings.users.create') }}"
                   class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    <span>Tambah Pengguna</span>
                </a>
            @endcan
        </div>

        <x-ui.table :headers="['Nama & Username', 'Email', 'Role / Peran', 'Telepon', 'Login Terakhir', 'Status', 'Aksi']">
            @forelse ($users as $user)
                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                    <td class="px-6 py-4">
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $user->name }}</div>
                        <div class="text-xs font-mono text-gray-400 mt-0.5">{{ '@' . ($user->username ?? '-') }}</div>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                        {{ $user->email }}
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $role = $user->roles->first()?->name ?? 'staff';
                            $badgeVariant = match($role) {
                                'owner'   => 'danger',
                                'admin'   => 'warning',
                                'finance' => 'primary',
                                'gudang'  => 'info',
                                'sales'   => 'success',
                                default   => 'gray',
                            };
                        @endphp
                        <x-ui.badge :variant="$badgeVariant" class="uppercase text-[10px]">
                            {{ $role }}
                        </x-ui.badge>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300 font-mono">
                        {{ $user->phone ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                        @if ($user->last_login_at)
                            <div>{{ $user->last_login_at->translatedFormat('d M Y, H:i') }}</div>
                            <div class="text-[10px] text-gray-400 font-mono">{{ $user->last_login_ip ?? '' }}</div>
                        @else
                            <span class="text-gray-400">Belum pernah login</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if ($user->is_active)
                            <x-ui.badge variant="success" :dot="true">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge variant="danger" :dot="true">Nonaktif</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('settings.users.edit', $user) }}" class="p-1.5 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950/60 transition inline-block">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="p-8 text-center">
                        <x-ui.empty title="Tidak ada data pengguna" />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        <div>
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
