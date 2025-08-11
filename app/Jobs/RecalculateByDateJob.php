<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateByDateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var string YYYY-MM-DD */
    public string $date;

    public function __construct(string $date)
    {
        // 统一标准化，避免时区影响
        $this->date = Carbon::parse($date, 'Asia/Singapore')->toDateString();

        // 可选：指定队列名（如你有多条队列）
        // $this->onQueue('recalc');
        // 可选：指定连接（如果用了 redis/beanstalkd 等）
        // $this->onConnection('redis');
    }

    // 可选：重试次数/退避
    public $tries = 3;
    public function backoff(): int|array { return [10, 60, 300]; } // 秒

    public function handle(): void
    {
        $date = Carbon::createFromFormat('Y-m-d', $this->date, 'Asia/Singapore');

        // TODO: 在这里实现受汇率影响的数据重算
        // 例：
        // app(YourRecalcService::class)->recalcForDate($date);

        // 你也可以按模块拆分：
        // app(FixedExpenseService::class)->recalcForDate($date);
        // app(DailyCostService::class)->recalcForDate($date);
        // app(ReportsService::class)->rebuildDailyAggregates($date);
    }

    // 可选：失败告警/记录
    public function failed(\Throwable $e): void
    {
        // 记录日志或通知
        // \Log::error('Recalculate failed for '.$this->date, ['e'=>$e]);
    }
}
