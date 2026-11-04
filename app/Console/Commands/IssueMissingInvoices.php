<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class IssueMissingInvoices extends Command
{
    protected $signature = 'invoices:backfill';

    protected $description = 'Issue invoice numbers, oldest order first, for shipped/delivered orders that have none';

    public function handle(): int
    {
        $count = 0;
        Order::whereIn('status', ['shipped', 'delivered'])->whereNull('invoice_number')->orderBy('id')
            ->each(function (Order $order) use (&$count) {
                $order->issueInvoice();
                $count++;
            });

        $this->info("Issued {$count} invoice(s).");

        return self::SUCCESS;
    }
}
