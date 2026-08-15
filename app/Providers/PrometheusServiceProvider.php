<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CollectorRegistry::class, function ($app) {
            // Local development / Cloudflare Quick Tunnels should not require a
            // native Redis extension or a running Redis server just to render pages.
            if ($app->environment(['local', 'testing'])) {
                return new CollectorRegistry(new InMemory());
            }

            $redis = new Redis([
                'host' => config('database.redis.default.host'),
                'port' => config('database.redis.default.port'),
                'password' => config('database.redis.default.password'),
                'timeout' => 5.0,
                'read_timeout' => 10,
                'persistent_connections' => false,
            ]);

            return new CollectorRegistry($redis);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
