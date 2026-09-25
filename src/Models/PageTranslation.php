<?php

namespace Step2dev\LazyPage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    protected $fillable = [
        'locale',
        'title',
        'description',
        'content',
    ];

    public function getTable(): string
    {
        return (string) config('lazy-page.tables.translations', 'lazy_page_translations');
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
