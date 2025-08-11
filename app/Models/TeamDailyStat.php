<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Team;

class TeamDailyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'team_id',
        'fixed_cost',
        'var_personnel_cost',
        'var_server_ip_cost',
        'ad_cost',
        'msg_count',
        'unit_price',
        'withdraw_cost',
        'online_today',
        'online_yesterday',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'fixed_cost' => 'decimal:2',
        'var_personnel_cost' => 'decimal:2',
        'var_server_ip_cost' => 'decimal:2',
        'ad_cost' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'withdraw_cost' => 'decimal:2',
        'online_today' => 'integer',
        'online_yesterday' => 'integer',
    ];

    // 添加验证规则
    public static function rules($teamDailyStatId = null)
    {
        $uniqueRule = 'unique:team_daily_stats,date,team_id';
        if ($teamDailyStatId) {
            $uniqueRule .= ',' . $teamDailyStatId;
        }

        return [
            'date' => ['required', 'date', $uniqueRule],
            'team_id' => ['required', 'exists:teams,id'],
            'fixed_cost' => ['required', 'numeric', 'min:0'],
            'var_personnel_cost' => ['required', 'numeric', 'min:0'],
            'var_server_ip_cost' => ['required', 'numeric', 'min:0'],
            'ad_cost' => ['required', 'numeric', 'min:0'],
            'msg_count' => ['required', 'integer', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'withdraw_cost' => ['required', 'numeric', 'min:0'],
            'online_today' => ['nullable', 'integer', 'min:0'],
            'online_yesterday' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    protected $appends = ['growth_rate','daily_gross_profit',];
    //增长率
    public function getGrowthRateAttribute(): ?float
    {
        $y = (int) $this->online_yesterday;
        if ($y <= 0) {
            return null; // 或者返回 0，看你业务需要
        }

        $t = (int) $this->online_today;
        return round((($t - $y) / $y) * 100, 2); // 单位：百分比
    }

    // protected $appends = ['daily_gross_profit'];
    //每日毛利
    public function getDailyGrossProfitAttribute(): ?float
    {
        // 获取当日汇率（假设有 exchange_rates 表，rate_date 是日期字段）
        $rate = \App\Models\ExchangeRate::query()
        ->whereDate('rate_date', $this->date)
        ->where('base_currency', 'USD')
        ->value('usd_to_cny');
    
        if (empty($rate) || $rate == 0) {
            return null; // 没有汇率数据，返回空
        }
    
        // 计算：消息数 × 单价 ÷ 汇率
        return round(($this->msg_count * $this->unit_price) / $rate, 2);
    }
}
