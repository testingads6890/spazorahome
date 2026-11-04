<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, MustVerifyEmailTrait;

    protected $fillable = [
        'name', 'email', 'phone', 'phone_verified_at', 'password', 'is_guest', 'account_type', 'company_name', 'designation',
        'professional_description', 'license_number', 'website',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_guest' => 'boolean',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    /** Staff membership of somebody else's shop (a user works for at most one). */
    public function vendorMembership(): HasOne
    {
        return $this->hasOne(VendorUser::class);
    }

    /** The shop this user works in: the one they own, otherwise the one they are staff of. */
    public function vendorAccount(): ?Vendor
    {
        return $this->vendor ?? $this->vendorMembership?->vendor;
    }

    public function isVendorOwner(): bool
    {
        return $this->vendor !== null;
    }

    /**
     * May this user use an area of the seller panel? Areas: products, orders, payouts, and "owner" for
     * staff management and the business profile. Owners can do everything, managers everything except
     * the owner-only areas, staff only the areas ticked for them.
     */
    public function vendorCan(string $area): bool
    {
        if ($this->isVendorOwner()) {
            return true;
        }

        $membership = $this->vendorMembership;
        if (! $membership || $area === 'owner' || ! in_array($area, VendorUser::AREAS, true)) {
            return false;
        }

        return $membership->role === 'manager' || in_array($area, $membership->permissions ?? [], true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function professionalProfile(): HasOne
    {
        return $this->hasOne(ProfessionalProfile::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    public function ticketMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function callRecords(): HasMany
    {
        return $this->hasMany(TicketCallRecord::class);
    }
}
