<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Product;
use App\Observers\ProductObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Elasticsearch Client
        $this->app->singleton(\Elastic\Elasticsearch\Client::class, function ($app) {
            $host = env('ELASTICSEARCH_HOST', 'elasticsearch');
            $port = env('ELASTICSEARCH_PORT', '9200');
            $scheme = env('ELASTICSEARCH_SCHEME', 'http');
            
            return \Elastic\Elasticsearch\ClientBuilder::create()
                ->setHosts(["{$scheme}://{$host}:{$port}"])
                ->build();
        });

        // Bind PSR HTTP Client for Elasticsearch
        $this->app->bind(\Psr\Http\Client\ClientInterface::class, function () {
            return new \GuzzleHttp\Client();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);
    }
}
