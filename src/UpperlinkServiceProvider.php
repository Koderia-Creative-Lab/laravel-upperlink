<?php

namespace Koderia\Upperlink;

use Illuminate\Support\ServiceProvider;

class UpperlinkServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/upperlink.php', 'upperlink');

        $this->app->singleton('upperlink', function ($app) {
            $config = $app['config']->get('upperlink', []);

            // Seamless fallback if user has configuration under services.upperlink
            if (empty($config['username']) && $app['config']->has('services.upperlink')) {
                $legacy = $app['config']->get('services.upperlink', []);
                $config['checkout_url'] = $legacy['checkout_url'] ?? $config['checkout_url'];
                $config['requery_url'] = $legacy['requery_url'] ?? $config['requery_url'];
                $config['username'] = $legacy['checkout_username'] ?? ($legacy['username'] ?? null);
                $config['password'] = $legacy['checkout_password'] ?? ($legacy['password'] ?? null);
                $config['merchant_id'] = $legacy['ref'] ?? ($legacy['merchant_id'] ?? null);
                $config['split_mode'] = (bool) ($legacy['split_mode'] ?? $config['split_mode']);
                $config['split_code'] = $legacy['split_code'] ?? $config['split_code'];
                $config['redirect_url'] = $legacy['redirect_url'] ?? $config['redirect_url'];
                $config['convenience_fee'] = (int) ($legacy['convenience_fee'] ?? $config['convenience_fee']);
            }

            return new UpperlinkClient($config);
        });

        $this->app->alias('upperlink', UpperlinkClient::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/upperlink.php' => config_path('upperlink.php'),
            ], 'upperlink-config');
        }
    }
}
