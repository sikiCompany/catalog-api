<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test listing products with pagination
     */
    public function test_can_list_products_with_pagination(): void
    {
        Product::factory()->count(25)->create();

        $response = $this->getJson('/api/products?per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'name',
                        'description',
                        'price',
                        'category',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links',
                'meta' => [
                    'current_page',
                    'total',
                    'per_page'
                ]
            ]);

        $this->assertEquals(10, count($response->json('data')));
    }

    /**
     * Test filtering products by category
     */
    public function test_can_filter_products_by_category(): void
    {
        Product::factory()->create(['category' => 'Eletrônicos']);
        Product::factory()->create(['category' => 'Livros']);
        Product::factory()->create(['category' => 'Eletrônicos']);

        $response = $this->getJson('/api/products?category=Eletrônicos');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
    }

    /**
     * Test filtering products by status
     */
    public function test_can_filter_products_by_status(): void
    {
        Product::factory()->create(['status' => 'active']);
        Product::factory()->create(['status' => 'inactive']);
        Product::factory()->create(['status' => 'active']);

        $response = $this->getJson('/api/products?status=active');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data')));
    }

    /**
     * Test filtering products by price range
     */
    public function test_can_filter_products_by_price_range(): void
    {
        Product::factory()->create(['price' => 50.00]);
        Product::factory()->create(['price' => 150.00]);
        Product::factory()->create(['price' => 250.00]);

        $response = $this->getJson('/api/products?min_price=100&max_price=200');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data')));
    }

    /**
     * Test creating a product with valid data
     */
    public function test_can_create_product_with_valid_data(): void
    {
        $productData = [
            'sku' => 'TEST001',
            'name' => 'Test Product',
            'description' => 'This is a test product',
            'price' => 99.99,
            'category' => 'Test Category',
            'status' => 'active'
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Produto criado com sucesso'
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'sku',
                    'name',
                    'description',
                    'price',
                    'category',
                    'status'
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST001',
            'name' => 'Test Product'
        ]);
    }

    /**
     * Test creating product fails without required fields
     */
    public function test_cannot_create_product_without_required_fields(): void
    {
        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku', 'name', 'price', 'category']);
    }

    /**
     * Test creating product fails with invalid price
     */
    public function test_cannot_create_product_with_invalid_price(): void
    {
        $productData = [
            'sku' => 'TEST002',
            'name' => 'Test Product',
            'price' => -10.00,
            'category' => 'Test'
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    /**
     * Test creating product fails with duplicate SKU
     */
    public function test_cannot_create_product_with_duplicate_sku(): void
    {
        Product::factory()->create(['sku' => 'DUPLICATE001']);

        $productData = [
            'sku' => 'DUPLICATE001',
            'name' => 'Another Product',
            'price' => 50.00,
            'category' => 'Test'
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    /**
     * Test creating product fails with short name
     */
    public function test_cannot_create_product_with_short_name(): void
    {
        $productData = [
            'sku' => 'TEST003',
            'name' => 'AB',
            'price' => 50.00,
            'category' => 'Test'
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test showing a single product
     */
    public function test_can_show_single_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name
                ]
            ]);
    }

    /**
     * Test showing non-existent product returns 404
     */
    public function test_showing_non_existent_product_returns_404(): void
    {
        $response = $this->getJson('/api/products/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Produto não encontrado'
            ]);
    }

    /**
     * Test updating a product
     */
    public function test_can_update_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'price' => 100.00
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'price' => 150.00
        ];

        $response = $this->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Produto atualizado com sucesso',
                'data' => [
                    'name' => 'Updated Name',
                    'price' => '150.00'
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'price' => 150.00
        ]);
    }

    /**
     * Test updating product with invalid data fails
     */
    public function test_cannot_update_product_with_invalid_data(): void
    {
        $product = Product::factory()->create();

        $updateData = [
            'price' => -50.00
        ];

        $response = $this->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    /**
     * Test deleting a product (soft delete)
     */
    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Produto excluído com sucesso'
            ]);

        $this->assertSoftDeleted('products', [
            'id' => $product->id
        ]);
    }

    /**
     * Test restoring a soft-deleted product
     */
    public function test_can_restore_deleted_product(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $response = $this->postJson("/api/products/{$product->id}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Produto restaurado com sucesso'
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null
        ]);
    }

    /**
     * Test restoring non-existent product returns 404
     */
    public function test_restoring_non_existent_product_returns_404(): void
    {
        $response = $this->postJson('/api/products/99999/restore');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Produto não encontrado'
            ]);
    }

    /**
     * Test sorting products by price ascending
     */
    public function test_can_sort_products_by_price_ascending(): void
    {
        Product::factory()->create(['price' => 300.00]);
        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 200.00]);

        $response = $this->getJson('/api/products?sort_by=price&sort_order=asc');

        $response->assertStatus(200);
        
        $prices = collect($response->json('data'))->pluck('price')->toArray();
        $this->assertEquals(['100.00', '200.00', '300.00'], $prices);
    }

    /**
     * Test sorting products by price descending
     */
    public function test_can_sort_products_by_price_descending(): void
    {
        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 300.00]);
        Product::factory()->create(['price' => 200.00]);

        $response = $this->getJson('/api/products?sort_by=price&sort_order=desc');

        $response->assertStatus(200);
        
        $prices = collect($response->json('data'))->pluck('price')->toArray();
        $this->assertEquals(['300.00', '200.00', '100.00'], $prices);
    }

    /**
     * Test product default status is active
     */
    public function test_product_default_status_is_active(): void
    {
        $productData = [
            'sku' => 'TEST004',
            'name' => 'Test Product',
            'price' => 99.99,
            'category' => 'Test'
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'status' => 'active'
                ]
            ]);
    }

    /**
     * Test creating product with inactive status
     */
    public function test_can_create_product_with_inactive_status(): void
    {
        $productData = [
            'sku' => 'TEST005',
            'name' => 'Inactive Product',
            'price' => 99.99,
            'category' => 'Test',
            'status' => 'inactive'
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'status' => 'inactive'
                ]
            ]);
    }

    /**
     * Test creating product with invalid status fails
     */
    public function test_cannot_create_product_with_invalid_status(): void
    {
        $productData = [
            'sku' => 'TEST006',
            'name' => 'Test Product',
            'price' => 99.99,
            'category' => 'Test',
            'status' => 'invalid_status'
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
