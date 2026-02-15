<?php

namespace App\Services;

use App\Models\Product;
use App\Services\StorageService;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    /**
     * List all products with filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Product::query();

        // Apply filters
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('sku', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Apply sorting
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        $query->orderBy($sortField, $sortOrder);

        // Include trashed if requested
        if (!empty($filters['with_trashed']) && $filters['with_trashed'] === 'true') {
            $query->withTrashed();
        }

        // Paginate
        $perPage = $filters['per_page'] ?? 15;
        
        return $query->paginate($perPage);
    }

    /**
     * Create new product
     */
    public function create(StoreProductRequest $request): Product
    {
        return DB::transaction(function () use ($request) {
            $product = Product::create($request->validated());
            
            Log::info('Product created', ['id' => $product->id, 'sku' => $product->sku]);
            
            return $product;
        });
    }

    /**
     * Update existing product
     */
    public function update(UpdateProductRequest $request, Product $product): Product
    {
        return DB::transaction(function () use ($request, $product) {
            $product->update($request->validated());
            
            Log::info('Product updated', ['id' => $product->id, 'sku' => $product->sku]);
            
            return $product->fresh();
        });
    }

    /**
     * Delete product (soft delete)
     */
    public function delete(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $result = $product->delete();
            
            if ($result) {
                Log::info('Product deleted', ['id' => $product->id, 'sku' => $product->sku]);
            }
            
            return $result;
        });
    }

    /**
     * Restore soft-deleted product
     */
    public function restore(string $id): ?Product
    {
        return DB::transaction(function () use ($id) {
            $product = Product::withTrashed()->findOrFail($id);
            $product->restore();
            
            Log::info('Product restored', ['id' => $product->id, 'sku' => $product->sku]);
            
            return $product;
        });
    }

    /**
     * Upload image for product (AWS S3)
     */
    public function uploadImage(Product $product, $image): ?string
    {
        try {
            // Use StorageService for upload with fallback
            $storageService = app(StorageService::class);
            
            $result = $storageService->upload(
                $image,
                'products',
                config('filesystems.default')
            );
            
            if (!$result['success']) {
                throw new \Exception('Failed to upload image');
            }
            
            // Update product with image URL
            $product->update([
                'image_url' => $result['url']
            ]);
            
            Log::info('Product image uploaded', [
                'id' => $product->id,
                'path' => $result['path'],
                'disk' => $result['disk']
            ]);
            
            return $product->image_url;
            
        } catch (\Exception $e) {
            Log::error('Failed to upload product image', [
                'id' => $product->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
