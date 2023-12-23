<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Route::pattern('id', '[0-9]+');
        Route::pattern('seat_number', '[0-9]+');
        Route::pattern('tid', '[0-9]+');
        Route::pattern('cid', '[0-9]+');
        Route::pattern('tnumber', '[0-9]+');
    }
}
