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
                ->label('店舗（任意）')
                ->relationship('store', 'name')
                ->searchable()
                ->nullable()
                ->helperText('未選択の場合、グローバルクーポン'),

            Forms\Components\Select::make('mode')
                ->label('モード')
                ->options([
                    'normal'  => '通常クーポン',
                    'lottery' => '抽選クーポン',
                ])
                ->default('normal')
                ->required()
                ->live(),

            Forms\Components\Hidden::make('type')
                ->default('stamp'),

            Forms\Components\TextInput::make('title')
                ->label('タイトル')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('note')
                ->label('備考')
                ->rows(3),

            Forms\Components\FileUpload::make('image_url')
                ->label('クーポン画像（正方形 1:1）')
                ->disk('public')
                ->directory('coupon-images')
                ->image()
                ->imageResizeMode('cover')
                ->imageCropAspectRatio('1:1')
                ->imageResizeTargetWidth(900)
                ->imageResizeTargetHeight(900)
                ->panelAspectRatio('1:1')
                ->maxSize(2048)
                ->helperText('推奨: 900×900px / 最大2MB'),

            // 誕生日配信設定（オプション）
            Forms\Components\Section::make('誕生日配信設定')
                ->description('設定するとユーザーの誕生日に合わせて自動配信されます。')
                ->collapsed()
                ->collapsible()
                ->schema([
                    Forms\Components\TextInput::make('birthday_offset_days')
                        ->label('誕生日の何日前に配信')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('日前')
                        ->nullable()
                        ->helperText('0 = 誕生日当日'),
                ]),

            Forms\Components\Toggle::make('is_active')
                ->label('有効')
                ->default(true),

            // 抽選設定（lottery モード時のみ表示）
            Forms\Components\Section::make('抽選賞品設定')
                ->description('確率の合計が100%になるように設定してください')
                ->visible(fn ($get) => $get('mode') === 'lottery')
                ->schema([
                    Forms\Components\Repeater::make('lotteryPrizes')
                        ->label('賞品')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('rank')
                                ->label('等数（0=ハズレ）')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(5)
                                ->required(),

                            Forms\Components\TextInput::make('title')
                                ->label('賞品名')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('image_url')
                                ->label('画像URL')
                                ->maxLength(2000),

                            Forms\Components\TextInput::make('probability')
                                ->label('確率（%）')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(100)
                                ->required()
                                ->suffix('%'),

                            Forms\Components\Toggle::make('is_miss')
                                ->label('ハズレ')
                                ->default(false),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->maxItems(6)
                        ->defaultItems(1)
                        ->reorderable(false)
                        ->addActionLabel('賞品を追加'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('画像')
                    ->disk('public')
                    ->width(72)
                    ->height(40)
                    ->width(40),
                Tables\Columns\TextColumn::make('store.name')->label('店舗')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('mode')->label('モード')
                    ->formatStateUsing(fn (string $state) => $state === 'lottery' ? '抽選' : '通常')
                    ->sortable(),
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
