<?php

namespace Step2dev\LazyPage\Http\Controllers;

use Illuminate\Contracts\View\View;
use Step2dev\LazyPage\Services\PageResolver;

class PageController
{
    public function __construct(private readonly PageResolver $resolver) {}

    public function __invoke(string $page): View
    {
        $resolvedPage = $this->resolver->resolve($page);
        $templates = (array) config('lazy-page.templates', []);
        $templateKey = $resolvedPage->template ?: (string) config('lazy-page.default_template', 'default');
        $view = $templates[$templateKey] ?? $templates['default'] ?? 'lazy-page::show';

        return view($view, ['page' => $resolvedPage]);
    }
}
