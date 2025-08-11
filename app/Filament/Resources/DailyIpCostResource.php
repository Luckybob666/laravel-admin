<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyIpCostResource\Pages;
use App\Models\DailyIpCost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyIpCostResource extends Resource
{
    protected static ?string $model = DailyIpCost::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationGroup = '基础档案';
    protected static ?string $modelLabel = '每日IP费用';
    protected static ?string $pluralModelLabel = '每日IP费用';

    /** 检查权限（这里用角色判断，也可以换成权限判断） */
    protected static function allowed(): bool
    {
        return auth()->check() && ! auth()->user()->hasRole('viewer');
    }

    /** 以下几个方法都调用 allowed() */
    public static function shouldRegisterNavigation(): bool { return static::allowed(); }
    public static function canViewAny(): bool               { return static::allowed(); }
    public static function canCreate(): bool                { return static::allowed(); }
    public static function canEdit($record): bool           { return static::allowed(); }
    public static function canDelete($record): bool         { return static::allowed(); }
    public static function canDeleteAny(): bool             { return static::allowed(); }
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')
                ->label('日期')
                ->required()
                ->default(fn() => now('Asia/Singapore')->toDateString())
                ->unique(table: 'daily_ip_costs', column: 'date', ignoreRecord: true)
                ->disabled(fn($context) => $context === 'edit') // 编辑时禁用
                ->validationMessages([
                    'required' => '日期不能为空',
                    'unique' => '该日期的IP费用记录已存在，请选择其他日期',
                ]),

            Forms\Components\TextInput::make('amount')
                ->label('IP+服务器费用')
                ->numeric()
                ->minValue(0)
                ->required()
                ->prefix('$')
                ->validationMessages([
                    'required' => '费用不能为空',
                    'numeric' => '费用必须是数字',
                    'min' => '费用不能小于0',
                ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date')->label('日期')->date(),
                Tables\Columns\TextColumn::make('amount')->label('费用')->money('USD'),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->since(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function () {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('确认删除')
                            ->body('删除IP费用记录将同时删除相关的所有数据，此操作不可恢复。')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->modalHeading('确认批量删除')
                    ->modalDescription('删除IP费用记录将同时删除相关的所有数据，此操作不可恢复。确定要删除选中的IP费用记录吗？')
                    ->modalSubmitActionLabel('确认删除'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyIpCosts::route('/'),
            'create' => Pages\CreateDailyIpCost::route('/create'),
            'edit' => Pages\EditDailyIpCost::route('/{record}/edit'),
        ];
    }
}
