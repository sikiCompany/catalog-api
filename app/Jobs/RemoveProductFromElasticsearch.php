<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemoveProductFromElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The product ID.
     *
     * @var string
     */
    public $productId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(string $productId)
    {
        $this->productId = $productId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $product = Product::withTrashed()->find($this->productId);
            
            if ($product) {
                $product->unsearchable();
                
                Log::info('Product removed from Elasticsearch', [
                    'product_id' => $this->productId
                ]);
            } else {
                Log::warning('Product not found for Elasticsearch removal', [
                    'product_id' => $this->productId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to remove product from Elasticsearch', [
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Product Elasticsearch removal job failed', [
            'product_id' => $this->productId,
            'error' => $exception->getMessage()
        ]);
    }
}
