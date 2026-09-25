<?php

namespace Step2dev\LazyPage\Observers;

use Illuminate\Contracts\Events\Dispatcher;
use Step2dev\LazyPage\Events\PageTranslationUpdated;
use Step2dev\LazyPage\Models\PageTranslation;
use Step2dev\LazyPage\Services\PageCache;

class PageTranslationObserver
{
    public function __construct(
        private readonly PageCache $cache,
        private readonly Dispatcher $events,
    ) {}

    public function saved(PageTranslation $translation): void
    {
        $this->cache->flush();
        $this->events->dispatch(new PageTranslationUpdated($translation));
    }

    public function deleted(PageTranslation $translation): void
    {
        $this->cache->flush();
        $this->events->dispatch(new PageTranslationUpdated($translation));
    }
}
