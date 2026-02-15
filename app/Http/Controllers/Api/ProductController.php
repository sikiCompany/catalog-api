<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Traits\Cacheable;
use App\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    use Cacheable;

    protected ProductService $productService;

    /**
     * Constructor
     */
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of products.
     * 
     * @GET /api/products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->validate([
                'category' => 'sometimes|string',
                'status' => 'sometimes|in:active,inactive',
                'min_price' => 'sometimes|numeric|min:0',
                'max_price' => 'sometimes|numeric|min:0',
                'search' => 'sometimes|string|max:100',
                'sort_by' => 'sometimes|in:price,created_at,name',
                'sort_order' => 'sometimes|in:asc,desc',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
                'with_trashed' => 'sometimes|in:true,false'
            ]);

            if ($this->shouldBypassCache($filters)) {
                $products = $this->productService->list($filters);
                return response()->json(new ProductCollection($products));
            }


            $cacheKey = $this->getListCacheKey($filters);
            $ttl = now()->addSeconds(rand(60, 120)); 

            $products = $this->remember($cacheKey, $ttl, function () use ($filters) {
                return $this->productService->list($filters);
            });

            return response()->json(new ProductCollection($products));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error listing products', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao listar produtos',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created product.
     * 
     * @POST /api/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->create($request);

            Cache::tags(['products'])->flush();

            return response()->json([
                'success' => true,
                'message' => 'Produto criado com sucesso',
                'data' => new ProductResource($product)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating product', [
                'data' => $request->validated(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar produto',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified product.
     * 
     * @GET /api/products/{id}
     */
    public function show(string $id): JsonResponse
    {
        try {

            $cacheKey = $this->getProductCacheKey($id);
            $ttl = now()->addSeconds(rand(60, 120));

            $product = $this->remember($cacheKey, $ttl, function () use ($id) {
                return Product::withTrashed()->find($id);
            });

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new ProductResource($product)
            ]);

        } catch (\Exception $e) {
            Log::error('Error showing product', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar produto',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified product.
     * 
     * @PUT /api/products/{id}
     * @PATCH /api/products/{id}
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        try {
            $product = $this->productService->update($request, $product);

            $this->clearProductCache($product->id);

            return response()->json([
                'success' => true,
                'message' => 'Produto atualizado com sucesso',
                'data' => new ProductResource($product)
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating product', [
                'id' => $product->id,
                'data' => $request->validated(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar produto',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified product (soft delete).
     * 
     * @DELETE /api/products/{id}
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $deleted = $this->productService->delete($product);

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível excluir o produto'
                ], 400);
            }

            $this->clearProductCache($product->id);

            return response()->json([
                'success' => true,
                'message' => 'Produto excluído com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting product', [
                'id' => $product->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir produto',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Restore soft-deleted product.
     * 
     * @POST /api/products/{id}/restore
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $product = $this->productService->restore($id);

            $this->clearProductCache($id);

            return response()->json([
                'success' => true,
                'message' => 'Produto restaurado com sucesso',
                'data' => new ProductResource($product)
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error restoring product', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao restaurar produto',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Upload image for product (AWS S3 diferencial).
     * 
     * @POST /api/products/{id}/image
     */
    public function uploadImage(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            $product = Product::withTrashed()->findOrFail($id);

            $imageUrl = $this->productService->uploadImage($product, $request->file('image'));

            $this->clearProductCache($id);

            return response()->json([
                'success' => true,
                'message' => 'Imagem enviada com sucesso',
                'data' => [
                    'image_url' => $imageUrl
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação da imagem',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error uploading product image', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar imagem',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}