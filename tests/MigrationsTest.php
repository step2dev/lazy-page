<?php

use Illuminate\Support\Facades\Schema;

it('loads package migrations through artisan migrate', function (): void {
    Schema::dropIfExists('lazy_page_translations');
    Schema::dropIfExists('lazy_pages');

    $this->artisan('migrate:fresh')->assertSuccessful();

    expect(Schema::hasTable('lazy_pages'))->toBeTrue()
        ->and(Schema::hasTable('lazy_page_translations'))->toBeTrue();
});
