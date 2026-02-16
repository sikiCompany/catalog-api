<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search products using Elasticsearch with fallback.
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

            $results = $this->searchService->search($params);

            return response()->json([
                'success' => true,
                'data' => new ProductCollection($results)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Unexpected error in SearchController', [
                'params' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}