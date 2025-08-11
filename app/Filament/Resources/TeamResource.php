<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = '基础档案';
    protected static ?int $navigationSort = 10;
    protected static ?string $modelLabel = '团队设置';
    protected static ?string $pluralModelLabel = '团队设置';

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
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->label('团队名称')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'required' => '团队名称不能为空',
                        'max' => '团队名称不能超过100个字符',
                        'unique' => '该团队名称已存在，请使用其他名称',
                    ]),

                Forms\Components\TextInput::make('code')
                    ->label('编码')
                    ->maxLength(50)
                    ->helperText('可选，用于对接或导入导出')
                    ->validationMessages([
                        'max' => '编码不能超过50个字符',
                    ]),

                Forms\Components\Toggle::make('is_active')
                    ->label('启用')
                    ->default(true),

                Forms\Components\Select::make('leader_id')
                    ->label('负责人')
                    ->relationship(name: 'leader', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('（可选）')
                    ->validationMessages([
                        'exists' => '选择的负责人不存在',
                    ]),
            ])->columns(2),

            Forms\Components\Section::make('高级设置（可选）')->schema([
                Forms\Components\KeyValue::make('settings')
                    ->label('预设参数')
                    ->keyLabel('键')
                    ->valueLabel('值')
                    ->helperText('例如：default_unit_price、default_fx_rate、fixed_cost_policy 等'),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('团队名称')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->label('编码')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->label('启用')->boolean(),
                Tables\Columns\TextColumn::make('leader.name')->label('负责人')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->since(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('启用状态')
                    ->trueLabel('仅启用')->falseLabel('仅停用')->placeholder('全部'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn(Team $record) => $record->is_active ? '停用' : '启用')
                    ->color(fn(Team $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn(Team $record) => $record->is_active ? '确认停用团队' : '确认启用团队')
                    ->modalDescription(fn(Team $record) => $record->is_active 
                        ? '确定要停用团队 "' . $record->name . '" 吗？' 
                        : '确定要启用团队 "' . $record->name . '" 吗？')
                    ->modalSubmitActionLabel(fn(Team $record) => $record->is_active ? '确认停用' : '确认启用')
                    ->action(fn(Team $record) => $record->update(['is_active' => ! $record->is_active]))
                    ->after(function (Team $record) {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title($record->is_active ? '团队启用成功' : '团队停用成功')
                            ->body('团队 "' . $record->name . '" 已' . ($record->is_active ? '启用' : '停用') . '。')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_enable')->label('批量启用')
                    ->requiresConfirmation()
                    ->modalHeading('确认批量启用')
                    ->modalDescription('确定要启用选中的团队吗？')
                    ->modalSubmitActionLabel('确认启用')
                    ->action(fn($records) => $records->each->update(['is_active' => true]))
                    ->after(function ($records) {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('批量启用成功')
                            ->body('已成功启用 ' . $records->count() . ' 个团队。')
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('bulk_disable')->label('批量停用')->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('确认批量停用')
                    ->modalDescription('确定要停用选中的团队吗？')
                    ->modalSubmitActionLabel('确认停用')
                    ->action(fn($records) => $records->each->update(['is_active' => false]))
                    ->after(function ($records) {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('批量停用成功')
                            ->body('已成功停用 ' . $records->count() . ' 个团队。')
                            ->send();
                    }),
                Tables\Actions\DeleteBulkAction::make()
                    ->modalHeading('确认批量删除')
                    ->modalDescription('删除团队将同时删除相关的所有数据，此操作不可恢复。确定要删除选中的团队吗？')
                    ->modalSubmitActionLabel('确认删除'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit'   => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
