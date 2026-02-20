<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StampCardDefinitionResource\Pages;
use App\Models\StampCardDefinition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StampCardDefinitionResource extends Resource
{
    protected static ?string $model = StampCardDefinition::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'スタンプ管理';
    protected static ?string $navigationLabel = 'スタンプカード定義';
    protected static ?string $modelLabel = 'スタンプカード';
    protected static ?string $pluralModelLabel = 'スタンプカード';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('store_id')
                ->label('店舗')
                ->relationship('store', 'name')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('識別名（BEGINNERなど）')
                ->required()
                ->maxLength(50),

            Forms\Components\TextInput::make('display_name')
                ->label('表示名（画面に出る）')
                ->required()
                ->maxLength(50),

            Forms\Components\TextInput::make('required_stamps')
                ->label('必要スタンプ数')
                ->numeric()
                ->minValue(1)
                ->required(),

            Forms\Components\TextInput::make('priority')
                ->label('順序（昇格順）')
                ->numeric()
                ->minValue(1)
                ->required(),

            Forms\Components\TextInput::make('theme_bg')
                ->label('背景色（例 #0b0b0f）')
                ->maxLength(20),

            Forms\Components\TextInput::make('theme_accent')
                ->label('アクセント色（例 #ffd54a）')
                ->maxLength(20),

            Forms\Components\TextInput::make('theme_logo_opacity')
                ->label('ロゴ透明度（例 0.10）')
                ->numeric()
                ->minValue(0)
                ->maxValue(1),

            Forms\Components\Toggle::make('is_active')
                ->label('有効')
                ->default(true),

            Forms\Components\Select::make('rankup_coupon_id')
                ->label('ランクアップ時クーポン')
                ->relationship('rankupCoupon', 'title')
                ->searchable()
                ->nullable()
                ->live()
                ->helperText('このランクに昇格した時に配布するクーポン'),

            Forms\Components\TextInput::make('rankup_coupon_expires_days')
                ->label('ランクアップクーポン有効日数')
                ->numeric()
                ->minValue(1)
                ->nullable()
                ->placeholder('例: 30')
                ->helperText('発行からX日後に失効（未設定=無期限）')
                ->visible(fn ($get) => !empty($get('rankup_coupon_id'))),

            Forms\Components\Select::make('checkin_coupon_id')
                ->label('チェックイン時クーポン')
                ->relationship('checkinCoupon', 'title')
                ->searchable()
                ->nullable()
                ->live()
                ->helperText('このランクでチェックインした時に配布するクーポン（任意）'),

            Forms\Components\TextInput::make('checkin_coupon_expires_days')
                ->label('チェックインクーポン有効日数')
                ->numeric()
                ->minValue(1)
                ->nullable()
                ->placeholder('例: 7')
                ->helperText('発行からX日後に失効（未設定=無期限）')
                ->visible(fn ($get) => !empty($get('checkin_coupon_id'))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')->label('店舗')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('priority')->label('順序')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('識別名')->searchable(),
                Tables\Columns\TextColumn::make('display_name')->label('表示名')->searchable(),
                Tables\Columns\TextColumn::make('required_stamps')->label('必要数')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('有効')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('更新')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('priority')
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
            'index' => Pages\ListStampCardDefinitions::route('/'),
            'create' => Pages\CreateStampCardDefinition::route('/create'),
            'edit' => Pages\EditStampCardDefinition::route('/{record}/edit'),
        ];
    }
}
