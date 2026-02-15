<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     * 
     * @GET /api/health
     */
    public function index(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'services' => []
        ];

        // Check Database
        try {
            DB::connection()->getPdo();
            $health['services']['database'] = [
                'status' => 'up',
                'connection' => config('database.default')
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['services']['database'] = [
                'status' => 'down',
                'error' => $e->getMessage()
            ];
        }

        // Check Redis/Cache
        try {
            Cache::put('health_check', true, 10);
            $health['services']['cache'] = [
                'status' => 'up',
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            $health['services']['cache'] = [
                'status' => 'down',
                'error' => $e->getMessage()
            ];
        }

        // Check Storage
        try {
            $storageService = app(StorageService::class);
            $storageInfo = $storageService->getStorageInfo();
            
            $health['services']['storage'] = [
                'status' => 'up',
                'default_disk' => $storageInfo['default_disk'],
                's3_configured' => $storageInfo['s3_configured'],
                's3_available' => $storageInfo['s3_available']
            ];
        } catch (\Exception $e) {
            $health['services']['storage'] = [
                'status' => 'down',
                'error' => $e->getMessage()
            ];
        }

        // Check Elasticsearch (Scout)
        try {
            $health['services']['elasticsearch'] = [
                'status' => 'up',
                'driver' => config('scout.driver')
            ];
        } catch (\Exception $e) {
            $health['services']['elasticsearch'] = [
                'status' => 'down',
                'error' => $e->getMessage()
            ];
        }

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Readiness check endpoint
     * 
     * @GET /api/ready
     */
    public function ready(): JsonResponse
    {
        try {
            // Check if database is accessible
            DB::connection()->getPdo();
            
            return response()->json([
                'status' => 'ready',
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'not ready',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String()
            ], 503);
        }
    }

    /**
     * Liveness check endpoint
     * 
     * @GET /api/live
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
