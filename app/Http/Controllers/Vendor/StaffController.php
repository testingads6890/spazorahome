<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * The shop owner adds team members and chooses which parts of the seller panel each one can use.
 * A new email gets its own sign-in; an existing customer account is linked as it is (its password is never touched).
 */
class StaffController extends Controller
{
    public const ROLES = ['manager' => 'Manager (all selling areas)', 'staff' => 'Staff (only the areas ticked below)'];

    public function index(Request $request)
    {
        $vendor = $this->vendor($request);
        $members = $vendor->vendorUsers()->with('user:id,name,email,created_at')->latest('id')->get();

        return view('vendor.staff.index', compact('vendor', 'members') + ['areas' => VendorUser::AREA_LABELS, 'roles' => self::ROLES]);
    }

    public function create(Request $request)
    {
        $this->vendor($request);

        return view('vendor.staff.form', ['member' => new VendorUser(['role' => 'staff', 'permissions' => []]), 'areas' => VendorUser::AREA_LABELS, 'roles' => self::ROLES]);
    }

    public function store(Request $request)
    {
        $vendor = $this->vendor($request);
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
        ] + $this->accessRules());

        $existing = User::whereRaw('lower(email) = ?', [mb_strtolower(trim($data['email']))])->first();
        if ($existing) {
            $why = match (true) {
                $existing->id === $request->user()->id => 'That is your own account.',
                $existing->isVendorOwner() => 'This email belongs to another seller.',
                $existing->hasRole('admin') => 'This email belongs to an administrator and cannot be seller staff.',
                $existing->vendorMembership()->exists() => 'This person already works for a shop.',
                default => null,
            };
            if ($why) {
                throw ValidationException::withMessages(['email' => $why]);
            }
        } else {
            $request->validate([
                'name' => 'required|string|max:255',
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);
        }

        DB::transaction(function () use ($data, $vendor, $existing, $request) {
            $user = $existing ?? User::create([
                'name' => $data['name'],
                'email' => mb_strtolower(trim($data['email'])),
                'password' => Hash::make($request->input('password')),
                'account_type' => 'customer',
            ]);
            // Seller-panel pages need the vendor role; what they can open is limited by the membership below.
            $user->assignRole(Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']));
            VendorUser::create([
                'vendor_id' => $vendor->id,
                'user_id' => $user->id,
                'role' => $data['role'],
                'permissions' => $this->permissions($data),
            ]);
        });

        return redirect()->route('vendor.staff.index')->with('success', $existing
            ? "{$existing->email} can now use your seller panel with their existing password."
            : "{$data['email']} can now sign in at " . route('vendor.login') . ' with the password you set.');
    }

    public function edit(Request $request, VendorUser $staff)
    {
        $this->own($request, $staff);
        $staff->load('user');

        return view('vendor.staff.form', ['member' => $staff, 'areas' => VendorUser::AREA_LABELS, 'roles' => self::ROLES]);
    }

    public function update(Request $request, VendorUser $staff)
    {
        $this->own($request, $staff);
        $data = $request->validate($this->accessRules() + ['name' => 'nullable|string|max:255']);

        $staff->update(['role' => $data['role'], 'permissions' => $this->permissions($data)]);
        if (filled($data['name'] ?? null) && $staff->user) {
            $staff->user->update(['name' => $data['name']]);
        }

        return redirect()->route('vendor.staff.index')->with('success', 'Access updated for ' . ($staff->user?->name ?? 'this person') . '.');
    }

    /** Removes the person from this shop. Their account stays usable as a customer account; nothing is deleted. */
    public function destroy(Request $request, VendorUser $staff)
    {
        $this->own($request, $staff);
        $user = $staff->user;
        $staff->delete();
        if ($user && ! $user->isVendorOwner()) {
            $user->removeRole('vendor');
            if (! $user->hasRole('customer')) {
                $user->assignRole(Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']));
            }
        }

        return redirect()->route('vendor.staff.index')->with('success', ($user?->name ?? 'The staff member') . ' no longer has access to your seller panel.');
    }

    private function accessRules(): array
    {
        return [
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'permissions' => 'nullable|array',
            'permissions.*' => Rule::in(VendorUser::AREAS),
        ];
    }

    /** Managers get every selling area; staff get what was ticked (nothing ticked = dashboard only). */
    private function permissions(array $data): array
    {
        return $data['role'] === 'manager' ? VendorUser::AREAS : array_values(array_intersect(VendorUser::AREAS, $data['permissions'] ?? []));
    }

    private function vendor(Request $request)
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor, 403, 'Only the shop owner can manage staff.');

        return $vendor;
    }

    /** Another shop's staff record is off limits. */
    private function own(Request $request, VendorUser $staff): void
    {
        abort_unless((int) $staff->vendor_id === (int) $this->vendor($request)->id, 403);
    }
}
