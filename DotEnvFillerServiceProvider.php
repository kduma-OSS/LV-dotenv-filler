<?php

namespace KDuma\DotEnvFiller;

use Illuminate\Support\ServiceProvider;

/**
 * Class DotEnvFillerServiceProvider.
 */
class DotEnvFillerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.config.env', function () {
            return new DotEnvFillerCommand();
        });

        $this->commands('command.config.env');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.config.env',
        ];
    }
}
