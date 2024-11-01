<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\JurnalApi;

class JurnalApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(JurnalApi::class, function ($app) {
            return new JurnalApi(
                config('services.jurnal.username'),
                config('services.jurnal.secret'),
                config('services.jurnal.environment')
            );
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
