<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreQrLinkResource\Pages;
use App\Filament\Resources\StoreQrLinkResource\RelationManagers;
use App\Models\StoreQrLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StoreQrLinkResource extends Resource
{
    protected static ?string $model = StoreQrLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = '店舗別QR';
    protected static ?string $pluralLabel = '店舗別QR';
    protected static ?string $label = '店舗別QR';
    protected static ?string $navigationGroup = '店舗管理';


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('store_id')
                ->label('店舗')
                ->relationship('store', 'name')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('QR名')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('slug')
                ->label('Slug（URL識別子）')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('例：dt-test-front（英数字とハイフン推奨）'),

            Forms\Components\TextInput::make('redirect_url')
                ->label('リダイレクトURL')
                ->url()
                ->maxLength(2000)
                ->helperText('LINEのスタンプ付与URL（u.lin.ee など）'),

            Forms\Components\Toggle::make('is_active')
                ->label('有効')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('QR名')
                    ->searchable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->copyable()
                    ->badge(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('店舗')
                    ->sortable(),

                Tables\Columns\TextColumn::make('redirect_url')
                    ->label('リダイレクトURL')
                    ->limit(30)
                    ->copyable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('有効')
                    ->boolean(),
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
            'index' => Pages\ListStoreQrLinks::route('/'),
            'create' => Pages\CreateStoreQrLink::route('/create'),
            'edit' => Pages\EditStoreQrLink::route('/{record}/edit'),
        ];
    }
}
