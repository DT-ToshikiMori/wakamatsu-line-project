<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponTemplateResource\Pages;
use App\Models\CouponTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponTemplateResource extends Resource
{
    protected static ?string $model = CouponTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'クーポン管理';
    protected static ?string $navigationLabel = 'クーポンテンプレ';
    protected static ?string $modelLabel = 'クーポン';
    protected static ?string $pluralModelLabel = 'クーポン';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('store_id')
                ->label('店舗')
                ->relationship('store', 'name')
                ->searchable()
                ->required(),

            Forms\Components\Select::make('type')
                ->label('種別')
                ->options([
                    'birthday' => '誕生日',
                    'inactive' => '離脱防止',
                    'rank_up'  => 'ランクアップ',
                    'stamp'    => 'スタンプ到達',
                ])
                ->required()
                ->live(),

            Forms\Components\TextInput::make('title')
                ->label('タイトル')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('note')
                ->label('備考')
                ->rows(3),

            Forms\Components\TextInput::make('image_url')
                ->label('画像URL（300x900想定）')
                ->maxLength(2000),

            // 誕生日
            Forms\Components\TextInput::make('birthday_offset_days')
                ->label('誕生日オフセット（日）')
                ->numeric()
                ->visible(fn ($get) => $get('type') === 'birthday'),

            // 離脱防止
            Forms\Components\TextInput::make('inactive_days')
                ->label('最終来店からX日')
                ->numeric()
                ->visible(fn ($get) => $get('type') === 'inactive'),

            Forms\Components\TextInput::make('inactive_hour')
                ->label('配信時刻（時）')
                ->numeric()
                ->minValue(0)->maxValue(23)
                ->visible(fn ($get) => $get('type') === 'inactive'),

            Forms\Components\TextInput::make('inactive_minute')
                ->label('配信時刻（分）')
                ->numeric()
                ->minValue(0)->maxValue(59)
                ->visible(fn ($get) => $get('type') === 'inactive'),

            // スタンプ到達
            Forms\Components\TextInput::make('required_stamps')
                ->label('チェックイン回数A回で付与')
                ->numeric()
                ->visible(fn ($get) => $get('type') === 'stamp'),

            // ランクアップ
            Forms\Components\Select::make('rank_card_id')
                ->label('対象ランク（rank_up用）')
                ->relationship('rankCard', 'display_name')
                ->searchable()
                ->visible(fn ($get) => $get('type') === 'rank_up'),

            Forms\Components\Toggle::make('is_active')
                ->label('有効')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')->label('店舗')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('type')->label('種別')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('タイトル')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->label('有効')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('更新')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
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
            'index' => Pages\ListCouponTemplates::route('/'),
            'create' => Pages\CreateCouponTemplate::route('/create'),
            'edit' => Pages\EditCouponTemplate::route('/{record}/edit'),
        ];
    }
}
