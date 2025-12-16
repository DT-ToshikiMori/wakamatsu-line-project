<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Filament\Resources\StoreResource\RelationManagers;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = '店舗';
    protected static ?string $pluralLabel = '店舗';
    protected static ?string $label = '店舗';
    protected static ?string $navigationGroup = '店舗管理';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('店舗名')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('address')
                ->label('住所')
                ->maxLength(255),

            Forms\Components\Toggle::make('is_active')
                ->label('稼働中')
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
                    ->label('店舗名')
                    ->searchable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('住所')
                    ->limit(30),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('稼働')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y-m-d'),
            ])
            ->defaultSort('id');
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
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
