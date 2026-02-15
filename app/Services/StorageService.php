<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StorageService
{
    /**
     * Upload file to storage (S3 or local fallback)
     */
    public function upload(UploadedFile $file, string $path, string $disk = null): array
    {
        $disk = $disk ?? $this->getDefaultDisk();
        
        try {
            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $fullPath = $path . '/' . $filename;
            // dd($disk, $path, $filename);

            // Upload file
            $uploaded = Storage::disk($disk)->putFileAs($path, $file, $filename);

            if (!$uploaded) {
                throw new \Exception('Failed to upload file');
            }

            // Get URL
            $url = Storage::disk($disk)->url($fullPath);

            Log::info('File uploaded successfully', [
                'disk' => $disk,
                'path' => $fullPath,
                'url' => $url
            ]);

            return [
                'success' => true,
                'disk' => $disk,
                'path' => $fullPath,
                'url' => $url,
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            // Fallback to local storage if S3 fails
            if ($disk === 's3') {
                Log::warning('Falling back to local storage');
                return $this->upload($file, $path, 'public');
            }

            throw $e;
        }
    }

    /**
     * Delete file from storage
     */
    public function delete(string $path, string $disk = null): bool
    {
        $disk = $disk ?? $this->getDefaultDisk();

        try {
            $deleted = Storage::disk($disk)->delete($path);

            Log::info('File deleted', [
                'disk' => $disk,
                'path' => $path,
                'success' => $deleted
            ]);

            return $deleted;

        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Check if file exists
     */
    public function exists(string $path, string $disk = null): bool
    {
        $disk = $disk ?? $this->getDefaultDisk();

        try {
            return Storage::disk($disk)->exists($path);
        } catch (\Exception $e) {
            Log::error('File existence check failed', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get file URL
     */
    public function url(string $path, ?string $disk = null): ?string
    {
        $disk ??= $this->getDefaultDisk();

        try {
            return Storage::disk($disk)->url($path);
        } catch (\Exception $e) {
            Log::error('Failed to get file URL', [
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get default disk
     */
    protected function getDefaultDisk(): string
    {
        return config('filesystems.default', 'local');
    }

    /**
     * Check if S3 is configured
     */
    public function isS3Configured(): bool
    {
        return !empty(config('filesystems.disks.s3.key')) &&
               !empty(config('filesystems.disks.s3.secret')) &&
               !empty(config('filesystems.disks.s3.bucket'));
    }

    /**
     * Get storage info
     */
    public function getStorageInfo(): array
    {
        $disk = $this->getDefaultDisk();
        $s3Configured = $this->isS3Configured();

        return [
            'default_disk' => $disk,
            's3_configured' => $s3Configured,
            's3_available' => $s3Configured && $disk === 's3',
            'fallback_disk' => 'public'
        ];
    }
}
