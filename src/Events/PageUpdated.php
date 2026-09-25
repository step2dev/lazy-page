<?php

namespace Step2dev\LazyPage\Events;

use Step2dev\LazyPage\Models\Page;

class PageUpdated
{
    public function __construct(public readonly Page $page) {}
}
