<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'customer_profiles';

    protected $fillable = ['user_id', 'phone', 'whatsapp', 'other_phone', 'date_of_birth', 'gender', 'profile_image'];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
