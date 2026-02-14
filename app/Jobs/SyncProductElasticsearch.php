<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProductToElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function handle()
    {
        try {
            $this->product->searchable();
            Log::info('Produto sincronizado com Elasticsearch', ['id' => $this->product->id]);
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar produto com Elasticsearch', [
                'id' => $this->product->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}