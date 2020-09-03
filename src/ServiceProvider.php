<?php

namespace Sydante\LaravelSensitive;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/sensitive.php' => config_path('sensitive.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(Sensitive::class, function () {
            return new Sensitive(config('sensitive'));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [Sensitive::class, 'sensitive'];
    }
}
