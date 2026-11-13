<?php

namespace App\Providers;

use App\Services\CleanlinessIndex;
use App\Services\CollectionPlanner;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CleanlinessIndex::class);
        $this->app->singleton(CollectionPlanner::class);
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'French');
        Paginator::useBootstrapFive();
    }
}
