<?php

namespace App\Observers;

use App\Models\ExchangeRate;
use App\Jobs\RecalculateByDateJob;

class ExchangeRateObserver
{
    /**
     * Handle the ExchangeRate "created" event.
     */
    public function created(ExchangeRate $exchangeRate): void
    {
        //
    }

    /**
     * Handle the ExchangeRate "updated" event.
     */
    public function updated(ExchangeRate $rate): void
    {
        if ($rate->wasChanged(['usd_to_cny','usd_to_pkr','usd_to_inr'])) {
            // 任意一个币种变动就触发重算
            dispatch(new RecalculateByDateJob($rate->rate_date->toDateString()));
            // 或者指定队列： dispatch((new RecalculateByDateJob(...))->onQueue('recalc'));
        }
    }

    /**
     * Handle the ExchangeRate "deleted" event.
     */
    public function deleted(ExchangeRate $exchangeRate): void
    {
        //
    }

    /**
     * Handle the ExchangeRate "restored" event.
     */
    public function restored(ExchangeRate $exchangeRate): void
    {
        //
    }

    /**
     * Handle the ExchangeRate "force deleted" event.
     */
    public function forceDeleted(ExchangeRate $exchangeRate): void
    {
        //
    }
}
