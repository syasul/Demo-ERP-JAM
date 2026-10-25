<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with('roles');

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $roles = Role::all();

        return view('settings.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $roles = Role::all();

        return view('settings.users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Admin cannot assign owner role
        if ($validated['role'] === 'owner' && ! $currentUser->hasRole('owner')) {
            return back()->withErrors(['role' => 'Hanya Owner yang dapat memberikan role Owner.']);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('settings.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        $roles = Role::all();

        return view('settings.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        // AC: user cannot deactivate themselves
        if ($currentUser->id === $user->id && ! $request->boolean('is_active', true)) {
            return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
        }

        // AC: Admin cannot change owner's role
        if ($user->hasRole('owner') && ! $currentUser->hasRole('owner') && $validated['role'] !== 'owner') {
            return back()->withErrors(['role' => 'Admin tidak dapat mengubah role dari Owner.']);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->username = $validated['username'];
        $user->phone = $validated['phone'];
        $user->is_active = $request->boolean('is_active', true);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->syncRoles([$validated['role']]);

        return redirect()->route('settings.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }
}
