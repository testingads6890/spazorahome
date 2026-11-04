<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;

class CleanupGuestCarts extends Command
{
    protected $signature = 'shop:cleanup-guest-carts {--days=30 : Delete guest carts inactive for at least this many days}';
    protected $description = 'Delete stale guest carts only; registered-customer carts are never touched.';

    public function handle(): int
    {
        $days = max(7, min(365, (int) $this->option('days')));
        $deleted = Cart::query()
            ->whereNull('user_id')
            ->whereIn('status', ['active', 'abandoned'])
            ->where('updated_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Deleted {$deleted} stale guest cart(s) older than {$days} days.");
        return self::SUCCESS;
    }
}
