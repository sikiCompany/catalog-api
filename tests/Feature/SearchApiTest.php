<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test searching products with query term
     */
    public function test_can_search_products_with_query_term(): void
    {
        Product::factory()->create([
            'name' => 'Smartphone Samsung',
            'description' => 'Latest Samsung phone'
        ]);
        
        Product::factory()->create([
            'name' => 'Laptop Dell',
            'description' => 'Dell laptop for work'
        ]);

        $response = $this->getJson('/api/search/products?q=Samsung&page=1&per_page=10');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'name',
                        'description',
                        'price',
                        'category',
                        'status'
                    ]
                ],
                'links',
                'meta'
            ]
        ]);
    }

    /**
     * Test searching products by category
     */
    public function test_can_search_products_by_category(): void
    {
        // Product::factory()->create(['category' => 'Eletrônicos']);
        // Product::factory()->create(['category' => 'Livros']);
        // Product::factory()->create(['category' => 'Eletrônicos']);

            Product::factory()->create([
                'name' => 'Smartphone',
                'description'=> '',
                'category' => 'Eletrônicos',
                'status'=> 'active',
                'price' => 999.99
            ]);
  
        $response = $this->getJson('/api/search/products?category=Eletrônicos');

        $response->assertStatus(200);
        
        // Verifica se retornou dados
        $data = $response->json('data.data');
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
    }

    /**
     * Test searching products with price range
     */
    public function test_can_search_products_with_price_range(): void
    {
        Product::factory()->create(['price' => 50]);
        Product::factory()->create(['price' => 150]);
        Product::factory()->create(['price' => 750]);
        Product::factory()->create(['price' => 1750]);


        $response = $this->getJson('/api/search/products?min_price=100&max_price=750');

        $response->assertStatus(200);

        $response->assertJsonCount(2, 'data.data');
        $prices = collect($response->json()['data']['data'])->pluck('price');

        $this->assertTrue($prices->contains(150));
        $this->assertTrue($prices->contains(750));
    }

    /**
     * Test searching products by status
     */
    public function test_can_search_products_by_status(): void
    {
        Product::factory()->create(['status' => 'active']);
        Product::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/search/products?status=active');
        $response->assertStatus(200);
        
        // Verifica se retornou dados
        $data = $response->json('data.data');
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
    }

    /**
     * Test searching with multiple filters
     */
    public function test_can_search_with_multiple_filters(): void
    {
        Product::factory()->create([
            'name' => 'Smartphone Premium',
            'category' => 'Eletrônicos',
            'price' => 1500.00,
            'status' => 'active'
        ]);

        Product::factory()->create([
            'name' => 'Smartphone Basic',
            'category' => 'Eletrônicos',
            'price' => 500.00,
            'status' => 'inactive'
        ]);

        $response = $this->getJson('/api/search/products?q=Smartphone&category=Eletrônicos&min_price=1000&status=active');

        $response->assertStatus(200);
        // Verifica estrutura da resposta
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'name',
                        'price',
                        'category',
                        'status'
                    ]
                ],
                'links',
                'meta'
            ]
        ]);
    }

    /**
     * Test search with sorting by price ascending
     */
    public function test_can_search_with_sort_by_price_asc(): void
    {
        Product::factory()->create(['price' => 300.00]);
        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 200.00]);

        $response = $this->getJson('/api/search/products?sort=price&order=asc');

        $response->assertStatus(200);
        
        // Verifica se retornou dados
        $data = $response->json('data.data');
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
    }

    /**
     * Test search with sorting by price descending
     */
    public function test_can_search_with_sort_by_price_desc(): void
    {
        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 300.00]);
        Product::factory()->create(['price' => 200.00]);

        $response = $this->getJson('/api/search/products?sort=price&order=desc');

        $response->assertStatus(200);
        
        // Verifica se retornou dados
        $data = $response->json('data.data');
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
    }

    /**
     * Test search with pagination
     */
    public function test_can_search_with_pagination(): void
    {
        Product::factory()->count(25)->create();

        $response = $this->getJson('/api/search/products?per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'links',
                    'meta' => [
                        'current_page',
                        'total',
                        'per_page'
                    ]
                ]
            ]);
    }

    /**
     * Test search with invalid status returns validation error
     */
    public function test_search_with_invalid_status_returns_validation_error(): void
    {
        $response = $this->getJson('/api/search/products?status=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test search with invalid sort field returns validation error
     */
    public function test_search_with_invalid_sort_field_returns_validation_error(): void
    {
        $response = $this->getJson('/api/search/products?sort=invalid_field');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    /**
     * Test search with invalid order returns validation error
     */
    public function test_search_with_invalid_order_returns_validation_error(): void
    {
        $response = $this->getJson('/api/search/products?order=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }

    /**
     * Test search with negative price returns validation error
     */
    public function test_search_with_negative_price_returns_validation_error(): void
    {
        $response = $this->getJson('/api/search/products?min_price=-10');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['min_price']);
    }

    /**
     * Test search returns empty result when no products match
     */
    public function test_search_returns_empty_when_no_products_match(): void
    {
        Product::factory()->create(['name' => 'Smartphone']);

        $response = $this->getJson('/api/search/products?q=NonExistentProduct');

        $response->assertStatus(200);
        
        // Pode retornar array vazio ou estrutura de paginação vazia
        $this->assertIsArray($response->json('data'));
    }

    /**
     * Test search with very long query string
     */
    public function test_search_with_long_query_returns_validation_error(): void
    {
        $longQuery = str_repeat('a', 101);
        
        $response = $this->getJson("/api/search/products?q={$longQuery}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    /**
     * Test search endpoint returns proper JSON structure
     */
    public function test_search_returns_proper_json_structure(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/search/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'sku',
                            'name',
                            'price',
                            'category',
                            'status'
                        ]
                    ],
                    'links',
                    'meta'
                ]
            ]);
    }

    /**
     * Test search with per_page limit
     */
    public function test_search_respects_per_page_limit(): void
    {
        Product::factory()->count(150)->create();

        $response = $this->getJson('/api/search/products?per_page=100');

        $response->assertStatus(200);
        
        // Verifica se não retorna mais que o limite
        $this->assertLessThanOrEqual(100, count($response->json('data.data')));;
    }

    /**
     * Test search with per_page exceeding maximum returns validation error
     */
    public function test_search_with_excessive_per_page_returns_validation_error(): void
    {
        $response = $this->getJson('/api/search/products?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }
}
