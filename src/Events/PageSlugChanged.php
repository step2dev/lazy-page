<?php

namespace Step2dev\LazyPage\Events;

use Step2dev\LazyPage\Models\Page;

class PageSlugChanged
{
    public function __construct(
        public readonly Page $page,
        public readonly string $oldSlug,
        public readonly string $newSlug,
    ) {}
}
