<?php

namespace Kaoken\LaravelConfirmation;

use Illuminate\Support\ServiceProvider;

class ConfirmationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * The basic path of the library here.
     * @param string $path
     * @return string
     */
    protected function my_base_path($path='')
    {
        return __DIR__.'/../'.$path;
    }

    /**
     * The basic path of the library here.
     * @param string $path
     * @return string
     */
    protected function my_resources_path($path='')
    {
        return $this->my_base_path('resources/'.$path);
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->my_resources_path('views') => resource_path('views/vendor/confirmation'),
                $this->my_resources_path('lang') => resource_path('lang'),
                $this->my_base_path('database/migrations') => database_path('migrations'),
            ], 'confirmation');
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfirmationBroker();
    }

    /**
     * Register the confirmation broker instance.
     *
     * @return void
     */
    protected function registerConfirmationBroker()
    {
        $this->app->singleton('auth.confirmation', function ($app) {
            return new ConfirmationBrokerManager($app);
        });

        $this->app->bind('auth.confirmation.broker', function ($app) {
            return $app->make('auth.confirmation')->broker();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['auth.confirmation', 'auth.confirmation.broker'];
    }
}
