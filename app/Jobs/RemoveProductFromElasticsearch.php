<?php

namespace App\Jobs;

use App\Models\Product;
use Elastic\Elasticsearch\ClientBuilder;
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
            $builder = ClientBuilder::create()
                ->setHosts([config('elasticsearch.host')]);

            if (config('elasticsearch.user')) {
                $builder->setBasicAuthentication(
                    config('elasticsearch.user'),
                    config('elasticsearch.password')
                );
            }

            $client = $builder->build();
            $indexName = 'products';

            $client->delete([
                'index' => $indexName,
                'id' => $this->productId
            ]);

            Log::info('Product removed from Elasticsearch', [
                'product_id' => $this->productId
            ]);
        } catch (\Exception $e) {
            // If document doesn't exist, it's not an error
            if (strpos($e->getMessage(), 'not_found') !== false || strpos($e->getMessage(), '404') !== false) {
                Log::info('Product not found in Elasticsearch (already removed)', [
                    'product_id' => $this->productId
                ]);
                return;
            }

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
