<?php

namespace Step2dev\LazyPage\Events;

use Step2dev\LazyPage\Models\Page;

class PageRestored
{
    public function __construct(public readonly Page $page) {}
}
