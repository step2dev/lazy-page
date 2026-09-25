<?php

namespace Step2dev\LazyPage\Services;

use Step2dev\LazyPage\Models\Page;

class LazyPageManager
{
    public function __construct(
        private readonly PageResolver $resolver,
        private readonly PageCache $cache,
    ) {}

    public function resolve(string $path, bool $publishedOnly = true): Page
    {
        return $this->resolver->resolve($path, $publishedOnly);
    }

    public function findByKey(string $key, bool $publishedOnly = true): Page
    {
        return $this->resolver->findByKey($key, $publishedOnly);
    }

    public function flushCache(): void
    {
        $this->cache->flush();
    }
}
