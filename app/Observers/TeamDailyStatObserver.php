<?php

namespace App\Observers;

use App\Models\TeamDailyStat;
use App\Services\TeamDailyStatCalculationService;
use Carbon\Carbon;

class TeamDailyStatObserver
{
    /**
     * Handle the TeamDailyStat "created" event.
     */
    public function created(TeamDailyStat $teamDailyStat): void
    {
        // 当创建新记录时，重新计算同一天所有团队的费用
        $this->recalculateAllTeamsForDate($teamDailyStat->date);
    }

    /**
     * Handle the TeamDailyStat "updated" event.
     */
    public function updated(TeamDailyStat $teamDailyStat): void
    {
        // 当更新记录时，重新计算同一天所有团队的费用
        $this->recalculateAllTeamsForDate($teamDailyStat->date);
    }

    /**
     * Handle the TeamDailyStat "deleted" event.
     */
    public function deleted(TeamDailyStat $teamDailyStat): void
    {
        // 当删除记录时，重新计算同一天所有团队的费用
        $this->recalculateAllTeamsForDate($teamDailyStat->date);
    }

    /**
     * Handle the TeamDailyStat "restored" event.
     */
    public function restored(TeamDailyStat $teamDailyStat): void
    {
        //
    }

    /**
     * Handle the TeamDailyStat "force deleted" event.
     */
    public function forceDeleted(TeamDailyStat $teamDailyStat): void
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
