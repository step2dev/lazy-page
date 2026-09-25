<?php

namespace Step2dev\LazyPage\Events;

use Step2dev\LazyPage\Models\PageTranslation;

class PageTranslationUpdated
{
    public function __construct(public readonly PageTranslation $translation) {}
}
