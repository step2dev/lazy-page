<?php

namespace Step2dev\LazyPage\Services;

use Step2dev\LazyPage\Contracts\PageUrlGeneratorContract;
use Step2dev\LazyPage\Models\Page;

class PageUrlGenerator implements PageUrlGeneratorContract
{
    public function url(Page $page): string
    {
        $prefix = trim((string) config('lazy-page.route.prefix', 'page'), '/');
        $path = trim($page->path(), '/');

        return url(implode('/', array_filter([$prefix, $path])));
    }
}
