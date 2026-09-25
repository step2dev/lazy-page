<?php

namespace Step2dev\LazyPage\Events;

use Step2dev\LazyPage\Models\Page;

class PageCreated
{
    public function __construct(public readonly Page $page) {}
}
