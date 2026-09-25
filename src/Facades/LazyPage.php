<?php

namespace Step2dev\LazyPage\Facades;

use Illuminate\Support\Facades\Facade;
use Step2dev\LazyPage\Models\Page;
use Step2dev\LazyPage\Services\LazyPageManager;

/**
 * @method static Page resolve(string $path, bool $publishedOnly = true)
 * @method static Page findByKey(string $key, bool $publishedOnly = true)
 * @method static void flushCache()
 *
 * @see LazyPageManager
 */
class LazyPage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LazyPageManager::class;
    }
}
