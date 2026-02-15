<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Traits\Cacheable;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use Elastic\Elasticsearch\ClientBuilder;
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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            try {
                $results = $this->fallbackSearch($params);
                
                return response()->json([
                    'success' => true,
                    'data' => new ProductCollection($results),
                    'warning' => 'Elasticsearch service unavailable. Using database search as fallback. Details: ' . $e->getMessage()
                ]);
                
            } catch (\Exception $fallbackError) {
                Log::error('Fallback database search also failed', [
                    'error' => $fallbackError->getMessage(),
                    'trace' => $fallbackError->getTraceAsString()
                ]);
                
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
        try {
            $builder = ClientBuilder::create()
                ->setHosts([config('elasticsearch.host')]);

            if (config('elasticsearch.user')) {
                $builder->setBasicAuthentication(
                    config('elasticsearch.user'),
                    config('elasticsearch.password')
                );
            }

            $client = $builder->build();
            $indexName = config('elasticsearch.index', 'products');
            
            // Verify index exists
            if (!$client->indices()->exists(['index' => $indexName])) {
                Log::warning('Elasticsearch index does not exist', ['index' => $indexName]);
                throw new \Exception("Elasticsearch index '{$indexName}' does not exist");
            }
            
            $must = [];
            $filter = [];

            // Text search
            if (!empty($params['q'])) {
                $must[] = [
                    'multi_match' => [
                        'query' => $params['q'],
                        'fields' => ['name', 'description', 'sku']
                    ]
                ];
            }

            // Filter by category
            if (!empty($params['category'])) {
                $filter[] = ['term' => ['category.keyword' => $params['category']]];
            }

            // Filter by status
            if (!empty($params['status'])) {
                $filter[] = ['term' => ['status.keyword' => $params['status']]];
            }

            // Price range
            if (!empty($params['min_price'])) {
                $filter[] = ['range' => ['price' => ['gte' => (float) $params['min_price']]]];
            }

            if (!empty($params['max_price'])) {
                $filter[] = ['range' => ['price' => ['lte' => (float) $params['max_price']]]];
            }

            // Build the query
            $body = [
                'query' => [
                    'bool' => [
                        'must' => empty($must) ? [['match_all' => []]] : $must,
                        'filter' => $filter
                    ]
                ],
                'sort' => []
            ];

            // Add sorting
            $sortField = $params['sort'] ?? 'created_at';
            $sortOrder = $params['order'] ?? 'desc';
            
            if ($sortField === 'price') {
                $body['sort'][] = ['price' => $sortOrder];
            } else {
                $body['sort'][] = ['created_at' => $sortOrder];
            }

            // Pagination
            $page = $params['page'] ?? 1;
            $perPage = $params['per_page'] ?? 15;
            $body['from'] = ($page - 1) * $perPage;
            $body['size'] = $perPage;

            $response = $client->search(['index' => $indexName, 'body' => $body]);

            // Extract IDs from Elasticsearch results
            $ids = collect($response['hits']['hits'])->pluck('_id')->toArray();

            if (empty($ids)) {
                return new \Illuminate\Pagination\Paginator(
                    [],
                    $perPage,
                    $page,
                    [
                        'path' => request()->url(),
                        'query' => request()->query(),
                    ]
                );
            }

            // Fetch from database in the same order
            $products = Product::whereIn('id', $ids)
                ->get()
                ->keyBy('id')
                ->map(function ($product) use ($ids) {
                    return $product;
                })
                ->values();

            // Create a paginated collection
            $items = $products->values();
            $total = $response['hits']['total']['value'] ?? 0;

            return new \Illuminate\Pagination\Paginator(
                $items,
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );

        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed', [
                'error' => $e->getMessage(),
                'params' => $params,
                'trace' => $e->getTraceAsString()
            ]);

            // Fallback to database search
            throw $e;
        }
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