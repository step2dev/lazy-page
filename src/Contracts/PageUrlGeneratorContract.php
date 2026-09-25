<?php

namespace Step2dev\LazyPage\Contracts;

use Step2dev\LazyPage\Models\Page;

interface PageUrlGeneratorContract
{
    public function url(Page $page): string;
}
