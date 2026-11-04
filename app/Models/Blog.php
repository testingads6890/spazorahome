<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use \App\Models\Concerns\CleansRichText;

    /** Columns written in the CKEditor: cleaned on every save. */
    protected array $richText = ['content'];

    protected $fillable = ['title', 'slug', 'content', 'excerpt', 'featured_image', 'author_name', 'is_published', 'published_at', 'meta_title', 'meta_description'];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    /** Featured image: a full URL, or a path on the public storage disk. */
    public function imageUrl(): ?string
    {
        $img = trim((string) $this->featured_image);
        if ($img === '') {
            return null;
        }

        return preg_match('#^(https?:)?//#i', $img) ? $img : asset('storage/' . ltrim($img, '/'));
    }

    public function summary(int $words = 28): string
    {
        return filled($this->excerpt) ? $this->excerpt : \Illuminate\Support\Str::words(\App\Support\Html::text($this->content), $words);
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->content)) / 200));
    }

    public function dateLabel(): string
    {
        return ($this->published_at ?? $this->created_at)?->translatedFormat('d M Y') ?? '';
    }
}
