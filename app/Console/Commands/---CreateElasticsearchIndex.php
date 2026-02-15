<?php

namespace App\Console\Commands;

use App\Models\Product;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

class CreateElasticsearchIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-elasticsearch-index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Elasticsearch index for Product model and sync data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $builder = ClientBuilder::create()
                ->setHosts([config('elasticsearch.host')]);

            // Add authentication only if credentials are present
            if (config('elasticsearch.user')) {
                $builder->setBasicAuthentication(
                    config('elasticsearch.user'),
                    config('elasticsearch.password')
                );
            }

            $client = $builder->build();
            $indexName = 'products';

            $this->info("Elasticsearch Host: " . config('elasticsearch.host'));

            // Check if index exists
            try {
                $indexExists = $client->indices()->exists(['index' => $indexName]);
                if (!$indexExists->asBool()) {
                    $this->info("Creating index '$indexName'...");

                    $client->indices()->create([
                        'index' => $indexName,
                        'body' => [
                            'settings' => [
                                'number_of_shards' => 1,
                                'number_of_replicas' => 0,
                            ],
                            'mappings' => [
                                'properties' => [
                                    'id' => ['type' => 'keyword'],
                                    'sku' => ['type' => 'keyword'],
                                    'name' => ['type' => 'text', 'analyzer' => 'standard'],
                                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                                    'price' => ['type' => 'float'],
                                    'category' => ['type' => 'keyword'],
                                    'status' => ['type' => 'keyword'],
                                    'created_at' => ['type' => 'date'],
                                ],
                            ],
                        ],
                    ]);

                    $this->info("✅ Index '$indexName' created successfully!");
                } else {
                    $this->info("ℹ️ Index '$indexName' already exists.");
                }
            } catch (\Exception $e) {
                $this->error("Error managing index: " . $e->getMessage());
            }

            // Sync products directly to Elasticsearch
            $this->info("\nSyncing Product data to Elasticsearch...");
            
            $count = Product::count();
            $this->info("Found $count products to sync");

            if ($count > 0) {
                $products = Product::all();
                $body = [];

                foreach ($products as $product) {
                    $body[] = ['index' => ['_index' => $indexName, '_id' => $product->id]];
                    $body[] = $product->toSearchableArray();
                }

                if (!empty($body)) {
                    $response = $client->bulk(['body' => $body]);
                    sleep(1); // Allow Elasticsearch to process
                    
                    // Count documents in the alias or index
                    try {
                        $countResult = $client->count(['index' => $indexName]);
                        $actualCount = $countResult['count'];
                        $this->info("✅ $count products synced successfully!");
                        $this->info("Verified: $actualCount documents in Elasticsearch index '$indexName'");
                    } catch (\Exception $e) {
                        $this->info("✅ $count products synced successfully!");
                        $this->warn("Could not verify count: " . $e->getMessage());
                    }
                }
            } else {
                $this->warn("⚠️ No products to sync");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
