<?php

namespace App\Services;

use App\Models\Product;
use App\Traits\Cacheable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class SearchService
{
    use Cacheable;

    /**
     * Search products based on parameters
     */
    public function search(array $params): LengthAwarePaginator
    {
        if ($this->shouldBypassCache($params)) {
            return $this->performSearch($params);
        }

        $cacheKey = $this->getListCacheKey($params);
        
        // Cache for 60-120 seconds to prevent stampede
        $ttl = now()->addSeconds(rand(60, 120));

        return $this->remember($cacheKey, $ttl, function () use ($params) {
            return $this->performSearch($params);
        });
    }

    /**
     * Perform the actual search using Elasticsearch (via Scout)
     */
    protected function performSearch(array $params): LengthAwarePaginator
    {
        try {
            $query = Product::search($params['q'] ?? '');

            if (!empty($params['category'])) {
                $query->where('category', $params['category']);
            }

            if (!empty($params['status'])) {
                $query->where('status', $params['status']);
            }

            // Fix for range queries in Scout/Elasticsearch
            if (!empty($params['min_price']) || !empty($params['max_price'])) {
                $query->where('price', function ($q) use ($params) {
                    $range = [];
                    if (!empty($params['min_price'])) {
                        $range['gte'] = (float) $params['min_price'];
                    }
                    if (!empty($params['max_price'])) {
                        $range['lte'] = (float) $params['max_price'];
                    }
                    return $range;
                });
            }

            if (!empty($params['sort'])) {
                $order = $params['order'] ?? 'asc';
                $query->orderBy($params['sort'], $order);
            }

            $perPage = $params['per_page'] ?? 15;
            
            return $query->paginate($perPage);

        } catch (\Exception $e) {
            Log::error('Elasticsearch failed, falling back to database', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);

            return $this->performFallbackSearch($params);
        }
    }

    /**
     * Fallback search using MySQL
     */
    protected function performFallbackSearch(array $params): LengthAwarePaginator
    {
        $query = Product::query();

        if (!empty($params['q'])) {
            $query->where(function ($q) use ($params) {
                $q->where('name', 'like', '%' . $params['q'] . '%')
                  ->orWhere('description', 'like', '%' . $params['q'] . '%')
                  ->orWhere('sku', 'like', '%' . $params['q'] . '%');
            });
        }

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['min_price'])) {
            $query->where('price', '>=', $params['min_price']);
        }

        if (!empty($params['max_price'])) {
            $query->where('price', '<=', $params['max_price']);
        }

        $sortField = $params['sort'] ?? 'created_at';
        $sortOrder = $params['order'] ?? 'desc';
        
        $query->orderBy($sortField, $sortOrder);

        $perPage = $params['per_page'] ?? 15;
        
        return $query->paginate($perPage);
    }
}
