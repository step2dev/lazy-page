<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $pages = (string) config('lazy-page.tables.pages', 'lazy_pages');
        $translations = (string) config('lazy-page.tables.translations', 'lazy_page_translations');

        if (! Schema::hasTable($pages)) {
            Schema::create($pages, function (Blueprint $table) use ($pages): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained($pages)->nullOnDelete();
            $table->string('key')->nullable()->unique();
            $table->string('slug', 191)->unique();
            $table->string('status', 32)->default('draft')->index();
            $table->string('template', 100)->nullable();
            $table->string('original_locale', 10)->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable($translations)) {
            Schema::create($translations, function (Blueprint $table) use ($pages): void {
            $table->id();
            $table->foreignId('page_id')->constrained($pages)->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();

                $table->unique(['page_id', 'locale']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists((string) config('lazy-page.tables.translations', 'lazy_page_translations'));
        Schema::dropIfExists((string) config('lazy-page.tables.pages', 'lazy_pages'));
    }
};
