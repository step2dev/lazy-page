<?php

namespace Step2dev\LazyPage\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Step2dev\LazyPage\Contracts\PageUrlGeneratorContract;
use Step2dev\LazyPage\Database\Factories\PageFactory;
use Step2dev\LazyPage\Enums\PageStatus;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string|null $key
 * @property string $slug
 * @property PageStatus $status
 * @property string|null $template
 * @property string|null $original_locale
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property int $position
 * @property-read Page|null $parent
 * @property-read Collection<int, Page> $children
 */
class Page extends Model implements TranslatableContract
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    use SoftDeletes;
    use Translatable;

    /** @var list<string> */
    public array $translatedAttributes = [
        'title',
        'description',
        'content',
    ];

    protected $fillable = [
        'parent_id',
        'key',
        'slug',
        'status',
        'template',
        'original_locale',
        'published_at',
        'expires_at',
        'position',
    ];

    protected $attributes = [
        'status' => PageStatus::Draft->value,
        'position' => 0,
    ];

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }

    public function getTable(): string
    {
        return (string) config('lazy-page.tables.pages', 'lazy_pages');
    }

    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    /**
     * @return Collection<int, Page>
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection;
        $current = $this->parent;

        while ($current !== null) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    public function path(): string
    {
        return $this->ancestors()
            ->push($this)
            ->pluck('slug')
            ->filter()
            ->implode('/');
    }

    public function url(): string
    {
        return app(PageUrlGeneratorContract::class)->url($this);
    }

    public function isPubliclyVisible(): bool
    {
        $now = now();

        if (in_array($this->status, [PageStatus::Published, PageStatus::Scheduled], true) === false) {
            return false;
        }

        if ($this->published_at !== null && $this->published_at->isFuture()) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isAfter($now);
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [
                PageStatus::Published->value,
                PageStatus::Scheduled->value,
            ])
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Draft->value);
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Scheduled->value);
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Archived->value);
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }
}
