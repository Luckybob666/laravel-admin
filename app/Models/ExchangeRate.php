<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'rate_date','base_currency',
        'usd_to_cny','usd_to_pkr','usd_to_inr',
        'source','is_locked','notes',
      ];
      
      protected $casts = [
        'rate_date'=>'date',
        'is_locked'=>'boolean',
        'usd_to_cny'=>'decimal:6',
        'usd_to_pkr'=>'decimal:6',
        'usd_to_inr'=>'decimal:6',
      ];

    // 添加验证规则
    public static function rules($exchangeRateId = null)
    {
        $uniqueRule = 'unique:exchange_rates,rate_date';
        if ($exchangeRateId) {
            $uniqueRule .= ',' . $exchangeRateId;
        }

        return [
            'rate_date' => ['required', 'date', $uniqueRule],
            'base_currency' => ['required', 'string', 'max:3'],
            'usd_to_cny' => ['required', 'numeric', 'gt:0'],
            'usd_to_pkr' => ['required', 'numeric', 'gt:0'],
            'usd_to_inr' => ['required', 'numeric', 'gt:0'],
            'source' => ['required', 'string', 'max:16'],
            'is_locked' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
      
      public static function ensureDailyDefaults(\Carbon\CarbonInterface $date): void
      {
          static::firstOrCreate(
              ['rate_date'=>$date->toDateString(),'base_currency'=>'USD'],
              ['usd_to_cny'=>7,'usd_to_pkr'=>290,'usd_to_inr'=>87,'source'=>'system']
          );
      }
      
}
