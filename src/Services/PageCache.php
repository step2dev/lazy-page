<?php

namespace Step2dev\LazyPage\Services;

use Closure;
use Illuminate\Contracts\Cache\Repository;

class PageCache
{
    public function __construct(private readonly Repository $cache) {}

    public function remember(string $path, Closure $callback): mixed
    {
        if (! (bool) config('lazy-page.cache.enabled', true)) {
            return $callback();
        }

        $key = $this->key($path);
        $cached = $this->cache->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();

        $this->cache->put(
            $key,
            $value,
            (int) config('lazy-page.cache.ttl', 3600),
        );

        return $value;
    }

    public function flush(): void
    {
        $this->cache->forever($this->versionKey(), $this->version() + 1);
    }

    private function key(string $path): string
    {
        return implode(':', [
            $this->prefix(),
            'page',
            (string) $this->version(),
            app()->getLocale(),
            trim($path, '/'),
        ]);
    }

    private function version(): int
    {
        return (int) $this->cache->get($this->versionKey(), 1);
    }

    private function versionKey(): string
    {
        return $this->prefix().':version';
    }

    private function prefix(): string
    {
        return trim((string) config('lazy-page.cache.prefix', 'lazy-page'), ':');
    }
}
