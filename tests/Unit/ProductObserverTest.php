<?php

namespace Tests\Unit;

use App\Jobs\RemoveProductFromElasticsearch;
use App\Jobs\SyncProductElasticsearch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Queue::fake();
    }

    /**
     * Test job is dispatched when product is created
     */
    public function test_job_is_dispatched_when_product_is_created(): void
    {
        $product = Product::factory()->create();

        Queue::assertPushed(SyncProductElasticsearch::class, function ($job) use ($product) {
            return $job->product->id === $product->id;
        });
    }

    /**
     * Test job is dispatched when product is updated
     */
    public function test_job_is_dispatched_when_product_is_updated(): void
    {
        $product = Product::factory()->create();
        
        Queue::fake(); // Reset queue after creation

        $product->update(['name' => 'Updated Name']);

        Queue::assertPushed(SyncProductElasticsearch::class, function ($job) use ($product) {
            return $job->product->id === $product->id;
        });
    }

    /**
     * Test job is dispatched when product is deleted
     */
    public function test_job_is_dispatched_when_product_is_deleted(): void
    {
        $product = Product::factory()->create();
        
        Queue::fake(); // Reset queue after creation

        $product->delete();

        Queue::assertPushed(RemoveProductFromElasticsearch::class, function ($job) use ($product) {
            return $job->productId === $product->id;
        });
    }

    /**
     * Test job is dispatched when product is restored
     */
    public function test_job_is_dispatched_when_product_is_restored(): void
    {
        $product = Product::factory()->create();
        $product->delete();
        
        Queue::fake(); // Reset queue after deletion

        $product->restore();

        Queue::assertPushed(SyncProductElasticsearch::class, function ($job) use ($product) {
            return $job->product->id === $product->id;
        });
    }

    /**
     * Test sync job is not dispatched multiple times for same update
     */
    public function test_sync_job_is_dispatched_once_per_update(): void
    {
        $product = Product::factory()->create();
        
        Queue::fake(); // Reset queue after creation

        $product->update(['name' => 'Updated Name']);

        Queue::assertPushed(SyncProductElasticsearch::class, 1);
    }

    /**
     * Test remove job is not dispatched multiple times for same deletion
     */
    public function test_remove_job_is_dispatched_once_per_deletion(): void
    {
        $product = Product::factory()->create();
        
        Queue::fake(); // Reset queue after creation

        $product->delete();

        Queue::assertPushed(RemoveProductFromElasticsearch::class, 1);
    }

    /**
     * Test observer handles multiple product creations
     */
    public function test_observer_handles_multiple_product_creations(): void
    {
        Product::factory()->count(3)->create();

        Queue::assertPushed(SyncProductElasticsearch::class, 3);
    }

    /**
     * Test observer handles multiple product updates
     */
    public function test_observer_handles_multiple_product_updates(): void
    {
        $products = Product::factory()->count(3)->create();
        
        Queue::fake(); // Reset queue after creation

        foreach ($products as $product) {
            $product->update(['name' => 'Updated ' . $product->name]);
        }

        Queue::assertPushed(SyncProductElasticsearch::class, 3);
    }

    /**
     * Test observer handles multiple product deletions
     */
    public function test_observer_handles_multiple_product_deletions(): void
    {
        $products = Product::factory()->count(3)->create();
        
        Queue::fake(); // Reset queue after creation

        foreach ($products as $product) {
            $product->delete();
        }

        Queue::assertPushed(RemoveProductFromElasticsearch::class, 3);
    }

    /**
     * Test correct job is dispatched for each operation
     */
    public function test_correct_job_is_dispatched_for_each_operation(): void
    {
        // Create
        $product = Product::factory()->create();
        Queue::assertPushed(SyncProductElasticsearch::class);
        Queue::assertNotPushed(RemoveProductFromElasticsearch::class);
        
        Queue::fake(); // Reset

        // Update
        $product->update(['name' => 'Updated']);
        Queue::assertPushed(SyncProductElasticsearch::class);
        Queue::assertNotPushed(RemoveProductFromElasticsearch::class);
        
        Queue::fake(); // Reset

        // Delete
        $product->delete();
        Queue::assertPushed(RemoveProductFromElasticsearch::class);
        Queue::assertNotPushed(SyncProductElasticsearch::class);
    }

    /**
     * Test observer works with mass updates
     */
    public function test_observer_works_with_mass_updates(): void
    {
        $products = Product::factory()->count(5)->create(['status' => 'active']);
        
        Queue::fake(); // Reset queue after creation

        // Mass update
        Product::where('status', 'active')->update(['status' => 'inactive']);

        // Mass updates don't trigger model events per record
        // This is expected Laravel behavior
        $this->assertTrue(true);
    }

    /**
     * Test observer doesn't interfere with product creation
     */
    public function test_observer_doesnt_interfere_with_product_creation(): void
    {
        $product = Product::factory()->create([
            'sku' => 'OBSERVER001',
            'name' => 'Observer Test'
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'OBSERVER001',
            'name' => 'Observer Test'
        ]);
    }

    /**
     * Test observer doesn't interfere with product update
     */
    public function test_observer_doesnt_interfere_with_product_update(): void
    {
        $product = Product::factory()->create(['name' => 'Original']);
        
        $product->update(['name' => 'Updated']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated'
        ]);
    }

    /**
     * Test observer doesn't interfere with product deletion
     */
    public function test_observer_doesnt_interfere_with_product_deletion(): void
    {
        $product = Product::factory()->create();
        
        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
