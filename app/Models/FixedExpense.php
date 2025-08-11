<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FixedExpense extends Model
{
    protected $fillable = ['month_date', 'amount', 'note'];

    protected $casts = [
        'month_date' => 'date',
    ];

    // 添加验证规则
    public static function rules($fixedExpenseId = null)
    {
        $uniqueRule = 'unique:fixed_expenses,month_date';
        if ($fixedExpenseId) {
            $uniqueRule .= ',' . $fixedExpenseId;
        }

        return [
            'month_date' => ['required', 'date', $uniqueRule],
            'amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected static function booted(): void
    {
        // 新增/更新后重算
        static::saved(function (FixedExpense $fx) {
            self::recalculateMonth($fx->month_date);
        });

        // 删除后也重算（可能没有该月固定费用了）
        static::deleted(function (FixedExpense $fx) {
            self::recalculateMonth($fx->month_date);
        });
    }

    /**
     * 重新计算并批量更新 "该月所有 TeamDailyStat.fixed_cost"
     */
    protected static function recalculateMonth(Carbon|string $monthDate): void
    {
        $monthStart = Carbon::parse($monthDate)->startOfMonth();
        $monthEnd   = Carbon::parse($monthDate)->endOfMonth();

        // 1) 取当月固定费用（若一个月可能多条，可改为 ->sum('amount')）
        $monthlyAmount = (float) (
            static::query()
                ->whereBetween('month_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->latest('month_date')
                ->value('amount') ?? 0
        );

        // 2) 当月天数
        $daysInMonth = $monthStart->daysInMonth;

        // 3) 启用团队数量（如需包含停用团队，改成 Team::count()）
        $teamCount = (int) \App\Models\Team::where('is_active', true)->count();

        // 4) 计算当日每个团队的固定费用
        $perDayPerTeam = ($monthlyAmount > 0 && $daysInMonth > 0 && $teamCount > 0)
            ? round($monthlyAmount / $daysInMonth / $teamCount, 2)
            : 0.00;

        // 5) 批量更新该月全部 TeamDailyStat
        \App\Models\TeamDailyStat::query()
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->update(['fixed_cost' => $perDayPerTeam]);
    }
}
