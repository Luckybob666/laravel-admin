<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeRateResource\Pages;
use App\Models\ExchangeRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = '基础档案';
    protected static ?int $navigationSort = 30;
    protected static ?string $modelLabel = '每日汇率';
    protected static ?string $pluralModelLabel = '每日汇率';

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
            Forms\Components\DatePicker::make('rate_date')
                ->label('日期')
                ->required()
                ->default(fn() => now('Asia/Singapore')->toDateString())
                ->unique(ignoreRecord: true)
                ->disabled(fn($context) => $context === 'edit') // 编辑时禁用
                ->validationMessages([
                    'required' => '日期不能为空',
                    'unique' => '该日期已存在汇率记录，请选择其他日期',
                ]),

            Forms\Components\TextInput::make('base_currency')
                ->label('基准币种')
                ->default('USD')
                ->disabled()
                ->dehydrated(true),

            Forms\Components\Fieldset::make('汇率（1 USD = ?）')
                ->schema([
                    Forms\Components\TextInput::make('usd_to_cny')
                        ->label('CNY')
                        ->numeric()
                        ->rule('gt:0')
                        ->required()
                        ->default(7)
                        ->validationMessages([
                            'required' => 'CNY汇率不能为空',
                            'numeric' => 'CNY汇率必须是数字',
                            'gt' => 'CNY汇率必须大于0',
                        ]),

                    Forms\Components\TextInput::make('usd_to_pkr')
                        ->label('PKR')
                        ->numeric()
                        ->rule('gt:0')
                        ->required()
                        ->default(290)
                        ->validationMessages([
                            'required' => 'PKR汇率不能为空',
                            'numeric' => 'PKR汇率必须是数字',
                            'gt' => 'PKR汇率必须大于0',
                        ]),

                    Forms\Components\TextInput::make('usd_to_inr')
                        ->label('INR')
                        ->numeric()
                        ->rule('gt:0')
                        ->required()
                        ->default(87)
                        ->validationMessages([
                            'required' => 'INR汇率不能为空',
                            'numeric' => 'INR汇率必须是数字',
                            'gt' => 'INR汇率必须大于0',
                        ]),
                ])->columns(3),

            Forms\Components\Hidden::make('source')->default('manual'),
            Forms\Components\Toggle::make('is_locked')->label('锁定'),
            Forms\Components\Textarea::make('notes')->label('备注')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rate_date')->label('日期')->date(),
                Tables\Columns\TextColumn::make('usd_to_cny')->label('CNY')->numeric(4),
                Tables\Columns\TextColumn::make('usd_to_pkr')->label('PKR')->numeric(4),
                Tables\Columns\TextColumn::make('usd_to_inr')->label('INR')->numeric(4),
                Tables\Columns\BooleanColumn::make('is_locked')->label('锁定'),
                Tables\Columns\BadgeColumn::make('source')->label('来源'),
                Tables\Columns\TextColumn::make('updated_at')->label('更新时间')->since(),
            ])
            ->defaultSort('rate_date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('起'),
                        Forms\Components\DatePicker::make('to')->label('止'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn($q, $d) => $q->whereDate('rate_date', '>=', $d))
                            ->when($data['to'] ?? null, fn($q, $d) => $q->whereDate('rate_date', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->modalHeading('确认批量删除')
                    ->modalDescription('删除汇率记录将同时删除相关的所有数据，此操作不可恢复。确定要删除选中的汇率记录吗？')
                    ->modalSubmitActionLabel('确认删除'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExchangeRates::route('/'),
            'create' => Pages\CreateExchangeRate::route('/create'),
            'edit'   => Pages\EditExchangeRate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery();
    }
}
