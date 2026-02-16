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

class SyncProductElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The product instance.
     *
     * @var \App\Models\Product
     */
    public $product;

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
    public function __construct(Product $product)
    {
        $this->product = $product;
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

            $body = [
                ['index' => ['_index' => $indexName, '_id' => $this->product->id]],
                $this->product->toSearchableArray()
            ];

            $client->bulk(['body' => $body]);

            Log::info('Product synced to Elasticsearch', [
                'product_id' => $this->product->id,
                'sku' => $this->product->sku
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync product to Elasticsearch', [
                'product_id' => $this->product->id,
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
        Log::error('Product Elasticsearch sync job failed', [
            'product_id' => $this->product->id,
            'error' => $exception->getMessage()
        ]);
    }
}
