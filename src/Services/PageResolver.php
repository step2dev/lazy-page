<?php

namespace Step2dev\LazyPage\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Step2dev\LazyPage\Models\Page;

class PageResolver
{
    public function __construct(private readonly PageCache $cache) {}

    public function resolve(string $path, bool $publishedOnly = true): Page
    {
        $normalizedPath = trim($path, '/');

        if ($normalizedPath === '') {
            throw (new ModelNotFoundException)->setModel(Page::class);
        }

        return $this->cache->remember(
            ($publishedOnly ? 'published:' : 'any:').$normalizedPath,
            fn (): Page => $this->resolveUncached($normalizedPath, $publishedOnly),
        );
    }

    public function findByKey(string $key, bool $publishedOnly = true): Page
    {
        return $this->cache->remember(
            ($publishedOnly ? 'key:published:' : 'key:any:').$key,
            function () use ($key, $publishedOnly): Page {
                $query = Page::query()->with(['translations', 'parent']);

                if ($publishedOnly) {
                    $query->published();
                }

                return $query->byKey($key)->firstOrFail();
            },
        );
    }

    private function resolveUncached(string $path, bool $publishedOnly): Page
    {
        $slug = basename($path);
        $query = Page::query()->with(['translations', 'parent']);

        if ($publishedOnly) {
            $query->published();
        }

        $page = $query->bySlug($slug)->firstOrFail();

        if ($page->path() !== $path) {
            throw (new ModelNotFoundException)->setModel(Page::class, [$path]);
        }

        return $page;
    }
}
