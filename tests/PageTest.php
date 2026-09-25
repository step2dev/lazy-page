<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Step2dev\LazyPage\Enums\PageStatus;
use Step2dev\LazyPage\Events\PageSlugChanged;
use Step2dev\LazyPage\Models\Page;
use Step2dev\LazyPage\Services\PageResolver;

it('stores translated page content while keeping one shared slug', function (): void {
    $page = Page::factory()->create([
        'slug' => 'privacy-policy',
        'status' => PageStatus::Published,
    ]);

    $page->translateOrNew('en')->title = 'Privacy Policy';
    $page->translateOrNew('uk')->title = 'Ukrainian Privacy Policy';
    $page->save();

    app()->setLocale('uk');
    $page->refresh();

    expect($page->slug)->toBe('privacy-policy')
        ->and($page->title)->toBe('Ukrainian Privacy Policy');
});

it('resolves published hierarchical pages by path', function (): void {
    $parent = Page::factory()->published()->create(['slug' => 'docs']);
    $child = Page::factory()->published()->create([
        'slug' => 'install',
        'parent_id' => $parent->id,
    ]);

    $resolved = app(PageResolver::class)->resolve('docs/install');

    expect($resolved->is($child))->toBeTrue()
        ->and($resolved->path())->toBe('docs/install');
});

it('does not expose draft or future scheduled pages publicly', function (): void {
    Page::factory()->create([
        'slug' => 'draft-page',
        'status' => PageStatus::Draft,
    ]);

    Page::factory()->scheduled()->create([
        'slug' => 'future-page',
    ]);

    expect(fn () => app(PageResolver::class)->resolve('draft-page'))
        ->toThrow(ModelNotFoundException::class)
        ->and(fn () => app(PageResolver::class)->resolve('future-page'))
        ->toThrow(ModelNotFoundException::class);
});

it('exposes scheduled pages after their publication time', function (): void {
    $page = Page::factory()->create([
        'slug' => 'scheduled-page',
        'status' => PageStatus::Scheduled,
        'published_at' => now()->subMinute(),
    ]);

    expect(app(PageResolver::class)->resolve('scheduled-page')->is($page))->toBeTrue();
});

it('does not expose expired pages', function (): void {
    Page::factory()->published()->create([
        'slug' => 'expired-page',
        'expires_at' => now()->subMinute(),
    ]);

    expect(fn () => app(PageResolver::class)->resolve('expired-page'))
        ->toThrow(ModelNotFoundException::class);
});

it('dispatches a slug changed event', function (): void {
    Event::fake([PageSlugChanged::class]);

    $page = Page::factory()->create(['slug' => 'old-slug']);
    $page->update(['slug' => 'new-slug']);

    Event::assertDispatched(
        PageSlugChanged::class,
        fn (PageSlugChanged $event): bool => $event->oldSlug === 'old-slug'
            && $event->newSlug === 'new-slug'
            && $event->page->is($page)
    );
});

it('supports soft deletion and restoration', function (): void {
    $page = Page::factory()->create();

    $page->delete();

    expect(Page::query()->find($page->id))->toBeNull()
        ->and(Page::withTrashed()->find($page->id))->not->toBeNull();

    $page->restore();

    expect(Page::query()->find($page->id))->not->toBeNull();
});
