<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Traits\Cacheable;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    use Cacheable;

    /**
     * Search products using Elasticsearch.
     * 
     * @GET /api/search/products
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $params = $request->validate([
                'q' => 'sometimes|string|max:100',
                'category' => 'sometimes|string',
                'min_price' => 'sometimes|numeric|min:0',
                'max_price' => 'sometimes|numeric|min:0',
                'status' => 'sometimes|in:active,inactive',
                'sort' => 'sometimes|in:price,created_at',
                'order' => 'sometimes|in:asc,desc',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100'
            ]);

            if ($this->shouldBypassCache($params)) {
                $results = $this->performSearch($params);
                return response()->json(new ProductCollection($results));
            }

            $cacheKey = 'search_' . md5(json_encode($params));
            $ttl = now()->addSeconds(rand(60, 120));

            $results = $this->remember($cacheKey, $ttl, function () use ($params) {
                return $this->performSearch($params);
            });

            return response()->json(new ProductCollection($results));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error searching products', [
                'params' => $request->all(),
                'error' => $e->getMessage()
            ]);

            try {
                $results = $this->fallbackSearch($params);
                
                return response()->json([
                    'success' => true,
                    'data' => new ProductCollection($results),
                    'warning' => 'Usando busca alternativa devido a problemas no Elasticsearch'
                ]);
                
            } catch (\Exception $fallbackError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao realizar busca',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        }
    }

    /**
     * Perform search using Elasticsearch
     */
    protected function performSearch(array $params)
    {        
        $query = Product::search($params['q'] ?? '');

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['min_price'])) {
            $query->where('price', '>=', (float) $params['min_price']);
        }

        if (!empty($params['max_price'])) {
            $query->where('price', '<=', (float) $params['max_price']);
        }

        if (!empty($params['sort'])) {
            $order = $params['order'] ?? 'asc';
            $query->orderBy($params['sort'], $order);
        }

        $perPage = $params['per_page'] ?? 15;
        
        return $query->paginate($perPage);
    }

    /**
     * Fallback search using MySQL
     */
    protected function fallbackSearch(array $params)
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