<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Step2dev\LazyPage\Enums\PageStatus;
use Step2dev\LazyPage\Models\Page;

it('imports the legacy step2dev page schema', function (): void {
    Schema::create('pages', function (Blueprint $table): void {
        $table->id();
        $table->string('slug');
        $table->boolean('published')->default(false);
        $table->string('original_locale')->nullable();
        $table->timestamps();
    });

    Schema::create('page_translations', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('page_id');
        $table->string('locale');
        $table->string('title')->nullable();
        $table->text('description')->nullable();
        $table->text('content')->nullable();
        $table->timestamps();
    });

    DB::table('pages')->insert([
        'id' => 10,
        'slug' => 'privacy-policy',
        'published' => true,
        'original_locale' => 'uk',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('page_translations')->insert([
        'page_id' => 10,
        'locale' => 'uk',
        'title' => 'Privacy Policy',
        'description' => null,
        'content' => '<p>Content</p>',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('lazy-page:import')
        ->expectsOutput('Imported 1 pages and 1 translations.')
        ->assertSuccessful();

    $page = Page::query()->findOrFail(10);

    expect($page->slug)->toBe('privacy-policy')
        ->and($page->status)->toBe(PageStatus::Published)
        ->and($page->translate('uk')?->title)->toBe('Privacy Policy');
});
