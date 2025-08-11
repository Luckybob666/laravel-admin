<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamDailyStatResource\Pages;
use App\Models\TeamDailyStat;
use App\Models\Team;
use App\Models\FixedExpense;
use App\Models\DailyIpCost;
use App\Services\TeamDailyStatCalculationService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Filament\Exports\TeamDailyStatExporter;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\Action;

class TeamDailyStatResource extends Resource
{
    protected static ?string $model = TeamDailyStat::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = '业务数据';
    protected static ?int $navigationSort = 20;
    protected static ?string $modelLabel = '团队每日数据';
    protected static ?string $pluralModelLabel = '团队每日数据';

    /** 检查权限（viewer 只读，其他角色可写）*/
    protected static function canManage(): bool
    {
        return auth()->check() && ! auth()->user()->hasRole('viewer');
    }

    /** 以下几个方法都调用 canManage() */
    public static function shouldRegisterNavigation(): bool { return auth()->check(); }
    public static function canViewAny(): bool               { return auth()->check(); }
    public static function canCreate(): bool                { return static::canManage(); }
    public static function canEdit($record): bool           { return static::canManage(); }
    public static function canDelete($record): bool         { return static::canManage(); }
    public static function canDeleteAny(): bool             { return static::canManage(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\DatePicker::make('date')
                ->label('日期')
                ->required()
                ->displayFormat('Y-m-d')
                ->format('Y-m-d')
                ->native(false)
                ->reactive()
                ->disabled(fn($context) => $context === 'edit')
                ->required()
                ->validationMessages([
                    'required' => '日期不能为空',
                ])
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    // $state 就是更新后的 date
                    $date   = $state;
                    $teamId = (int) $get('team_id');
                    $msgCount = (int) $get('msg_count');
                    $service = new TeamDailyStatCalculationService();
                    $set('fixed_cost', $date && $teamId ? $service->computeFixedCost(\Carbon\Carbon::parse($date), $teamId) : 0);
                    $set('var_server_ip_cost', $date && $teamId ? $service->computeServerIpCost(\Carbon\Carbon::parse($date), $teamId, $msgCount) : 0);
                }),
            
                Forms\Components\Select::make('team_id')
                ->label('团队')
                ->relationship('team', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->native(false)
                ->reactive()
                ->disabled(fn($context) => $context === 'edit')
                ->validationMessages([
                    'required' => '请选择团队',
                    'exists' => '选择的团队不存在',
                ])
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    // $state 就是更新后的 team_id
                    $teamId = (int) $state;
                    $date   = $get('date');
                    $msgCount = (int) $get('msg_count');
                    $service = new TeamDailyStatCalculationService();
                    $set('fixed_cost', $date && $teamId ? $service->computeFixedCost(\Carbon\Carbon::parse($date), $teamId) : 0);
                    $set('var_server_ip_cost', $date && $teamId ? $service->computeServerIpCost(\Carbon\Carbon::parse($date), $teamId, $msgCount) : 0);
                }),
            
            
            ])->columns(2),

            Forms\Components\Section::make('费用与业务量')->schema([
                TextInput::make('fixed_cost')
                ->label('固定费用(美金)')
                ->prefix('$')
                ->numeric()
                ->step('0.01')
                ->default(0)
                ->disabled()
                ->dehydrated()
                ->afterStateHydrated(function (Set $set, Get $get) {
                    $date   = $get('date');
                    $teamId = (int) $get('team_id');
                    if ($date && $teamId) {
                        $service = new TeamDailyStatCalculationService();
                        $set('fixed_cost', $service->computeFixedCost(\Carbon\Carbon::parse($date), $teamId));
                    }
                }),

                TextInput::make('var_personnel_cost')
                    ->label('浮动人员费用(美金)')
                    ->numeric()
                    ->default(0)
                    ->validationMessages([
                        'required' => '浮动人员费用不能为空',
                        'numeric' => '浮动人员费用必须是数字',
                        'min' => '浮动人员费用不能小于0',
                    ]),
                    
                TextInput::make('var_server_ip_cost')
                    ->label('服务器与IP费用(美金)')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated()
                    ->afterStateHydrated(function (Set $set, Get $get) {
                        $date   = $get('date');
                        $teamId = (int) $get('team_id');
                        $msgCount = (int) $get('msg_count');
                        if ($date && $teamId) {
                            $service = new TeamDailyStatCalculationService();
                            $set('var_server_ip_cost', $service->computeServerIpCost(\Carbon\Carbon::parse($date), $teamId, $msgCount));
                        }
                    })
                    ->validationMessages([
                        'required' => '服务器与IP费用不能为空',
                        'numeric' => '服务器与IP费用必须是数字',
                        'min' => '服务器与IP费用不能小于0',
                    ]),
                    
                TextInput::make('ad_cost')
                    ->label('推广广告费用(人民币)')
                    ->numeric()
                    ->default(0)
                    ->validationMessages([
                        'required' => '推广广告费用不能为空',
                        'numeric' => '推广广告费用必须是数字',
                        'min' => '推广广告费用不能小于0',
                    ]),

                TextInput::make('msg_count')
                    ->label('发送消息条数')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->reactive()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $msgCount = (int) $state;
                        $date   = $get('date');
                        $teamId = (int) $get('team_id');
                        $service = new TeamDailyStatCalculationService();
                        $set('var_server_ip_cost', $date && $teamId ? $service->computeServerIpCost(\Carbon\Carbon::parse($date), $teamId, $msgCount) : 0);
                    })
                    ->validationMessages([
                        'required' => '发送消息条数不能为空',
                        'numeric' => '发送消息条数必须是数字',
                        'min' => '发送消息条数不能小于0',
                    ]),
                    
                TextInput::make('unit_price')
                    ->label('发送单价')
                    ->numeric()
                    ->nullable()
                    ->validationMessages([
                        'numeric' => '发送单价必须是数字',
                        'min' => '发送单价不能小于0',
                    ]),
                    
                TextInput::make('withdraw_cost')
                    ->label('挂机提款支出')
                    ->numeric()
                    ->default(0)
                    ->validationMessages([
                        'required' => '挂机提款支出不能为空',
                        'numeric' => '挂机提款支出必须是数字',
                        'min' => '挂机提款支出不能小于0',
                    ]),
            ])->columns(3),

            Forms\Components\Section::make('在线人数')->schema([
                TextInput::make('online_today')
                    ->label('今日在线人数')
                    ->numeric()
                    ->minValue(0)
                    ->validationMessages([
                        'numeric' => '今日在线人数必须是数字',
                        'min' => '今日在线人数不能小于0',
                    ]),
                    
                TextInput::make('online_yesterday')
                    ->label('昨日在线绑定人数')
                    ->numeric()
                    ->minValue(0)
                    ->validationMessages([
                        'numeric' => '昨日在线绑定人数必须是数字',
                        'min' => '昨日在线绑定人数不能小于0',
                    ]),
            ])->columns(2),

            Forms\Components\Textarea::make('notes')->label('备注')->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('date')->label('日期')->date('Y-m-d')->sortable()->toggleable(),
                TextColumn::make('team.name')->label('团队')->sortable()->searchable(),
                TextColumn::make('msg_count')->label('消息条数')->sortable()->numeric()->alignment(\Filament\Support\Enums\Alignment::End),
                TextColumn::make('fixed_cost')->label('固定费用')->prefix('$')->alignment(\Filament\Support\Enums\Alignment::End),
                TextColumn::make('var_personnel_cost')->label('浮动人员')->sortable()->prefix('$')->alignment(\Filament\Support\Enums\Alignment::End)->toggleable(),
                TextColumn::make('var_server_ip_cost')->label('服务器/IP')->sortable()->prefix('$')->alignment(\Filament\Support\Enums\Alignment::End)->toggleable(),
                TextColumn::make('ad_cost')->label('广告费用')->sortable()->prefix('$')->alignment(\Filament\Support\Enums\Alignment::End)->toggleable(),
                TextColumn::make('unit_price')->label('发送单价')->sortable()->numeric(4)->alignment(\Filament\Support\Enums\Alignment::End)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('daily_gross_profit')->label('发送毛利')->formatStateUsing(fn ($state) => is_null($state) ? '-' : number_format($state, 2))->alignRight(),
                TextColumn::make('withdraw_cost')->label('提款金额')->sortable()->prefix('$')->alignment(\Filament\Support\Enums\Alignment::End)->toggleable(),
                TextColumn::make('online_today')->label('今日在线')->sortable()->alignment(\Filament\Support\Enums\Alignment::End)->toggleable(),
                TextColumn::make('online_yesterday')->label('昨日绑定')->sortable()->alignment(\Filament\Support\Enums\Alignment::End)->toggleable(),
                TextColumn::make('growth_rate')->label('增长率')->formatStateUsing(fn ($state) => is_null($state) ? '-' : number_format($state, 2) . '%')->alignRight(),
                TextColumn::make('created_at')->label('创建时间')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->label('日期范围')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('开始')->displayFormat('Y-m-d'),
                        Forms\Components\DatePicker::make('until')->label('结束')->displayFormat('Y-m-d'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn($q, $from) => $q->whereDate('date', '>=', $from))
                            ->when($data['until'] ?? null, fn($q, $until) => $q->whereDate('date', '<=', $until));
                    }),

                Tables\Filters\SelectFilter::make('team_id')
                    ->label('团队')
                    ->relationship('team', 'name')
                    ->preload()
                    ->searchable(),
            ])

            ->headerActions([
                ExportAction::make()
                    ->exporter(TeamDailyStatExporter::class)
                    ->fileName(fn () => 'team-daily-' . now()->format('Ymd-His')),
                    
                Action::make('recalculate')
                    ->label('批量重新计算')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn () => static::canManage())
                    ->modalHeading('批量重新计算费用')
                    ->modalDescription('此操作将重新计算所有团队的费用数据。建议在录入完当天所有团队数据后执行。')
                    ->modalSubmitActionLabel('开始重新计算')
                    ->modalCancelActionLabel('取消')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('重新计算日期')
                            ->default(now()->toDateString())
                            ->required()
                            ->displayFormat('Y-m-d')
                            ->validationMessages([
                                'required' => '请选择要重新计算的日期',
                            ]),
                        Forms\Components\Toggle::make('confirm')
                            ->label('我确认要重新计算该日期的所有团队费用数据')
                            ->required()
                            ->validationMessages([
                                'required' => '请确认操作',
                            ]),
                    ])
                    ->action(function (array $data) {
                        $date = Carbon::parse($data['date']);
                        $service = new TeamDailyStatCalculationService();
                        
                        // 获取该日期的所有团队记录
                        $records = \App\Models\TeamDailyStat::whereDate('date', $date->toDateString())->get();
                        
                        if ($records->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('没有找到数据')
                                ->body("{$date->format('Y-m-d')} 没有找到团队数据记录")
                                ->send();
                            return;
                        }
                        
                        $updatedCount = 0;
                        foreach ($records as $record) {
                            $oldFixedCost = $record->fixed_cost;
                            $oldServerIpCost = $record->var_server_ip_cost;
                            
                            // 重新计算费用
                            $newFixedCost = $service->computeFixedCost($date, $record->team_id);
                            $newServerIpCost = $service->computeServerIpCost($date, $record->team_id, $record->msg_count);
                            
                            if ($oldFixedCost != $newFixedCost || $oldServerIpCost != $newServerIpCost) {
                                $record->fixed_cost = $newFixedCost;
                                $record->var_server_ip_cost = $newServerIpCost;
                                $record->saveQuietly();
                                $updatedCount++;
                            }
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('重新计算完成')
                            ->body("已重新计算 {$date->format('Y-m-d')} 的 {$updatedCount} 条记录")
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function () {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('确认删除')
                            ->body('删除团队每日数据将同时删除相关的所有数据，此操作不可恢复。')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->modalHeading('确认批量删除')
                    ->modalDescription('删除团队每日数据将同时删除相关的所有数据，此操作不可恢复。确定要删除选中的记录吗？')
                    ->modalSubmitActionLabel('确认删除')
                    ->visible(fn() => static::canManage()),
            ])
            ->selectCurrentPageOnly();
    }


    /**
     * 表单提交时的兜底：统一计算 fixed_cost 和 var_server_ip_cost，防止联动没触发等边缘情况
     */
    public static function mutateFormDataUsing(array $data): array
    {
        if (!empty($data['date']) && !empty($data['team_id'])) {
            $service = new TeamDailyStatCalculationService();
            $data['fixed_cost'] = $service->computeFixedCost(
                Carbon::parse($data['date']),
                (int) $data['team_id']
            );
            
            $data['var_server_ip_cost'] = $service->computeServerIpCost(
                Carbon::parse($data['date']),
                (int) $data['team_id'],
                (int) ($data['msg_count'] ?? 0)
            );
        } else {
            $data['fixed_cost'] = 0.00;
            $data['var_server_ip_cost'] = 0.00;
        }
        return $data;
    }


    
    
    
    

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeamDailyStats::route('/'),
            'create' => Pages\CreateTeamDailyStat::route('/create'),
            'edit'   => Pages\EditTeamDailyStat::route('/{record}/edit'),
        ];
    }
}
