<?php

namespace App\Providers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(255);
        // Use Bootstrap 5 pagination views
        Paginator::useBootstrapFive();

        AuthenticationException::redirectUsing(function ($request) { return '/login'; });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
