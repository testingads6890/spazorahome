<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A request for an export quote from a buyer abroad (usually a bulk or trade order). */
class ExportEnquiry extends Model
{
    public const STATUSES = ['new' => 'New', 'replied' => 'Replied', 'closed' => 'Closed'];

    protected $fillable = ['product_id', 'user_id', 'name', 'email', 'phone', 'company', 'country', 'quantity', 'message', 'status', 'admin_notes'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
