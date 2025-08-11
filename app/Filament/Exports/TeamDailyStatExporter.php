<?php

namespace App\Filament\Exports;

use App\Models\TeamDailyStat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TeamDailyStatExporter extends Exporter
{
    protected static ?string $model = TeamDailyStat::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),   // ← 加回这行
            ExportColumn::make('date')->label('日期'),
            ExportColumn::make('team.name')->label('团队'),
            ExportColumn::make('msg_count')->label('消息条数'),
            ExportColumn::make('fixed_cost')->label('固定费用'),
            ExportColumn::make('var_personnel_cost')->label('浮动人员'),
            ExportColumn::make('var_server_ip_cost')->label('服务器/IP'),
            ExportColumn::make('ad_cost')->label('广告费用'),
            ExportColumn::make('unit_price')->label('发送单价'),

            // ✅ 动态列：发送毛利
            ExportColumn::make('daily_gross_profit')
                ->label('发送毛利')
                ->state(function (TeamDailyStat $record): ?float {
                    if ($record->daily_gross_profit !== null) {
                        return round((float) $record->daily_gross_profit, 2);
                    }
                    return round(
                        (float) $record->withdraw_cost
                        - (float) $record->fixed_cost
                        - (float) $record->var_personnel_cost
                        - (float) $record->var_server_ip_cost
                        - (float) $record->ad_cost,
                        2
                    );
                }),

            ExportColumn::make('withdraw_cost')->label('提款金额'),
            ExportColumn::make('online_today')->label('今日在线'),
            ExportColumn::make('online_yesterday')->label('昨日绑定'),

            // ✅ 动态列：增长率
            ExportColumn::make('growth_rate')
                ->label('增长率')
                ->state(function (TeamDailyStat $record): ?string {
                    if ($record->growth_rate !== null) {
                        return round((float) $record->growth_rate, 2) . '%';
                    }
                    $y = (float) $record->online_yesterday;
                    $t = (float) $record->online_today;
                    return $y > 0 ? round(($t - $y) / $y * 100, 2) . '%' : null;
                }),

            ExportColumn::make('created_at')->label('创建时间'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your team daily stat export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
