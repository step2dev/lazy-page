# Lazy Page

[![Latest Version on Packagist](https://img.shields.io/packagist/v/step2dev/lazy-page.svg?style=flat-square)](https://packagist.org/packages/step2dev/lazy-page)
[![GitHub Tests Action Status](https://github.com/step2dev/lazy-page/actions/workflows/run-tests.yml/badge.svg)](https://github.com/step2dev/lazy-page/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/step2dev/lazy-page/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/step2dev/lazy-page/actions?query=workflow%3A%22Code+style%22+branch%3Amain)
[![PHPStan](https://github.com/step2dev/lazy-page/actions/workflows/phpstan.yml/badge.svg)](https://github.com/step2dev/lazy-page/actions/workflows/phpstan.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/step2dev/lazy-page.svg?style=flat-square)](https://packagist.org/packages/step2dev/lazy-page)

Lazy Page is a reusable Laravel CMS page core. It owns page data, translations, publication state, hierarchy, routing, caching and domain events while staying independent from any admin panel, frontend theme, SEO engine or localization router.

## Requirements

- PHP 8.4+
- Laravel 11, 12 or 13
- Astrotomic Laravel Translatable 11.17+

## Installation

~~~bash
composer require step2dev/lazy-page
php artisan vendor:publish --tag=lazy-page-config
php artisan vendor:publish --tag=lazy-page-migrations
php artisan migrate
~~~

Public package routes are disabled by default. After publishing the config, enable them only when the application wants Lazy Page to own page routing:

~~~php
'route' => [
    'enabled' => true,
    'prefix' => 'page',
    'name' => 'lazy-page.show',
    'middleware' => ['web'],
],
~~~

## Data model

A page contains application-wide data:

- parent
- stable optional key
- shared slug
- status
- optional template
- original locale
- publication and expiration timestamps
- position
- soft deletes

Translated content is stored separately:

- locale
- title
- description
- content

The slug is intentionally shared between locales. A localized application can expose the same page as:

~~~text
/privacy-policy
/en/privacy-policy
/pl/privacy-policy
/ru/privacy-policy
~~~

while translating only page content.

## Localization

Lazy Page does not depend on mcamara/laravel-localization. It uses Laravel's active locale, so existing locale middleware and language switchers keep working.

~~~php
app()->setLocale('uk');

$page = LazyPage::resolve('privacy-policy');

echo $page->title;
~~~

This matches the Step2Dev setup: Mcamara may continue to manage locale prefixes and switching while Astrotomic handles model translations.

## Creating pages

~~~php
use Step2dev\LazyPage\Enums\PageStatus;
use Step2dev\LazyPage\Models\Page;

$page = Page::create([
    'key' => 'privacy-policy',
    'slug' => 'privacy-policy',
    'status' => PageStatus::Published,
    'original_locale' => 'uk',
]);

$page->translateOrNew('uk')->fill([
    'title' => 'Політика конфіденційності',
    'content' => '<p>...</p>',
]);

$page->translateOrNew('en')->fill([
    'title' => 'Privacy Policy',
    'content' => '<p>...</p>',
]);

$page->save();
~~~

## Stable keys

Application code should prefer a stable key over a slug when it needs a semantic page:

~~~php
$page = LazyPage::findByKey('privacy-policy');
~~~

The URL slug can change without changing application code.

## Publishing workflow

Supported states:

~~~text
draft
published
scheduled
archived
~~~

A page is publicly resolvable only when its state and publication window allow it.

~~~php
Page::published()->get();
Page::draft()->get();
Page::scheduled()->get();
Page::archived()->get();
~~~

published_at delays visibility and expires_at can automatically stop public resolution.

## Hierarchy

Pages may have parents:

~~~php
$docs = Page::create([
    'slug' => 'docs',
    'status' => PageStatus::Published,
]);

$install = Page::create([
    'parent_id' => $docs->id,
    'slug' => 'install',
    'status' => PageStatus::Published,
]);

$install->path(); // docs/install
$install->ancestors();
$install->children;
~~~

Slugs are globally unique in the first version. This keeps routing and migration predictable while still allowing hierarchical URLs.

## Resolving pages

~~~php
use Step2dev\LazyPage\Facades\LazyPage;

$page = LazyPage::resolve('docs/install');
$page = LazyPage::findByKey('privacy-policy');
~~~

The resolver validates the full hierarchy path and returns only publicly visible pages by default.

Admin tools may explicitly resolve unpublished content:

~~~php
$page = LazyPage::resolve('draft-page', publishedOnly: false);
~~~

## URLs

~~~php
$url = $page->url();
~~~

The URL generator is replaceable through the container. Applications using Mcamara or another localization router can bind their own implementation of:

~~~text
Step2dev\LazyPage\Contracts\PageUrlGeneratorContract
~~~

## Templates

Templates are application-owned and configured by key:

~~~php
'templates' => [
    'default' => 'web.page.show',
    'landing' => 'web.page.landing',
],
~~~

Lazy Page ships only a minimal fallback view.

## Cache

Page resolution is cached by locale and path. Changes to pages or translations invalidate the namespace automatically.

~~~php
'cache' => [
    'enabled' => true,
    'ttl' => 3600,
    'prefix' => 'lazy-page',
];

LazyPage::flushCache();
~~~

## Events

Lazy Page emits domain events that other packages may consume without hard dependencies:

- PageCreated
- PageUpdated
- PagePublished
- PageUnpublished
- PageDeleted
- PageRestored
- PageSlugChanged
- PageTranslationUpdated

This is the intended integration point for lazy-seo-redirects, lazy-seo-tools and lazy-breadcrumb.

## Lazy Admin

lazy-page does not depend on lazy-admin.

The dependency direction is intentionally:

~~~text
lazy-page
    ↑
lazy-admin
~~~

lazy-admin may provide page CRUD, permissions, locale tabs, preview and publishing UI while Lazy Page remains usable headlessly or with another admin interface.

## Migrating Step2Dev legacy pages

The package includes an importer for the current step2.dev schema:

~~~text
pages
page_translations
~~~

After installing and migrating Lazy Page:

~~~bash
php artisan lazy-page:import
~~~

The importer preserves IDs, slugs, original locale, timestamps and translations, and maps the legacy published boolean to the new publication status.

The command refuses to import into a non-empty target table unless explicitly requested:

~~~bash
php artisan lazy-page:import --force
~~~

Source table names are configurable.

## Extensibility

The core intentionally does not own:

- admin UI
- user permissions
- SEO metadata
- sitemap implementation
- breadcrumb rendering
- menu rendering
- AI translation providers
- media management
- page builder blocks

Those features can integrate through contracts, events and the Page model.

## Quality

~~~bash
composer test
composer analyse
composer format
~~~

CI covers Laravel 11, 12 and 13.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [CrazyBoy49z](https://github.com/CrazyBoy49z)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
