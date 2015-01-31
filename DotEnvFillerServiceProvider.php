<?php namespace KDuma\DotEnvFiller;

use Illuminate\Support\ServiceProvider;

/**
 * Class DotEnvFillerServiceProvider
 * @package KDuma\DotEnvFiller
 */
class DotEnvFillerServiceProvider extends ServiceProvider {

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
	public function boot()
	{
		//
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('command.config.env', function()
		{
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
		return array(
			'command.config.env'
		);
	}

}
