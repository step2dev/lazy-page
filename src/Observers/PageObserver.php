<?php

namespace Step2dev\LazyPage\Observers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Step2dev\LazyPage\Enums\PageStatus;
use Step2dev\LazyPage\Events\PageCreated;
use Step2dev\LazyPage\Events\PageDeleted;
use Step2dev\LazyPage\Events\PagePublished;
use Step2dev\LazyPage\Events\PageRestored;
use Step2dev\LazyPage\Events\PageSlugChanged;
use Step2dev\LazyPage\Events\PageUnpublished;
use Step2dev\LazyPage\Events\PageUpdated;
use Step2dev\LazyPage\Models\Page;
use Step2dev\LazyPage\Services\PageCache;

class PageObserver
{
    public function __construct(
        private readonly PageCache $cache,
        private readonly Dispatcher $events,
    ) {}

    public function created(Page $page): void
    {
        $this->cache->flush();
        $this->events->dispatch(new PageCreated($page));

        if ($page->isPubliclyVisible()) {
            $this->events->dispatch(new PagePublished($page));
        }
    }

    public function updated(Page $page): void
    {
        $wasPubliclyVisible = $this->wasPubliclyVisible($page);

        $this->cache->flush();
        $this->events->dispatch(new PageUpdated($page));

        if ($page->wasChanged('slug')) {
            $this->events->dispatch(new PageSlugChanged(
                $page,
                (string) $page->getRawOriginal('slug'),
                (string) $page->slug,
            ));
        }

        if ($page->wasChanged(['status', 'published_at', 'expires_at'])) {
            if ($page->isPubliclyVisible() && ! $wasPubliclyVisible) {
                $this->events->dispatch(new PagePublished($page));
            } elseif (! $page->isPubliclyVisible() && $wasPubliclyVisible) {
                $this->events->dispatch(new PageUnpublished($page));
            }
        }
    }

    public function deleted(Page $page): void
    {
        $this->cache->flush();
        $this->events->dispatch(new PageDeleted($page));
    }

    public function restored(Page $page): void
    {
        $this->cache->flush();
        $this->events->dispatch(new PageRestored($page));
    }

    private function wasPubliclyVisible(Page $page): bool
    {
        $status = (string) $page->getRawOriginal('status');

        if (! in_array($status, [PageStatus::Published->value, PageStatus::Scheduled->value], true)) {
            return false;
        }

        $publishedAt = $page->getRawOriginal('published_at');
        if ($publishedAt !== null && Carbon::parse($publishedAt)->isFuture()) {
            return false;
        }

        $expiresAt = $page->getRawOriginal('expires_at');

        return $expiresAt === null || Carbon::parse($expiresAt)->isFuture();
    }
}
