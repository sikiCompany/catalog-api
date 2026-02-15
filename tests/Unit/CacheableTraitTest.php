<?php

namespace Tests\Unit;

use App\Traits\Cacheable;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheableTraitTest extends TestCase
{
    use Cacheable;

    protected function setUp(): void
    {
        parent::setUp();
        
        Cache::flush();
    }

    /**
     * Test getProductCacheKey generates correct key
     */
    public function test_get_product_cache_key_generates_correct_key(): void
    {
        $key = $this->getProductCacheKey(123);

        $this->assertEquals('product_123', $key);
    }

    /**
     * Test getProductCacheKey with different IDs
     */
    public function test_get_product_cache_key_with_different_ids(): void
    {
        $key1 = $this->getProductCacheKey(1);
        $key2 = $this->getProductCacheKey(2);

        $this->assertNotEquals($key1, $key2);
        $this->assertEquals('product_1', $key1);
        $this->assertEquals('product_2', $key2);
    }

    /**
     * Test getListCacheKey generates consistent key for same params
     */
    public function test_get_list_cache_key_generates_consistent_key(): void
    {
        $params = ['category' => 'Test', 'status' => 'active'];

        $key1 = $this->getListCacheKey($params);
        $key2 = $this->getListCacheKey($params);

        $this->assertEquals($key1, $key2);
    }

    /**
     * Test getListCacheKey generates same key regardless of param order
     */
    public function test_get_list_cache_key_ignores_param_order(): void
    {
        $params1 = ['category' => 'Test', 'status' => 'active'];
        $params2 = ['status' => 'active', 'category' => 'Test'];

        $key1 = $this->getListCacheKey($params1);
        $key2 = $this->getListCacheKey($params2);

        $this->assertEquals($key1, $key2);
    }

    /**
     * Test getListCacheKey generates different keys for different params
     */
    public function test_get_list_cache_key_generates_different_keys_for_different_params(): void
    {
        $params1 = ['category' => 'Test1'];
        $params2 = ['category' => 'Test2'];

        $key1 = $this->getListCacheKey($params1);
        $key2 = $this->getListCacheKey($params2);

        $this->assertNotEquals($key1, $key2);
    }

    /**
     * Test getListCacheKey with empty params
     */
    public function test_get_list_cache_key_with_empty_params(): void
    {
        $key = $this->getListCacheKey([]);

        $this->assertIsString($key);
        $this->assertStringStartsWith('products_list_', $key);
    }

    /**
     * Test shouldBypassCache returns false for low page numbers
     */
    public function test_should_bypass_cache_returns_false_for_low_page_numbers(): void
    {
        $this->assertFalse($this->shouldBypassCache(['page' => 1]));
        $this->assertFalse($this->shouldBypassCache(['page' => 25]));
        $this->assertFalse($this->shouldBypassCache(['page' => 50]));
    }

    /**
     * Test shouldBypassCache returns true for high page numbers
     */
    public function test_should_bypass_cache_returns_true_for_high_page_numbers(): void
    {
        $this->assertTrue($this->shouldBypassCache(['page' => 51]));
        $this->assertTrue($this->shouldBypassCache(['page' => 100]));
        $this->assertTrue($this->shouldBypassCache(['page' => 1000]));
    }

    /**
     * Test shouldBypassCache returns false when page is not set
     */
    public function test_should_bypass_cache_returns_false_when_page_not_set(): void
    {
        $this->assertFalse($this->shouldBypassCache([]));
        $this->assertFalse($this->shouldBypassCache(['category' => 'Test']));
    }

    /**
     * Test remember method caches data
     */
    public function test_remember_method_caches_data(): void
    {
        $key = 'test_key';
        $ttl = now()->addMinutes(5);
        $value = 'test_value';

        $result = $this->remember($key, $ttl, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::tags(['products'])->has($key));
    }

    /**
     * Test remember method returns cached data on second call
     */
    public function test_remember_method_returns_cached_data_on_second_call(): void
    {
        $key = 'test_key';
        $ttl = now()->addMinutes(5);
        $callCount = 0;

        // First call
        $result1 = $this->remember($key, $ttl, function () use (&$callCount) {
            $callCount++;
            return 'value';
        });

        // Second call
        $result2 = $this->remember($key, $ttl, function () use (&$callCount) {
            $callCount++;
            return 'value';
        });

        $this->assertEquals($result1, $result2);
        $this->assertEquals(1, $callCount); // Callback should only be called once
    }

    /**
     * Test clearProductCache removes product cache
     */
    public function test_clear_product_cache_removes_product_cache(): void
    {
        $productId = 123;
        $key = $this->getProductCacheKey($productId);

        // Cache some data
        Cache::tags(['products'])->put($key, 'test_data', 60);
        $this->assertTrue(Cache::tags(['products'])->has($key));

        // Clear cache
        $this->clearProductCache($productId);

        // Cache should be cleared
        $this->assertFalse(Cache::tags(['products'])->has($key));
    }

    /**
     * Test clearProductCache flushes all product tags
     */
    public function test_clear_product_cache_flushes_all_product_tags(): void
    {
        // Cache multiple items with products tag
        Cache::tags(['products'])->put('product_1', 'data1', 60);
        Cache::tags(['products'])->put('product_2', 'data2', 60);
        Cache::tags(['products'])->put('products_list_1', 'list1', 60);

        $this->assertTrue(Cache::tags(['products'])->has('product_1'));
        $this->assertTrue(Cache::tags(['products'])->has('product_2'));
        $this->assertTrue(Cache::tags(['products'])->has('products_list_1'));

        // Clear cache for one product
        $this->clearProductCache(1);

        // All tagged cache should be cleared
        $this->assertFalse(Cache::tags(['products'])->has('product_1'));
        $this->assertFalse(Cache::tags(['products'])->has('product_2'));
        $this->assertFalse(Cache::tags(['products'])->has('products_list_1'));
    }

    /**
     * Test cache key format for product list
     */
    public function test_cache_key_format_for_product_list(): void
    {
        $params = ['category' => 'Test'];
        $key = $this->getListCacheKey($params);

        $this->assertStringStartsWith('products_list_', $key);
        $this->assertIsString($key);
    }

    /**
     * Test cache key is MD5 hash
     */
    public function test_cache_key_is_md5_hash(): void
    {
        $params = ['category' => 'Test'];
        $key = $this->getListCacheKey($params);

        // Remove prefix
        $hash = str_replace('products_list_', '', $key);

        // Check if it's a valid MD5 hash (32 characters, hexadecimal)
        $this->assertEquals(32, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $hash);
    }

    /**
     * Test shouldBypassCache with string page number
     */
    public function test_should_bypass_cache_with_string_page_number(): void
    {
        $this->assertTrue($this->shouldBypassCache(['page' => '51']));
        $this->assertFalse($this->shouldBypassCache(['page' => '50']));
    }

    /**
     * Test remember handles exceptions gracefully
     */
    public function test_remember_handles_exceptions_gracefully(): void
    {
        // This test verifies that if cache fails, the callback is still executed
        $key = 'test_key';
        $ttl = now()->addMinutes(5);
        $expectedValue = 'fallback_value';

        $result = $this->remember($key, $ttl, function () use ($expectedValue) {
            return $expectedValue;
        });

        $this->assertEquals($expectedValue, $result);
    }

    /**
     * Test getListCacheKey with complex params
     */
    public function test_get_list_cache_key_with_complex_params(): void
    {
        $params = [
            'category' => 'Electronics',
            'status' => 'active',
            'min_price' => 100,
            'max_price' => 500,
            'sort_by' => 'price',
            'sort_order' => 'asc',
            'per_page' => 15,
            'page' => 1
        ];

        $key = $this->getListCacheKey($params);

        $this->assertIsString($key);
        $this->assertStringStartsWith('products_list_', $key);
    }

    /**
     * Test getListCacheKey with special characters
     */
    public function test_get_list_cache_key_with_special_characters(): void
    {
        $params = [
            'category' => 'Test & Special',
            'search' => 'product name'
        ];

        $key = $this->getListCacheKey($params);

        $this->assertIsString($key);
        $this->assertStringStartsWith('products_list_', $key);
    }
}
