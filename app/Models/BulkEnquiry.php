<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkEnquiry extends Model
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'quoted' => 'Quoted', 'closed' => 'Closed'];

    protected $fillable = ['name', 'phone', 'email', 'whatsapp', 'other_phone', 'product_type', 'category_ids', 'subcategory_ids', 'product_name', 'quantity', 'message', 'status', 'admin_note', 'ip'];

    protected $casts = ['category_ids' => 'array', 'subcategory_ids' => 'array'];

    /** Names of the chosen categories and subcategories, for the team to read. */
    public function categoryNames(): string
    {
        $ids = array_merge($this->category_ids ?? [], $this->subcategory_ids ?? []);

        return $ids ? Category::whereIn('id', $ids)->orderBy('name')->pluck('name')->implode(', ') : '—';
    }
}
