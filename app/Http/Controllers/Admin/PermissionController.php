<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionController extends Controller
{
    public function index()
    {
        // Staff roles the panel understands always exist, so they can be handed out straight away.
        foreach (\App\Support\AdminArea::panelRoles() as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        $users = User::with('roles')->get();

        return view('admin.permissions.index', compact('roles', 'permissions', 'users'));
    }

    public function createRole()
    {
        $permissions = Permission::all();
        return view('admin.permissions.create-role', compact('permissions'));
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create(['name' => $request->name]);
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Role created successfully.');
    }

    public function editRole(Role $role)
    {
        $permissions = Permission::all();
        $role->load('permissions');
        return view('admin.permissions.edit-role', compact('role', 'permissions'));
    }

    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroyRole(Role $role)
    {
        $role->delete();
        return redirect()->route('admin.permissions.index')
            ->with('success', 'Role deleted successfully.');
    }

    public function createPermission()
    {
        return view('admin.permissions.create-permission');
    }

    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        Permission::create(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user->syncRoles($request->roles ?? []);

        return back()->with('success', 'User roles updated successfully.');
    }

    /** Create a staff account (manager or support executive) with one of the panel roles. */
    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'role' => ['required', \Illuminate\Validation\Rule::in(\App\Support\AdminArea::panelRoles())],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
            'account_type' => 'customer',
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => $data['role'], 'guard_name' => 'web']));

        return back()->with('success', "Staff account created for {$user->email}. They sign in at " . route('admin.login') . '.');
    }
}
