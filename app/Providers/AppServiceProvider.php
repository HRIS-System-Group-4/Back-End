<?php

namespace App\Providers;

use Xendit\Xendit;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Xendit::setApiKey(env('XENDIT_SECRET_API_KEY'));
    }
}
