<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceService
{
    /**
     * Cache data with intelligent TTL
     */
    public static function cacheData(string $key, $data, int $ttl = 3600): void
    {
        if (config('optimization.cache.enabled', true)) {
            Cache::put($key, $data, $ttl);
        }
    }

    /**
     * Get cached data or execute callback
     */
    public static function remember(string $key, $callback, int $ttl = 3600)
    {
        if (config('optimization.cache.enabled', true)) {
            return Cache::remember($key, $ttl, $callback);
        }
        
        return $callback();
    }

    /**
     * Optimize database queries
     */
    public static function optimizeQueries(): void
    {
        // Disable query logging in production
        if (!app()->environment('local')) {
            DB::connection()->disableQueryLog();
        }
    }

    /**
     * Monitor memory usage
     */
    public static function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Optimize images
     */
    public static function optimizeImage(string $path): string
    {
        // Add image optimization logic here
        return $path;
    }

    /**
     * Generate cache key based on request
     */
    public static function generateCacheKey(string $prefix, array $params = []): string
    {
        $key = $prefix;
        
        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }
        
        return $key;
    }

    /**
     * Clear specific cache keys
     */
    public static function clearCache(array $keys = []): void
    {
        if (empty($keys)) {
            Cache::flush();
        } else {
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Optimize response size
     */
    public static function optimizeResponse($response): void
    {
        if (config('optimization.compression.enabled', true)) {
            // Add compression logic here
        }
    }

    /**
     * Log performance metrics
     */
    public static function logPerformance(string $operation, float $duration, array $context = []): void
    {
        if ($duration > 1000) { // Log operations taking more than 1 second
            Log::warning('Slow operation detected', [
                'operation' => $operation,
                'duration' => round($duration, 2) . 'ms',
                'memory' => self::getMemoryUsage(),
                'context' => $context,
            ]);
        }
    }
} 