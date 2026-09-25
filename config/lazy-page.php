<?php

use Step2dev\LazyPage\Services\PageUrlGenerator;

return [
    'tables' => [
        'pages' => 'lazy_pages',
        'translations' => 'lazy_page_translations',
    ],

    'route' => [
        'enabled' => false,
        'prefix' => 'page',
        'name' => 'lazy-page.show',
        'middleware' => ['web'],
    ],

    'templates' => [
        'default' => 'lazy-page::show',
    ],

    'default_template' => 'default',

    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'lazy-page',
    ],

    'url_generator' => PageUrlGenerator::class,

    'legacy' => [
        'pages_table' => 'pages',
        'translations_table' => 'page_translations',
    ],
];
