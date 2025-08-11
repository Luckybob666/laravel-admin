<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyIpCost extends Model
{
    protected $fillable = [
        'date',
        'amount',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    // 添加验证规则
    public static function rules($dailyIpCostId = null)
    {
        $uniqueRule = 'unique:daily_ip_costs,date';
        if ($dailyIpCostId) {
            $uniqueRule .= ',' . $dailyIpCostId;
        }

        return [
            'date' => ['required', 'date', $uniqueRule],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
