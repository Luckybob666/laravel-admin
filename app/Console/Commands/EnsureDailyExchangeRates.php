<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExchangeRate;
use Carbon\Carbon;

class EnsureDailyExchangeRates extends Command
{
    protected $signature = 'rates:ensure-daily {date? : YYYY-MM-DD（可选，不传则用今天）}';
    protected $description = '确保指定日期存在默认汇率（CNY/PKR/INR），不存在则创建。';

    public function handle(): int
    {
        $date = $this->argument('date')
            ? Carbon::parse($this->argument('date'))
            : now('Asia/Singapore')->startOfDay();

        ExchangeRate::ensureDailyDefaults($date);
        $this->info("Ensured defaults for {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
