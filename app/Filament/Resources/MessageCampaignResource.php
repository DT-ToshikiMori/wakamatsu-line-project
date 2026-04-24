<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageCampaignResource\Pages;
use App\Models\MessageCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MessageCampaignResource extends Resource
{
    protected static ?string $model = MessageCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'メッセージ管理';
    protected static ?string $navigationLabel = '統合メッセージ';
    protected static ?string $modelLabel = 'メッセージキャンペーン';
    protected static ?string $pluralModelLabel = 'メッセージキャンペーン';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('配信タイプ')
                ->options([
                    'scenario' => 'シナリオ配信',
                    'campaign' => 'キャンペーン配信',
                    'birthday' => '誕生月配信',
                ])
                ->required()
                ->live(),

            Forms\Components\TextInput::make('name')
                ->label('配信名')
                ->required()
                ->maxLength(255),

            Forms\Components\Toggle::make('is_active')
                ->label('有効')
                ->default(true),

            // 共通: クーポン設定
            Forms\Components\Section::make('クーポン設定')
                ->schema([
                    Forms\Components\Select::make('coupon_template_id')
                        ->label('クーポンテンプレート')
                        ->relationship('couponTemplate', 'title')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Forms\Components\TextInput::make('coupon_expires_days')
                        ->label('クーポン有効期限（日数）')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                ]),

            // 共通: テキスト
            Forms\Components\Textarea::make('text_content')
                ->label('テキスト内容')
                ->rows(3)
                ->nullable(),

            // === シナリオ配信用 ===
            Forms\Components\Section::make('シナリオ設定')
                ->hidden(fn ($get) => $get('type') !== 'scenario')
                ->schema([
                    Forms\Components\TextInput::make('offset_days')
                        ->label('最終来店からX日後')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),

                    Forms\Components\TextInput::make('send_hour')
                        ->label('送信時（0〜23）')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(23)
                        ->nullable(),

                    Forms\Components\TextInput::make('send_minute')
                        ->label('送信分（0〜59）')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(59)
                        ->nullable(),
                ]),

            // === シナリオ・キャンペーン共通セグメント ===
            Forms\Components\Section::make('セグメント設定')
                ->hidden(fn ($get) => ! in_array($get('type'), ['scenario', 'campaign']))
                ->schema([
                    Forms\Components\Select::make('seg_rank_id')
                        ->label('ランク絞り込み')
                        ->relationship('segRank', 'display_name')
                        ->searchable()
                        ->nullable(),

                    Forms\Components\TextInput::make('seg_stamp_min')
                        ->label('スタンプ数（最小）')
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),

                    Forms\Components\TextInput::make('seg_stamp_max')
                        ->label('スタンプ数（最大）')
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),

                    Forms\Components\TextInput::make('seg_visit_min')
                        ->label('来店回数（最小）')
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                ]),

            // === キャンペーン配信用 ===
            Forms\Components\Section::make('キャンペーン設定')
                ->hidden(fn ($get) => $get('type') !== 'campaign')
                ->schema([
                    Forms\Components\DateTimePicker::make('send_at')
                        ->label('配信予定日時')
                        ->nullable(),

                    Forms\Components\Toggle::make('is_full_broadcast')
                        ->label('全員配信')
                        ->default(false),

                    Forms\Components\Select::make('seg_gender')
                        ->label('性別')
                        ->options([
                            'male' => '男性',
                            'female' => '女性',
                            'other' => 'その他',
                        ])
                        ->nullable(),

                    Forms\Components\TextInput::make('seg_last_visit_within_days')
                        ->label('最終来店X日以内')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),

                    Forms\Components\TextInput::make('seg_last_visit_over_days')
                        ->label('最終来店X日以上前')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                ]),

            // === 誕生月配信用 ===
            Forms\Components\Section::make('誕生月設定')
                ->hidden(fn ($get) => $get('type') !== 'birthday')
                ->schema([
                    Forms\Components\TextInput::make('birthday_send_day')
                        ->label('毎月の送信日（1〜28）')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(28)
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('配信名')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('タイプ')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'scenario' => 'シナリオ配信',
                        'campaign' => 'キャンペーン配信',
                        'birthday' => '誕生月配信',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'scenario' => 'info',
                        'campaign' => 'warning',
                        'birthday' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('有効')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListMessageCampaigns::route('/'),
            'create' => Pages\CreateMessageCampaign::route('/create'),
            'edit' => Pages\EditMessageCampaign::route('/{record}/edit'),
        ];
    }
}
