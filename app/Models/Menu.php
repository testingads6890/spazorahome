<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    public const LOCATIONS = ['header' => 'Header (top bar)', 'footer' => 'Footer'];

    protected $fillable = ['name', 'location', 'items', 'is_active'];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Links of every active menu in a location, in the order the menus were created.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public static function linksFor(string $location): array
    {
        return static::where('is_active', true)->where('location', $location)->orderBy('id')->get()
            ->flatMap(fn (self $m) => $m->items ?? [])
            ->filter(fn ($i) => is_array($i) && filled($i['label'] ?? null) && \App\Rules\LinkOrPath::passes($i['url'] ?? null))
            ->values()->all();
    }

    /** The links as the admin types them: one "Label | link" per line. */
    public function linksText(): string
    {
        return collect($this->items ?? [])->map(fn ($i) => ($i['label'] ?? '') . ' | ' . ($i['url'] ?? ''))->implode("\n");
    }
}
