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

    protected $productId;

    public function __construct($productId)
    {
        $this->productId = $productId;
    }

    public function handle()
    {
        try {
            $product = (new Product())->newQuery()
                ->where('id', $this->productId)
                ->withTrashed()
                ->first();
            
            if ($product) {
                $product->unsearchable();
                Log::info('Produto removido do Elasticsearch', ['id' => $this->productId]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao remover produto do Elasticsearch', [
                'id' => $this->productId,
                'error' => $e->getMessage()
            ]);
        }
    }
}