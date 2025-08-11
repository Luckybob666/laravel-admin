<?php

namespace App\Services;

use App\Models\TeamDailyStat;
use App\Models\DailyIpCost;
use App\Models\FixedExpense;
use App\Models\Team;
use Carbon\Carbon;

class TeamDailyStatCalculationService
{
    /**
     * 计算固定费用
     */
    public function computeFixedCost(Carbon $date, int $teamId): float
    {
        $monthlyAmount = (float) (
            FixedExpense::query()
                ->whereYear('month_date', $date->year)
                ->whereMonth('month_date', $date->month)
                ->orderByDesc('month_date')
                ->value('amount') ?? 0
        );

        $daysInMonth = $date->daysInMonth;
        $teamCount = (int) Team::where('is_active', true)->count();

        if ($monthlyAmount <= 0 || $daysInMonth <= 0 || $teamCount <= 0) {
            return 0.00;
        }

        return round($monthlyAmount / $daysInMonth / $teamCount, 2);
    }

    /**
     * 计算服务器/IP费用分摊
     * 算法：
     * 1. 当日有效团队数 = 参与分摊的团队数量
     * 2. 当日单个团队固定费用 = 当月固定费用总额 ÷ 当月天数 ÷ 当日有效团队数
     * 3. 当日单个团队 IP 费用 = 当日公司级 IP 费用 ÷ 当日有效团队数
     * 4. 当日单个团队总费用 = 当日单个团队固定费用 + 当日单个团队 IP 费用
     * 5. 全公司当日消息总数 = 所有团队当日消息数之和
     * 6. 当前团队当日费用占比 = 当前团队当日消息数 ÷ 全公司当日消息总数
     * 7. 全公司当日总费用 = （当月固定费用总额 ÷ 当月天数）+ 当日公司级 IP 费用
     * 8. 当前团队当日分摊费用 = 全公司当日总费用 × 当前团队当日费用占比
     */
    public function computeServerIpCost(Carbon $date, int $teamId, int $msgCount): float
    {
        // 1. 获取当日有效团队数（启用中的团队）
        $activeTeamCount = (int) Team::where('is_active', true)->count();
        
        if ($activeTeamCount <= 0) {
            return 0.00;
        }

        // 2. 获取当月固定费用总额
        $monthlyFixedAmount = (float) (
            FixedExpense::query()
                ->whereYear('month_date', $date->year)
                ->whereMonth('month_date', $date->month)
                ->orderByDesc('month_date')
                ->value('amount') ?? 0
        );

        // 3. 获取当日公司级 IP 费用
        $dailyIpCost = (float) (
            DailyIpCost::query()
                ->whereDate('date', $date->toDateString())
                ->value('amount') ?? 0
        );

        // 4. 计算当日单个团队固定费用
        $daysInMonth = $date->daysInMonth;
        $perTeamFixedCost = ($monthlyFixedAmount > 0 && $daysInMonth > 0 && $activeTeamCount > 0)
            ? $monthlyFixedAmount / $daysInMonth / $activeTeamCount
            : 0.00;

        // 5. 计算当日单个团队 IP 费用
        $perTeamIpCost = ($dailyIpCost > 0 && $activeTeamCount > 0)
            ? $dailyIpCost / $activeTeamCount
            : 0.00;

        // 6. 计算当日单个团队总费用
        $perTeamTotalCost = $perTeamFixedCost + $perTeamIpCost;

        // 7. 获取全公司当日消息总数（包括当前正在编辑的记录）
        $totalMsgCount = TeamDailyStat::query()
            ->whereDate('date', $date->toDateString())
            ->sum('msg_count');
        
        // 如果当前记录已存在，需要减去旧的消息数，加上新的消息数
        $existingRecord = TeamDailyStat::where('date', $date->toDateString())
            ->where('team_id', $teamId)
            ->first();
        
        if ($existingRecord) {
            $totalMsgCount = $totalMsgCount - $existingRecord->msg_count + $msgCount;
        } else {
            $totalMsgCount = $totalMsgCount + $msgCount;
        }

        // 8. 计算当前团队当日费用占比
        if ($totalMsgCount <= 0) {
            // 如果当日没有消息，按团队数量平均分摊
            return round($perTeamTotalCost, 2);
        }

        $teamCostRatio = $msgCount / $totalMsgCount;

        // 9. 计算全公司当日总费用
        $companyDailyTotalCost = ($monthlyFixedAmount / $daysInMonth) + $dailyIpCost;

        // 10. 计算当前团队当日分摊费用
        $teamAllocatedCost = $companyDailyTotalCost * $teamCostRatio;

        return round($teamAllocatedCost, 2);
    }

    /**
     * 重新计算指定日期所有团队的费用
     */
    public function recalculateAllTeamsForDate(Carbon $date): void
    {
        // 获取该日期的所有团队记录
        $records = TeamDailyStat::whereDate('date', $date->toDateString())->get();
        
        foreach ($records as $record) {
            // 重新计算固定费用
            $record->fixed_cost = $this->computeFixedCost($date, $record->team_id);
            
            // 重新计算服务器/IP费用
            $record->var_server_ip_cost = $this->computeServerIpCost($date, $record->team_id, $record->msg_count);
            
            // 保存更新
            $record->saveQuietly(); // 使用 saveQuietly 避免触发观察者循环
        }
    }

    /**
     * 批量重新计算指定日期范围的所有团队费用
     */
    public function recalculateDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $totalRecords = 0;
        $updatedRecords = 0;
        $dates = [];

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        foreach ($dates as $date) {
            $records = TeamDailyStat::whereDate('date', $date)->get();
            $totalRecords += $records->count();
            
            foreach ($records as $record) {
                $oldFixedCost = $record->fixed_cost;
                $oldServerIpCost = $record->var_server_ip_cost;
                
                // 重新计算费用
                $newFixedCost = $this->computeFixedCost(Carbon::parse($date), $record->team_id);
                $newServerIpCost = $this->computeServerIpCost(Carbon::parse($date), $record->team_id, $record->msg_count);
                
                if ($oldFixedCost != $newFixedCost || $oldServerIpCost != $newServerIpCost) {
                    $record->fixed_cost = $newFixedCost;
                    $record->var_server_ip_cost = $newServerIpCost;
                    $record->saveQuietly();
                    $updatedRecords++;
                }
            }
        }

        return [
            'total_records' => $totalRecords,
            'updated_records' => $updatedRecords,
            'dates_processed' => count($dates),
        ];
    }
}
