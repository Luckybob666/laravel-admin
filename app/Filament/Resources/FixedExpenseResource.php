<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FixedExpenseResource\Pages;
use App\Models\FixedExpense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FixedExpenseResource extends Resource
{
    protected static ?string $model = FixedExpense::class;
    protected static ?string $navigationGroup = '基础档案';
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $modelLabel = '固定费用';
    protected static ?string $pluralModelLabel = '固定费用';


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
            Forms\Components\DatePicker::make('month_date')
                ->label('月份')
                ->required()
                ->default(fn() => now('Asia/Singapore')->startOfMonth()->toDateString())
                ->disabled(fn($context) => $context === 'edit') // 编辑时禁用
                ->displayFormat('Y-m') // 只显示年月
                ->format('Y-m-01') // 自动设置为该月第一天
                ->rules([
                    'required',
                    'date',
                    'unique:fixed_expenses,month_date'
                ])
                ->validationMessages([
                    'required' => '月份不能为空',
                    'unique' => '该月份已存在固定费用记录，请选择其他月份',
                ]),

            Forms\Components\TextInput::make('amount')
                ->label('金额')
                ->numeric()
                ->minValue(0)
                ->required()
                ->validationMessages([
                    'required' => '金额不能为空',
                    'numeric' => '金额必须是数字',
                    'min' => '金额不能小于0',
                ]),

            Forms\Components\Textarea::make('note')
                ->label('备注')
                ->maxLength(255)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('month_date')
                    ->label('月份')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('Y-m') : '')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')->label('金额')->numeric(2)->sortable(),
                Tables\Columns\TextColumn::make('note')->label('备注')->limit(30),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->modalHeading('确认批量删除')
                    ->modalDescription('删除固定费用记录将同时删除相关的所有数据，此操作不可恢复。确定要删除选中的固定费用记录吗？')
                    ->modalSubmitActionLabel('确认删除'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFixedExpenses::route('/'),
            'create' => Pages\CreateFixedExpense::route('/create'),
            'edit'   => Pages\EditFixedExpense::route('/{record}/edit'),
        ];
    }
}
