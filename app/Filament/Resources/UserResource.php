<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Hash;
use Filament\Resources\Pages\CreateRecord; // 你用到了 CreateRecord 做“新建时必填”


class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = '用户管理';
    protected static ?string $navigationGroup = '管理员配置';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
            Section::make('基本信息')
                ->schema([
                    TextInput::make('username')
                        ->label('用户名')
                        ->required()
                        ->maxLength(64)
                        ->unique(ignoreRecord: true),
    
                    TextInput::make('name')
                        ->label('姓名')
                        ->required()
                        ->maxLength(255),
    
                    TextInput::make('email')
                        ->label('邮箱')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
    
                    DateTimePicker::make('email_verified_at')
                        ->label('邮箱验证时间')
                        ->seconds(false),
    
                    TextInput::make('password')
                        ->label('密码')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        // 新建时必填；编辑时可选
                        ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                        // 只有填了才写入
                        ->dehydrated(fn ($state) => filled($state))
                        // 保存前做哈希
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),
                ])
                ->columns(2),
    
            Section::make('访问控制')
                ->schema([
                    Select::make('roles')
                        ->label('角色')
                        ->multiple()
                        ->relationship('roles', 'name')
                        ->preload()
                        ->searchable()
                        ->helperText('通过角色继承权限，推荐优先使用角色进行授权。'),
    
                    Select::make('permissions')
                        ->label('直接权限')
                        ->multiple()
                        ->relationship('permissions', 'name')
                        ->preload()
                        ->searchable()
                        ->helperText('仅在需要细粒度控制时使用直接权限。'),
                ])
                ->collapsible()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->label('角色'),
                Tables\Columns\TextColumn::make('email_verified_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
