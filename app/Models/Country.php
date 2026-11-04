<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['name', 'iso2', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }
}
