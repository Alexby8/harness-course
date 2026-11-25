<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Guides\GuideContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property ?string $seo_title
 * @property string $description
 * @property ?string $card_description
 * @property string $category
 * @property string $topics
 * @property string $reading_time
 * @property Carbon $published_at
 * @property bool $is_published
 * @property int $sort_order
 * @property array<string, mixed> $intro
 * @property array<string, mixed> $preamble
 * @property list<array{id: string, label: string, content: array<string, mixed>}> $sections
 * @property ?Carbon $content_updated_at
 */
final class Guide extends Model
{
    public const array FIELDS = [
        'slug', 'title', 'seo_title', 'description', 'card_description',
        'category', 'topics', 'reading_time', 'published_at', 'is_published',
        'sort_order', 'intro', 'preamble', 'sections',
    ];

    protected $fillable = self::FIELDS;

    protected function casts(): array
    {
        return [
            'published_at' => 'date', 'content_updated_at' => 'datetime',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'intro' => 'array', 'preamble' => 'array', 'sections' => 'array',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (self $guide): void {
            if ($guide->exists && $guide->isDirty('slug')) {
                throw ValidationException::withMessages(['slug' => 'Адрес существующей статьи менять нельзя.']);
            }
            $data = app(GuideContent::class)->validate($guide->export(), $guide->exists ? $guide->id : null);
            $guide->fill($data);
            if (! $guide->exists || $guide->isDirty(['title', 'seo_title', 'description', 'intro', 'preamble', 'sections'])) {
                $guide->content_updated_at = now();
            }
        });
    }

    /** @param Builder<Guide> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function path(): string
    {
        return '/guides/'.$this->slug;
    }

    public function url(): string
    {
        return url($this->path());
    }

    /** @return array<string, mixed> */
    public function export(): array
    {
        $data = $this->only(self::FIELDS);
        $data['published_at'] = $this->published_at->format('Y-m-d');

        return $data;
    }

    /** @return array<string, string> */
    public function metadata(): array
    {
        $date = $this->published_at->copy();
        $date->locale('ru');

        return [
            'title' => $this->title, 'seo_title' => $this->seo_title ?: $this->title,
            'description' => $this->description, 'path' => $this->path(),
            'category' => $this->category, 'topics' => $this->topics,
            'date' => $this->published_at->format('Y-m-d'),
            'display_date' => $date->translatedFormat('j F Y'),
            'reading_time' => $this->reading_time,
        ];
    }
}
