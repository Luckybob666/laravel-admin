<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\ExchangeRate;
use App\Models\TeamDailyStat;
use App\Models\DailyIpCost;
use App\Observers\ExchangeRateObserver;
use App\Observers\TeamDailyStatObserver;
use App\Observers\DailyIpCostObserver;

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
        //
        ExchangeRate::observe(ExchangeRateObserver::class);
        TeamDailyStat::observe(TeamDailyStatObserver::class);
        DailyIpCost::observe(DailyIpCostObserver::class);
    }
}
