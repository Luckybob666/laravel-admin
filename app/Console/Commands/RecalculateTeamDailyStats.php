<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TeamDailyStat;
use App\Models\DailyIpCost;
use App\Models\FixedExpense;
use App\Models\Team;
use App\Services\TeamDailyStatCalculationService;
use Carbon\Carbon;

class RecalculateTeamDailyStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'team-daily-stats:recalculate 
                            {--date= : 指定日期 (Y-m-d 格式)}
                            {--start-date= : 开始日期 (Y-m-d 格式)}
                            {--end-date= : 结束日期 (Y-m-d 格式)}
                            {--all : 重新计算所有数据}
                            {--dry-run : 仅显示将要进行的操作，不实际执行}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重新计算团队每日数据的固定费用和服务器/IP费用';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('开始重新计算团队每日数据费用...');

        // 确定要处理的日期范围
        $dates = $this->getDatesToProcess();
        
        if (empty($dates)) {
            $this->error('没有找到需要处理的日期');
            return 1;
        }

        $this->info('将处理以下日期: ' . implode(', ', $dates));
        
        if ($this->option('dry-run')) {
            $this->info('DRY RUN 模式 - 不会实际修改数据');
        }

        $totalRecords = 0;
        $updatedRecords = 0;

        $service = new TeamDailyStatCalculationService();
        
        foreach ($dates as $date) {
            $this->info("处理日期: {$date}");
            
            $records = TeamDailyStat::whereDate('date', $date)->get();
            $totalRecords += $records->count();
            
            foreach ($records as $record) {
                $oldFixedCost = $record->fixed_cost;
                $oldServerIpCost = $record->var_server_ip_cost;
                
                // 重新计算费用
                $newFixedCost = $service->computeFixedCost(Carbon::parse($date), $record->team_id);
                $newServerIpCost = $service->computeServerIpCost(Carbon::parse($date), $record->team_id, $record->msg_count);
                
                if ($oldFixedCost != $newFixedCost || $oldServerIpCost != $newServerIpCost) {
                    $this->line("  团队 {$record->team->name}: 固定费用 {$oldFixedCost} -> {$newFixedCost}, 服务器/IP费用 {$oldServerIpCost} -> {$newServerIpCost}");
                    
                    if (!$this->option('dry-run')) {
                        $record->fixed_cost = $newFixedCost;
                        $record->var_server_ip_cost = $newServerIpCost;
                        $record->saveQuietly();
                        $updatedRecords++;
                    }
                }
            }
        }

        $this->info("处理完成！");
        $this->info("总记录数: {$totalRecords}");
        $this->info("更新记录数: {$updatedRecords}");
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN 模式 - 数据未实际更新');
        }

        return 0;
    }

    /**
     * 获取需要处理的日期列表
     */
    protected function getDatesToProcess(): array
    {
        if ($this->option('all')) {
            // 处理所有有团队数据的日期
            return TeamDailyStat::distinct()
                ->pluck('date')
                ->map(fn($date) => $date->format('Y-m-d'))
                ->toArray();
        }

        if ($date = $this->option('date')) {
            // 处理指定日期
            return [$date];
        }

        if ($startDate = $this->option('start-date')) {
            // 处理日期范围
            $endDate = $this->option('end-date') ?? $startDate;
            $dates = [];
            $current = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            while ($current->lte($end)) {
                $dates[] = $current->format('Y-m-d');
                $current->addDay();
            }
            
            return $dates;
        }

        // 默认处理最近7天
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->format('Y-m-d');
        }
        
        return $dates;
    }


}
