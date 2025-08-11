<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'leader_id',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    // 添加验证规则
    public static function rules($teamId = null)
    {
        $uniqueRule = 'unique:teams,name';
        if ($teamId) {
            $uniqueRule .= ',' . $teamId;
        }

        return [
            'name' => ['required', 'string', 'max:100', $uniqueRule],
            'code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'leader_id' => ['nullable', 'exists:users,id'],
            'settings' => ['nullable', 'array'],
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (\App\Models\Team $team) {
            if ($team->wasChanged('is_active')) {
                // 只重算"当前月"；如果你想重算多个历史月份，可以自行扩展
                \App\Models\FixedExpense::recalculateMonth(now()->startOfMonth());
            }
        });
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
