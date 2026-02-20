<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChurnScenarioResource\Pages;
use App\Models\ChurnScenario;
use App\Models\CouponTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChurnScenarioResource extends Resource
{
    protected static ?string $model = ChurnScenario::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'メッセージ管理';
    protected static ?string $navigationLabel = '離脱防止シナリオ';
    protected static ?string $modelLabel = 'シナリオ';
    protected static ?string $pluralModelLabel = 'シナリオ';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('store_id')
                ->label('店舗')
                ->relationship('store', 'name')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('シナリオ名')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('days_after_last_stamp')
                ->label('最終スタンプからX日後')
                ->numeric()
                ->minValue(1)
                ->required(),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('send_hour')
                    ->label('配信時刻（時）')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(23)
                    ->required(),

                Forms\Components\TextInput::make('send_minute')
                    ->label('配信時刻（分）')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(59)
                    ->required(),
            ]),

            Forms\Components\Toggle::make('is_active')
                ->label('有効')
                ->default(true),

            // バブルRepeater（1〜3）
            Forms\Components\Section::make('メッセージ内容')
                ->description('1〜3つのバブルを設定（テキスト or クーポン）')
                ->schema([
                    Forms\Components\Repeater::make('bubbles')
                        ->label('バブル')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('bubble_type')
                                ->label('種別')
                                ->options([
                                    'text' => 'テキスト',
                                    'coupon' => 'クーポン',
                                ])
                                ->required()
                                ->live(),

                            Forms\Components\Textarea::make('text_content')
                                ->label('テキスト内容')
                                ->rows(3)
                                ->visible(fn ($get) => $get('bubble_type') === 'text'),

                            Forms\Components\Select::make('coupon_template_id')
                                ->label('クーポンテンプレート')
                                ->relationship('couponTemplate', 'title')
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $coupon = CouponTemplate::find($state);
                                        $set('coupon_template_title', $coupon?->title ?? '');
                                        $set('coupon_template_note', $coupon?->note ?? '');
                                        $set('coupon_template_image_url', CouponTemplate::resolveImageUrl($coupon?->image_url) ?? '');
                                    }
                                })
                                ->visible(fn ($get) => $get('bubble_type') === 'coupon'),

                            Forms\Components\TextInput::make('coupon_expires_days')
                                ->label('有効期限（配信からX日後）')
                                ->numeric()
                                ->minValue(1)
                                ->placeholder('例: 14')
                                ->helperText('配信日からこの日数後にクーポンが失効します')
                                ->live(onBlur: true)
                                ->visible(fn ($get) => $get('bubble_type') === 'coupon'),

                            Forms\Components\Hidden::make('coupon_template_title')
                                ->dehydrated(false),
                            Forms\Components\Hidden::make('coupon_template_note')
                                ->dehydrated(false),
                            Forms\Components\Hidden::make('coupon_template_image_url')
                                ->dehydrated(false),
                        ])
                        ->minItems(1)
                        ->maxItems(3)
                        ->defaultItems(1)
                        ->reorderable(true)
                        ->orderColumn('position')
                        ->addActionLabel('バブルを追加'),
                ]),

            // LINE公式風プレビュー
            Forms\Components\View::make('filament.forms.components.bubble-preview')
                ->label('プレビュー'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')->label('店舗')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('シナリオ名')->searchable(),
                Tables\Columns\TextColumn::make('days_after_last_stamp')->label('X日後')->sortable(),
                Tables\Columns\TextColumn::make('send_hour')
                    ->label('配信時刻')
                    ->formatStateUsing(fn ($record) => sprintf('%02d:%02d', $record->send_hour, $record->send_minute)),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChurnScenarios::route('/'),
            'create' => Pages\CreateChurnScenario::route('/create'),
            'edit' => Pages\EditChurnScenario::route('/{record}/edit'),
        ];
    }
}
