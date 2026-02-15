<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProductService;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->productService = new ProductService();
    }

    /**
     * Test service can create a product
     */
    public function test_service_can_create_product(): void
    {
        $data = [
            'sku' => 'SERVICE001',
            'name' => 'Service Test Product',
            'description' => 'Created by service',
            'price' => 99.99,
            'category' => 'Test',
            'status' => 'active'
        ];

        $request = StoreProductRequest::create('/api/products', 'POST', $data);
        $request->setContainer(app());
        $request->validateResolved();

        $product = $this->productService->create($request);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('SERVICE001', $product->sku);
        $this->assertEquals('Service Test Product', $product->name);
    }

    /**
     * Test service can update a product
     */
    public function test_service_can_update_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'price' => 100.00
        ]);

        $data = [
            'name' => 'Updated Name',
            'price' => 150.00
        ];

        $request = UpdateProductRequest::create("/api/products/{$product->id}", 'PUT', $data);
        $request->setContainer(app());
        $request->validateResolved();

        $updatedProduct = $this->productService->update($request, $product);

        $this->assertEquals('Updated Name', $updatedProduct->name);
        $this->assertEquals('150.00', $updatedProduct->price);
    }

    /**
     * Test service can delete a product
     */
    public function test_service_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $result = $this->productService->delete($product);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /**
     * Test service can restore a deleted product
     */
    public function test_service_can_restore_deleted_product(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $restoredProduct = $this->productService->restore($product->id);

        $this->assertInstanceOf(Product::class, $restoredProduct);
        $this->assertNull($restoredProduct->deleted_at);
    }

    /**
     * Test service can list products with filters
     */
    public function test_service_can_list_products_with_filters(): void
    {
        Product::factory()->create(['category' => 'Eletrônicos', 'status' => 'active']);
        Product::factory()->create(['category' => 'Livros', 'status' => 'active']);
        Product::factory()->create(['category' => 'Eletrônicos', 'status' => 'inactive']);

        $filters = [
            'category' => 'Eletrônicos',
            'status' => 'active'
        ];

        $products = $this->productService->list($filters);

        $this->assertEquals(1, $products->total());
    }

    /**
     * Test service can list products with price range filter
     */
    public function test_service_can_list_products_with_price_range(): void
    {
        Product::factory()->create(['price' => 50.00]);
        Product::factory()->create(['price' => 150.00]);
        Product::factory()->create(['price' => 250.00]);

        $filters = [
            'min_price' => 100,
            'max_price' => 200
        ];

        $products = $this->productService->list($filters);

        $this->assertEquals(1, $products->total());
    }

    /**
     * Test service can list products with sorting
     */
    public function test_service_can_list_products_with_sorting(): void
    {
        Product::factory()->create(['price' => 300.00]);
        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 200.00]);

        $filters = [
            'sort_by' => 'price',
            'sort_order' => 'asc'
        ];

        $products = $this->productService->list($filters);

        $prices = $products->pluck('price')->toArray();
        $this->assertEquals(['100.00', '200.00', '300.00'], $prices);
    }

    /**
     * Test service can list products with search term
     */
    public function test_service_can_list_products_with_search_term(): void
    {
        Product::factory()->create(['name' => 'Smartphone Samsung']);
        Product::factory()->create(['name' => 'Laptop Dell']);
        Product::factory()->create(['name' => 'Smartphone Apple']);

        $filters = [
            'search' => 'Smartphone'
        ];

        $products = $this->productService->list($filters);

        $this->assertEquals(2, $products->total());
    }

    /**
     * Test service respects pagination
     */
    public function test_service_respects_pagination(): void
    {
        Product::factory()->count(25)->create();

        $filters = [
            'per_page' => 10,
            'page' => 1
        ];

        $products = $this->productService->list($filters);

        $this->assertEquals(10, $products->count());
        $this->assertEquals(25, $products->total());
    }

    /**
     * Test service can include trashed products
     */
    public function test_service_can_include_trashed_products(): void
    {
        Product::factory()->create();
        $deletedProduct = Product::factory()->create();
        $deletedProduct->delete();

        $filters = [
            'with_trashed' => 'true'
        ];

        $products = $this->productService->list($filters);

        $this->assertEquals(2, $products->total());
    }

    /**
     * Test service throws exception when restoring non-existent product
     */
    public function test_service_throws_exception_when_restoring_non_existent_product(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->productService->restore(99999);
    }

    /**
     * Test service list returns paginated results
     */
    public function test_service_list_returns_paginated_results(): void
    {
        Product::factory()->count(5)->create();

        $products = $this->productService->list([]);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $products);
    }

    /**
     * Test service create sets default status
     */
    public function test_service_create_sets_default_status(): void
    {
        $data = [
            'sku' => 'DEFAULT001',
            'name' => 'Default Status Product',
            'price' => 99.99,
            'category' => 'Test'
        ];

        $request = StoreProductRequest::create('/api/products', 'POST', $data);
        $request->setContainer(app());
        $request->validateResolved();

        $product = $this->productService->create($request);

        $this->assertEquals('active', $product->status);
    }

    /**
     * Test service update preserves unchanged fields
     */
    public function test_service_update_preserves_unchanged_fields(): void
    {
        $product = Product::factory()->create([
            'sku' => 'PRESERVE001',
            'name' => 'Original Name',
            'price' => 100.00,
            'category' => 'Original Category'
        ]);

        $data = [
            'name' => 'Updated Name'
        ];

        $request = UpdateProductRequest::create("/api/products/{$product->id}", 'PUT', $data);
        $request->setContainer(app());
        $request->validateResolved();

        $updatedProduct = $this->productService->update($request, $product);

        $this->assertEquals('Updated Name', $updatedProduct->name);
        $this->assertEquals('PRESERVE001', $updatedProduct->sku);
        $this->assertEquals('Original Category', $updatedProduct->category);
    }

    /**
     * Test service list with empty filters returns all products
     */
    public function test_service_list_with_empty_filters_returns_all_products(): void
    {
        Product::factory()->count(10)->create();

        $products = $this->productService->list([]);

        $this->assertEquals(10, $products->total());
    }

    /**
     * Test service list excludes soft deleted products by default
     */
    public function test_service_list_excludes_soft_deleted_products_by_default(): void
    {
        Product::factory()->count(3)->create();
        $deletedProduct = Product::factory()->create();
        $deletedProduct->delete();

        $products = $this->productService->list([]);

        $this->assertEquals(3, $products->total());
    }
}
