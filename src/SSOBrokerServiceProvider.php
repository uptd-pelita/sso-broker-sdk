<?php

namespace Baliprov\SSOBroker;

use Baliprov\SSOBroker\Http\Middleware\SSOAuthenticated;
use Baliprov\SSOBroker\Http\Middleware\SSOHasRole;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class SSOBrokerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sso-broker.php',
            'sso-broker'
        );

        // Register the main class as singleton
        $this->app->singleton('sso-broker', function ($app) {
            return new SSOBrokerManager();
        });

        // Bind to interface
        $this->app->bind(
            Contracts\SSOAuthenticatorInterface::class,
            SSOBrokerManager::class
        );

        // Alias the manager class
        $this->app->alias('sso-broker', SSOBrokerManager::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/sso-broker.php' => config_path('sso-broker.php'),
        ], 'sso-broker-config');

        // Publish routes
        $this->publishes([
            __DIR__ . '/../routes/sso.php' => base_path('routes/sso.php'),
        ], 'sso-broker-routes');

        // Register middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('sso.auth', SSOAuthenticated::class);
        $router->aliasMiddleware('sso.role', SSOHasRole::class);

        // Load default routes if enabled
        if (config('sso-broker.load_default_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/sso.php');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'sso-broker',
            SSOBrokerManager::class,
            Contracts\SSOAuthenticatorInterface::class,
        ];
    }
}
