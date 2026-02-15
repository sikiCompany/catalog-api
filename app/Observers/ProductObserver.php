<?php

namespace App\Observers;

use App\Models\Product;
use App\Jobs\SyncProductElasticsearch;
use App\Jobs\RemoveProductFromElasticsearch;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    public function created(Product $product)
    {
        dispatch(new SyncProductElasticsearch($product));
        
        Cache::tags(['products'])->flush();
    }

    public function updated(Product $product)
    {
        dispatch(new SyncProductElasticsearch($product));
        
        Cache::forget('product_' . $product->id);
        Cache::tags(['products'])->flush();
    }

    public function deleted(Product $product)
    {
        dispatch(new RemoveProductFromElasticsearch($product->id));
        
        Cache::forget('product_' . $product->id);
        Cache::tags(['products'])->flush();
    }

    public function restored(Product $product)
    {
        dispatch(new SyncProductElasticsearch($product));
    }
}
