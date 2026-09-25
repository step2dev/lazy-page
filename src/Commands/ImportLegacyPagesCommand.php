<?php

namespace Step2dev\LazyPage\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Step2dev\LazyPage\Enums\PageStatus;

class ImportLegacyPagesCommand extends Command
{
    protected $signature = 'lazy-page:import
        {--force : Update existing target records instead of requiring empty target tables}';

    protected $description = 'Import legacy pages and page translations into Lazy Page';

    public function handle(): int
    {
        $sourcePages = (string) config('lazy-page.legacy.pages_table', 'pages');
        $sourceTranslations = (string) config('lazy-page.legacy.translations_table', 'page_translations');
        $targetPages = (string) config('lazy-page.tables.pages', 'lazy_pages');
        $targetTranslations = (string) config('lazy-page.tables.translations', 'lazy_page_translations');

        if (! Schema::hasTable($sourcePages) || ! Schema::hasTable($sourceTranslations)) {
            $this->error('Legacy page tables were not found.');

            return self::FAILURE;
        }

        if (! Schema::hasTable($targetPages) || ! Schema::hasTable($targetTranslations)) {
            $this->error('Lazy Page tables were not found. Run migrations first.');

            return self::FAILURE;
        }

        if (! $this->option('force') && DB::table($targetPages)->exists()) {
            $this->error('Target page table is not empty. Use --force to update existing rows.');

            return self::FAILURE;
        }

        $pages = DB::table($sourcePages)->orderBy('id')->get();
        $translations = DB::table($sourceTranslations)->orderBy('id')->get();

        DB::transaction(function () use (
            $pages,
            $translations,
            $targetPages,
            $targetTranslations,
        ): void {
            foreach ($pages as $page) {
                $createdAt = $page->created_at ?? now();
                $updatedAt = $page->updated_at ?? $createdAt;

                DB::table($targetPages)->updateOrInsert(
                    ['id' => $page->id],
                    [
                        'parent_id' => null,
                        'key' => null,
                        'slug' => (string) $page->slug,
                        'status' => (bool) ($page->published ?? false)
                            ? PageStatus::Published->value
                            : PageStatus::Draft->value,
                        'template' => null,
                        'original_locale' => $page->original_locale ?? null,
                        'published_at' => null,
                        'expires_at' => null,
                        'position' => 0,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                        'deleted_at' => null,
                    ],
                );
            }

            foreach ($translations as $translation) {
                DB::table($targetTranslations)->updateOrInsert(
                    [
                        'page_id' => $translation->page_id,
                        'locale' => (string) $translation->locale,
                    ],
                    [
                        'title' => $translation->title ?? null,
                        'description' => $translation->description ?? null,
                        'content' => $translation->content ?? null,
                        'created_at' => $translation->created_at ?? now(),
                        'updated_at' => $translation->updated_at ?? ($translation->created_at ?? now()),
                    ],
                );
            }
        });

        $this->info(sprintf(
            'Imported %d pages and %d translations.',
            $pages->count(),
            $translations->count(),
        ));

        return self::SUCCESS;
    }
}
