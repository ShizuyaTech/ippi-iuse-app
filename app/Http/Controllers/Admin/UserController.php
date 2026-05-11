<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['roleModel', 'vendor'])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->role_id, fn($q) => $q->where('role_id', $request->role_id))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = Role::orderBy('display_name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles   = Role::orderBy('display_name')->get();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        return view('admin.users.create', compact('roles', 'vendors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'role_id'   => ['nullable', 'exists:roles,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'timezone'  => ['nullable', 'string', 'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'role_id'   => $data['role_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'timezone'  => $data['timezone'] ?? 'Asia/Jakarta',
            'password'  => Hash::make($data['password']),
        ]);

        return redirect()->route('admin.users.index')->with('success', "User \"{$user->name}\" berhasil dibuat.");
    }

    public function edit(User $user)
    {
        $roles   = Role::orderBy('display_name')->get();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $user->load('roleModel');

        return view('admin.users.edit', compact('user', 'roles', 'vendors'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email,' . $user->id],
            'role_id'   => ['nullable', 'exists:roles,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'timezone'  => ['nullable', 'string', 'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura'],
            'password'  => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->fill([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'role_id'   => $data['role_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'timezone'  => $data['timezone'] ?? 'Asia/Jakarta',
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', "User \"{$user->name}\" berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "User \"{$name}\" berhasil dihapus.");
    }

    public function editPermissions(User $user)
    {
        $permissions  = Permission::groupedForForm();
        $rolePerms    = $user->load('roleModel.permissions')->roleModel?->permissions->pluck('id')->toArray() ?? [];
        $userPerms    = $user->userPermissions->pluck('id')->toArray();
        return view('admin.users.permissions', compact('user', 'permissions', 'rolePerms', 'userPerms'));
    }

    public function updatePermissions(Request $request, User $user)
    {
        $data = $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $user->userPermissions()->sync($data['permissions'] ?? []);

        return redirect()->route('admin.users.permissions', $user)
            ->with('success', "Permission untuk \"{$user->name}\" berhasil disimpan.");
    }
}
