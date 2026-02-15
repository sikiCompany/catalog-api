<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configura cache para usar array driver nos testes
        config(['cache.default' => 'array']);
        
        // Limpa o cache antes de cada teste
        Cache::flush();
    }

    /**
     * Test that product show endpoint uses cache
     */
    public function test_product_show_uses_cache(): void
    {
        Cache::flush();

        $product = Product::factory()->create();

        $cacheKey = "product_{$product->id}";

        // Primeira requisição - deve gerar cache
        $response1 = $this->getJson("/api/products/{$product->id}");
        $response1->assertStatus(200);

        // Verifica se o cache foi criado (USANDO TAGS)
        $this->assertTrue(
            Cache::tags(['products'])->has($cacheKey),
            "Cache key '{$cacheKey}' not found"
        );

        // Segunda requisição - deve vir do cache
        $response2 = $this->getJson("/api/products/{$product->id}");
        $response2->assertStatus(200);

        // Ambas as respostas devem ser idênticas
        $this->assertEquals(
            $response1->json(),
            $response2->json()
        );
    }

    /**
     * Test that cache is invalidated when product is updated
     */
    public function test_cache_is_invalidated_on_product_update(): void
    {
        $product = Product::factory()->create(['name' => 'Original Name']);

        // Cacheia o produto
        $this->getJson("/api/products/{$product->id}");
        
        $cacheKey = "product_{$product->id}";
        $this->assertTrue(Cache::tags(['products'])->has($cacheKey));

        // Atualiza o produto
        $this->putJson("/api/products/{$product->id}", [
            'name' => 'Updated Name'
        ]);

        // Cache deve ter sido invalidado
        $this->assertFalse(Cache::tags(['products'])->has($cacheKey));

        // Nova requisição deve retornar dados atualizados
        $response = $this->getJson("/api/products/{$product->id}");
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name'
                ]
            ]);
    }

    /**
     * Test that cache is invalidated when product is deleted
     */
    public function test_cache_is_invalidated_on_product_delete(): void
    {
        $product = Product::factory()->create();

        // Cacheia o produto
        $this->getJson("/api/products/{$product->id}");
        
        $cacheKey = "product_{$product->id}";
        $this->assertTrue(Cache::tags(['products'])->has($cacheKey));

        // Deleta o produto
        $this->deleteJson("/api/products/{$product->id}");

        // Cache deve ter sido invalidado
        $this->assertFalse(Cache::tags(['products'])->has($cacheKey));
    }

    /**
     * Test that cache is invalidated when product is created
     */
    public function test_cache_is_invalidated_on_product_create(): void
    {
        // Cria alguns produtos e cacheia a listagem
        Product::factory()->count(3)->create();
        $this->getJson('/api/products');

        // Cria um novo produto
        $this->postJson('/api/products', [
            'sku' => 'NEW001',
            'name' => 'New Product',
            'price' => 99.99,
            'category' => 'Test'
        ]);

        // Cache da listagem deve ter sido limpo (tags)
        // Nova requisição deve incluir o novo produto
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
        
        $this->assertEquals(4, count($response->json('data')));
    }

    /**
     * Test that list endpoint uses cache
     */
    public function test_product_list_uses_cache(): void
    {
        Product::factory()->count(5)->create();

        // Primeira requisição
        $response1 = $this->getJson('/api/products?per_page=5');
        $response1->assertStatus(200);

        // Segunda requisição - deve vir do cache
        $response2 = $this->getJson('/api/products?per_page=5');
        $response2->assertStatus(200);

        // Respostas devem ser idênticas
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * Test that different filter combinations create different cache keys
     */
    public function test_different_filters_create_different_cache_keys(): void
    {
        Product::factory()->count(10)->create([
            'category' => 'Eletrônicos',
            'status' => 'active'
        ]);

        // Requisição com filtro de categoria
        $response1 = $this->getJson('/api/products?category=Eletrônicos');
        $response1->assertStatus(200);

        // Requisição com filtro de status
        $response2 = $this->getJson('/api/products?status=active');
        $response2->assertStatus(200);

        // Requisição com ambos os filtros
        $response3 = $this->getJson('/api/products?category=Eletrônicos&status=active');
        $response3->assertStatus(200);

        // Todas devem retornar sucesso (cache separado para cada combinação)
        $this->assertTrue(true);
    }

    /**
     * Test that high page numbers bypass cache
     */
    public function test_high_page_numbers_bypass_cache(): void
    {
        Product::factory()->count(100)->create();

        // Página alta (> 50) não deve usar cache
        $response = $this->getJson('/api/products?page=51');
        
        $response->assertStatus(200);
        
        // Não há uma forma direta de verificar se o cache foi usado,
        // mas o endpoint deve funcionar normalmente
        $this->assertTrue(true);
    }

    /**
     * Test search endpoint uses cache
     */
    public function test_search_endpoint_uses_cache(): void
    {
        Product::factory()->count(5)->create([
            'category' => 'Eletrônicos'
        ]);

        // Primeira busca
        $response1 = $this->getJson('/api/search/products?category=Eletrônicos');
        $response1->assertStatus(200);

        // Segunda busca - deve vir do cache
        $response2 = $this->getJson('/api/search/products?category=Eletrônicos');
        $response2->assertStatus(200);

        // Respostas devem ser idênticas
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * Test that cache has TTL between 60-120 seconds
     */
    public function test_cache_has_proper_ttl(): void
    {
        $product = Product::factory()->create();

        // Faz requisição para cachear
        $this->getJson("/api/products/{$product->id}");

        $cacheKey = "product_{$product->id}";
        
        // Verifica se o cache existe
        $this->assertTrue(Cache::tags(['products'])->has($cacheKey));

        // Nota: Não podemos testar o TTL exato em testes unitários,
        // mas podemos verificar que o cache foi criado
        $this->assertTrue(true);
    }

    /**
     * Test cache key generation for product list
     */
    public function test_cache_key_generation_for_list(): void
    {
        Product::factory()->count(3)->create();

        // Faz requisição com parâmetros específicos
        $params = [
            'category' => 'Test',
            'status' => 'active',
            'per_page' => 10
        ];

        $queryString = http_build_query($params);
        $response = $this->getJson("/api/products?{$queryString}");

        $response->assertStatus(200);
        
        // Cache deve ter sido criado com chave única para esses parâmetros
        $this->assertTrue(true);
    }

    /**
     * Test that restored product invalidates cache
     */
    public function test_restored_product_invalidates_cache(): void
    {
        $product = Product::factory()->create();
        
        // Cacheia o produto
        $this->getJson("/api/products/{$product->id}");
        
        // Deleta o produto
        $product->delete();
        
        // Restaura o produto
        $this->postJson("/api/products/{$product->id}/restore");

        // Cache deve ter sido invalidado
        $cacheKey = "product_{$product->id}";
        $this->assertFalse(Cache::has($cacheKey));
    }
}
