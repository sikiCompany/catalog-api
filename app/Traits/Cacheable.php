<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait Cacheable
{
    protected function remember(string $key, $ttl, callable $callback)
    {
        try {
            $store = Cache::getStore();

            if ($store instanceof \Illuminate\Cache\TaggableStore) {
                return Cache::tags(['products'])->remember($key, $ttl, $callback);
            }

            return Cache::remember($key, $ttl, $callback);

        } catch (\Throwable $e) {
            Log::warning('Cache error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return $callback();
        }
    }


    /**
     * Generate cache key for product
     */
    protected function getProductCacheKey(string $id): string
    {
        return 'product_' . $id;
    }

    /**
     * Generate cache key for product list
     */
    protected function getListCacheKey(array $params): string
    {
        ksort($params);
        $queryString = http_build_query($params);
        return 'products_list_' . md5($queryString);
    }

    /**
     * Clear product cache
     */
    protected function clearProductCache(string $id): void
    {
        Cache::forget($this->getProductCacheKey($id));
        
        // Flush tags only if supported
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['products'])->flush();
        }
    }

    /**
     * Check if should bypass cache (high page numbers)
     */
    protected function shouldBypassCache(array $params): bool
    {
        return isset($params['page']) && (int) $params['page'] > 50;
    }
}
