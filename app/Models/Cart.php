<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Cart extends Model
{
    /** Session key holding the random token that identifies a visitor's cart before they sign in. */
    public const GUEST_KEY = 'guest_cart_token';

    protected $fillable = ['user_id', 'session_id', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * The active cart of whoever is making this request: the signed-in customer's, otherwise the guest cart
     * tied to a random token kept in this visitor's session (never the session id, which changes at sign-in).
     */
    public static function current(bool $create = false): ?self
    {
        if ($userId = auth()->id()) {
            return $create
                ? static::firstOrCreate(['user_id' => $userId, 'status' => 'active'])
                : static::where('user_id', $userId)->where('status', 'active')->first();
        }

        $token = session(self::GUEST_KEY);
        if (! $token) {
            if (! $create) {
                return null;
            }
            session([self::GUEST_KEY => $token = Str::random(40)]);
        }

        $cart = static::whereNull('user_id')->where('session_id', $token)->where('status', 'active')->first();

        return $cart ?? ($create ? static::create(['session_id' => $token, 'status' => 'active']) : null);
    }

    /**
     * After sign-in or sign-up: move what the visitor put in their guest cart into their own cart.
     * Quantities are added together and capped at the stock on hand.
     */
    public static function adoptGuestCart(User $user): void
    {
        $token = session(self::GUEST_KEY);
        if (! $token) {
            return;
        }
        session()->forget(self::GUEST_KEY);

        if (! $user->hasRole('customer')) {
            return;
        }

        DB::transaction(function () use ($token, $user) {
            $guest = static::whereNull('user_id')->where('session_id', $token)->where('status', 'active')->lockForUpdate()->first();
            if (! $guest) {
                return;
            }

            $own = static::firstOrCreate(['user_id' => $user->id, 'status' => 'active']);
            foreach ($guest->items()->with(['product', 'variant'])->get() as $item) {
                if (! $item->product) {
                    continue;
                }
                $existing = $own->items()->where('product_id', $item->product_id)
                    ->when($item->variant_id, fn ($q) => $q->where('variant_id', $item->variant_id), fn ($q) => $q->whereNull('variant_id'))->first();
                $quantity = min(($existing->quantity ?? 0) + $item->quantity, max(1, $item->availableStock()));

                // The merged quantity can reach a bigger bulk discount, so price it at that quantity.
                $item->quantity = $quantity;
                if ($existing) {
                    $existing->update(['quantity' => $quantity, 'unit_price' => $item->currentUnitPrice()]);
                } else {
                    $own->items()->create(['product_id' => $item->product_id, 'variant_id' => $item->variant_id, 'quantity' => $quantity, 'unit_price' => $item->currentUnitPrice()]);
                }
            }

            $guest->items()->delete();
            $guest->delete();
        });
    }
}
