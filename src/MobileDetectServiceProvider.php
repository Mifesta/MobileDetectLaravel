<?php

namespace MifestaClientBrowserData;

use Illuminate\Support\ServiceProvider;

class MobileDetectServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register()
    {
        ClientBrowserData::useContainer($this->app);

        $this->app->singleton('mifesta.mobiledetect', function () {
            return new ClientBrowserData();
        });
    }

    /**
     * The detector keeps a manual User-Agent until the request changes.
     * Drop that cache when the response is sent and before every queued job,
     * including workers that reuse one request object.
     *
     * @return void
     */
    public function boot()
    {
        if (!is_object($this->app) || !method_exists($this->app, 'bound') || !$this->app->bound('events')) {
            return;
        }

        try {
            $events = $this->app->make('events');
        } catch (\Exception $exception) {
            return;
        }

        if (!is_object($events) || !method_exists($events, 'listen')) {
            return;
        }

        $reset = array('MifestaClientBrowserData\\ClientBrowserData', 'forgetRequest');
        $events->listen('Illuminate\\Foundation\\Http\\Events\\RequestHandled', $reset);
        $events->listen('Illuminate\\Queue\\Events\\JobProcessing', $reset);
    }
}
