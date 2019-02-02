<?php namespace Sboo\Laravel5Mailjet;

use Sboo\Laravel5Mailjet\Api\Mailjet;
use Swift_Mailer;
use Illuminate\Mail\Mailer;
use Illuminate\Support\ServiceProvider;

class MailjetServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('mailjet', function($app)
        {
            $config = $app['config']->get('services.mailjet', array());
            return new Mailjet($config['key'], $config['secret']);
        });
    }

    public function boot()
    {
        $this->app['swift.transport']->extend('mailjet', function ($app) {
            return new MailjetTransport($app['mailjet']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['mailjet'];
    }

}
