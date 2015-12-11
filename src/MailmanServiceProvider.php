<?php namespace Waavi\Mailman;

use Illuminate\Support\ServiceProvider;

class MailmanServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../resources/config/mailman.php' => config_path('mailman.php'),
        ]);
        $this->mergeConfigFrom(
            __DIR__ . '/../../resources/config/mailman.php', 'mailman'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('mailman', function ($app) {
            return new MailerFactory($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

}
