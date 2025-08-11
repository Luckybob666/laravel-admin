<?php

namespace App\Observers;

use App\Models\DailyIpCost;
use App\Models\TeamDailyStat;
use App\Services\TeamDailyStatCalculationService;
use Carbon\Carbon;

class DailyIpCostObserver
{
    /**
     * Handle the DailyIpCost "created" event.
     */
    public function created(DailyIpCost $dailyIpCost): void
    {
        // 当创建新的IP费用记录时，重新计算该日期所有团队的费用
        $this->recalculateAllTeamsForDate($dailyIpCost->date);
    }

    /**
     * Handle the DailyIpCost "updated" event.
     */
    public function updated(DailyIpCost $dailyIpCost): void
    {
        // 当更新IP费用记录时，重新计算该日期所有团队的费用
        $this->recalculateAllTeamsForDate($dailyIpCost->date);
    }

    /**
     * Handle the DailyIpCost "deleted" event.
     */
    public function deleted(DailyIpCost $dailyIpCost): void
    {
        // 当删除IP费用记录时，重新计算该日期所有团队的费用
        $this->recalculateAllTeamsForDate($dailyIpCost->date);
    }

    /**
     * Handle the DailyIpCost "restored" event.
     */
    public function restored(DailyIpCost $dailyIpCost): void
    {
        //
    }

    /**
     * Handle the DailyIpCost "force deleted" event.
     */
    public function forceDeleted(DailyIpCost $dailyIpCost): void
    {
        //
    }

    /**
     * 重新计算指定日期所有团队的费用
     */
    protected function recalculateAllTeamsForDate(Carbon $date): void
    {
        $service = new TeamDailyStatCalculationService();
        $service->recalculateAllTeamsForDate($date);
    }


}
