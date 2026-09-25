<?php

namespace Step2dev\LazyPage\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Step2dev\LazyPage\Enums\PageStatus;
use Step2dev\LazyPage\Models\Page;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        if (! is_string($title)) {
            $title = implode(' ', $title);
        }

        return [
            'key' => null,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'status' => PageStatus::Draft,
            'template' => null,
            'original_locale' => app()->getLocale(),
            'published_at' => null,
            'expires_at' => null,
            'position' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'status' => PageStatus::Scheduled,
            'published_at' => now()->addDay(),
        ]);
    }
}
