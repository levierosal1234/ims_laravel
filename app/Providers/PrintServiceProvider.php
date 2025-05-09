<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PrintLabelService;

class PrintServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PrintLabelService::class, function ($app) {
            return new PrintLabelService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
