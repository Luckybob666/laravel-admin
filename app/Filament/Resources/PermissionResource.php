<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\PermissionResource\Pages;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = '管理员配置';
    protected static ?string $navigationLabel = '权限管理';

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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('guard_name', 'web');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('权限键')
                ->helperText('例如：view_user、create_order、product.update')
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\Hidden::make('guard_name')->default('web'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('权限键')->searchable(),
            Tables\Columns\TextColumn::make('roles_count')->counts('roles')->label('关联角色数'),
            Tables\Columns\TextColumn::make('created_at')->dateTime('Y-m-d H:i')->label('创建时间'),
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit'   => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
