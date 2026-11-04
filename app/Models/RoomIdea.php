<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomIdea extends Model
{
    use \App\Models\Concerns\CleansRichText;

    /** Columns written in the CKEditor: cleaned on every save. */
    protected array $richText = ['description'];

    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = ['title', 'slug', 'description', 'image', 'room_type', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
