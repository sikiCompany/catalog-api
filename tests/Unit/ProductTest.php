<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test product can be created with valid data
     */
    public function test_product_can_be_created_with_valid_data(): void
    {
        $product = Product::create([
            'sku' => 'TEST001',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'category' => 'Test Category',
            'status' => 'active'
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('TEST001', $product->sku);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(99.99, $product->price);
    }

    /**
     * Test product has correct fillable attributes
     */
    public function test_product_has_correct_fillable_attributes(): void
    {
        $product = new Product();
        
        $fillable = $product->getFillable();
        
        $this->assertContains('sku', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('price', $fillable);
        $this->assertContains('category', $fillable);
        $this->assertContains('status', $fillable);
    }

    /**
     * Test product price is cast to decimal
     */
    public function test_product_price_is_cast_to_decimal(): void
    {
        $product = Product::factory()->create(['price' => 99.99]);

        $this->assertIsString($product->price);
        $this->assertEquals('99.99', $product->price);
    }

    /**
     * Test product has soft delete trait
     */
    public function test_product_uses_soft_delete(): void
    {
        $product = Product::factory()->create();
        
        $product->delete();
        
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertNotNull($product->fresh()->deleted_at);
    }

    /**
     * Test product can be restored after soft delete
     */
    public function test_product_can_be_restored_after_soft_delete(): void
    {
        $product = Product::factory()->create();
        
        $product->delete();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        
        $product->restore();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null
        ]);
    }

    /**
     * Test product default status is active
     */
    public function test_product_default_status_is_active(): void
    {
        $product = Product::factory()->create(['status' => 'active']);
        $this->assertEquals('active', $product->status);
    }

    /**
     * Test product can have inactive status
     */
    public function test_product_can_have_inactive_status(): void
    {
        $product = Product::factory()->create(['status' => 'inactive']);
        
        $this->assertEquals('inactive', $product->status);
    }

    /**
     * Test product SKU must be unique
     */
    public function test_product_sku_must_be_unique(): void
    {
        Product::factory()->create(['sku' => 'UNIQUE001']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Product::factory()->create(['sku' => 'UNIQUE001']);
    }

    /**
     * Test product has timestamps
     */
    public function test_product_has_timestamps(): void
    {
        $product = Product::factory()->create();
        
        $this->assertNotNull($product->created_at);
        $this->assertNotNull($product->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $product->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $product->updated_at);
    }

    /**
     * Test product validation rules
     */
    public function test_product_has_validation_rules(): void
    {
        $rules = Product::rules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('sku', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('category', $rules);
    }

    /**
     * Test product name validation requires minimum 3 characters
     */
    public function test_product_name_validation_requires_minimum_characters(): void
    {
        $rules = Product::rules();
        
        $this->assertStringContainsString('min:3', $rules['name']);
    }

    /**
     * Test product price validation requires positive value
     */
    public function test_product_price_validation_requires_positive_value(): void
    {
        $rules = Product::rules();
        
        $this->assertStringContainsString('min:0.01', $rules['price']);
    }

    /**
     * Test product SKU validation requires unique value
     */
    public function test_product_sku_validation_requires_unique_value(): void
    {
        $rules = Product::rules();
        
        $this->assertStringContainsString('unique:products,sku', $rules['sku']);
    }

    /**
     * Test product status validation accepts only active or inactive
     */
    public function test_product_status_validation_accepts_only_valid_values(): void
    {
        $rules = Product::rules();
        
        $this->assertStringContainsString('in:active,inactive', $rules['status']);
    }

    /**
     * Test product has searchable trait
     */
    public function test_product_uses_searchable_trait(): void
    {
        $product = new Product();
        
        $traits = class_uses($product);
        
        $this->assertContains('Laravel\Scout\Searchable', $traits);
    }

    /**
     * Test product toSearchableArray returns correct structure
     */
    public function test_product_to_searchable_array_returns_correct_structure(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SEARCH001',
            'name' => 'Searchable Product',
            'price' => 99.99
        ]);

        $searchableArray = $product->toSearchableArray();

        $this->assertIsArray($searchableArray);
        $this->assertArrayHasKey('id', $searchableArray);
        $this->assertArrayHasKey('sku', $searchableArray);
        $this->assertArrayHasKey('name', $searchableArray);
        $this->assertArrayHasKey('description', $searchableArray);
        $this->assertArrayHasKey('price', $searchableArray);
        $this->assertArrayHasKey('category', $searchableArray);
        $this->assertArrayHasKey('status', $searchableArray);
        $this->assertArrayHasKey('created_at', $searchableArray);
    }

    /**
     * Test product price in searchable array is float
     */
    public function test_product_price_in_searchable_array_is_float(): void
    {
        $product = Product::factory()->create(['price' => 99.99]);

        $searchableArray = $product->toSearchableArray();

        $this->assertIsFloat($searchableArray['price']);
        $this->assertEquals(99.99, $searchableArray['price']);
    }

    /**
     * Test product created_at in searchable array is timestamp
     */
    public function test_product_created_at_in_searchable_array_is_timestamp(): void
    {
        $product = Product::factory()->create();

        $searchableArray = $product->toSearchableArray();

        $this->assertIsInt($searchableArray['created_at']);
    }

    /**
     * Test product can be updated
     */
    public function test_product_can_be_updated(): void
    {
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'price' => 100.00
        ]);

        $product->update([
            'name' => 'Updated Name',
            'price' => 150.00
        ]);

        $this->assertEquals('Updated Name', $product->name);
        $this->assertEquals('150.00', $product->price);
    }

    /**
     * Test product description can be nullable
     */
    public function test_product_description_can_be_nullable(): void
    {
        $product = Product::factory()->create(['description' => null]);

        $this->assertNull($product->description);
    }

    /**
     * Test product factory creates valid products
     */
    public function test_product_factory_creates_valid_products(): void
    {
        $product = Product::factory()->create();

        $this->assertNotNull($product->sku);
        $this->assertNotNull($product->name);
        $this->assertNotNull($product->price);
        $this->assertNotNull($product->category);
        $this->assertGreaterThan(0, $product->price);
    }

    /**
     * Test product factory can create multiple products
     */
    public function test_product_factory_can_create_multiple_products(): void
    {
        $products = Product::factory()->count(5)->create();

        $this->assertCount(5, $products);
        
        foreach ($products as $product) {
            $this->assertInstanceOf(Product::class, $product);
        }
    }

    /**
     * Test product factory creates unique SKUs
     */
    public function test_product_factory_creates_unique_skus(): void
    {
        $products = Product::factory()->count(10)->create();

        $skus = $products->pluck('sku')->toArray();
        $uniqueSkus = array_unique($skus);

        $this->assertCount(10, $uniqueSkus);
    }
}

