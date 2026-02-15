<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ProcessProductImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product $product,
        public string $imagePath
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing product image', [
                'product_id' => $this->product->id,
                'path' => $this->imagePath
            ]);

            // Get image from S3
            $imageContent = Storage::disk('s3')->get($this->imagePath);
            
            // Create different sizes
            $sizes = [
                'thumbnail' => 150,
                'medium' => 500,
                'large' => 1200
            ];

            foreach ($sizes as $sizeName => $width) {
                $image = Image::read($imageContent);
                
                // Resize maintaining aspect ratio
                $image->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                // Optimize quality
                $image->encode('jpg', 85);

                // Generate path
                $pathInfo = pathinfo($this->imagePath);
                $newPath = $pathInfo['dirname'] . '/' . $sizeName . '_' . $pathInfo['basename'];

                // Upload to S3
                Storage::disk('s3')->put($newPath, $image->stream(), 'public');

                Log::info('Image size created', [
                    'product_id' => $this->product->id,
                    'size' => $sizeName,
                    'path' => $newPath
                ]);
            }

            Log::info('Product image processed successfully', [
                'product_id' => $this->product->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process product image', [
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
        Log::error('Product image processing job failed', [
            'product_id' => $this->product->id,
            'path' => $this->imagePath,
            'error' => $exception->getMessage()
        ]);
    }
}
