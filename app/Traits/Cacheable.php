<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait Cacheable
{
    /**
     * Get cached data or store from callback
     */
    protected function remember(string $key, $ttl, callable $callback)
    {
        try {
            return Cache::tags(['products'])->remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache error', ['key' => $key, 'error' => $e->getMessage()]);
            return $callback();
        }
    }

    /**
     * Generate cache key for product
     */
    protected function getProductCacheKey(int $id): string
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
    protected function clearProductCache(int $id): void
    {
        Cache::forget($this->getProductCacheKey($id));
        Cache::tags(['products'])->flush();
    }

    /**
     * Check if should bypass cache (high page numbers)
     */
    protected function shouldBypassCache(array $params): bool
    {
        return isset($params['page']) && (int) $params['page'] > 50;
    }
}
