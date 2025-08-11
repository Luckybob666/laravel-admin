<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = '产品';      // 左侧菜单项显示
    protected static ?string $navigationGroup = '基础资料';  // 左侧菜单分组（可选）
    protected static ?string $pluralModelLabel = '产品';      // 复数标签：列表页标题/面包屑
    protected static ?string $modelLabel = '产品';            // 单数标签：按钮里“创建产品/编辑产品”

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
    
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            TextInput::make('name')
                ->label('产品名称')
                ->required(),

            TextInput::make('quantity')
                ->label('数量')
                ->numeric()
                ->default(1)
                ->reactive()
                ->afterStateUpdated(function (Get $get, Set $set) {
                    $qty   = (int) ($get('quantity') ?? 0);
                    $price = (float) ($get('price') ?? 0);
                    $set('total', round($qty * $price, 2));
                }),

            TextInput::make('price')
                ->label('单价')
                ->numeric()
                ->default(0)
                ->reactive()
                ->afterStateUpdated(function (Get $get, Set $set) {
                    $qty   = (int) ($get('quantity') ?? 0);
                    $price = (float) ($get('price') ?? 0);
                    $set('total', round($qty * $price, 2));
                }),

            TextInput::make('total')
                ->label('总价')
                ->numeric()
                ->disabled()
                ->dehydrated(false)
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable(),
                TextColumn::make('name')->label('产品名称')->sortable()->searchable(),
                TextColumn::make('quantity')->label('数量')->sortable()->searchable(),
                TextColumn::make('price')->label('单价')->sortable()->searchable(),
                TextColumn::make('total')->label('总价')->sortable()->searchable(),
                TextColumn::make('created_at')->label('创建时间')->sortable()->searchable(),
                TextColumn::make('updated_at')->label('更新时间')->sortable()->searchable(),
            ])
            ->defaultSort('id', 'desc');
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
