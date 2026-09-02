<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\admin_acc\AdminDashboardController;

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

        View::composer('admin_acc.dashboard', function ($view) {
            $data = app(AdminDashboardController::class)->getDashboardData();
            $view->with($data);
        });
    }
}