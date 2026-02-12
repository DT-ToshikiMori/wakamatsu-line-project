<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BroadcastResource\Pages;
use App\Models\Broadcast;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BroadcastResource extends Resource
{
    protected static ?string $model = Broadcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'メッセージ管理';
    protected static ?string $navigationLabel = '自由配信';
    protected static ?string $modelLabel = '配信';
    protected static ?string $pluralModelLabel = '配信';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('store_id')
                ->label('店舗')
                ->relationship('store', 'name')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('配信名')
                ->required()
                ->maxLength(255),

            // フィルター設定
            Forms\Components\Section::make('配信対象')
                ->schema([
                    Forms\Components\Select::make('filter_type')
                        ->label('対象')
                        ->options([
                            'all' => '全員',
                            'filtered' => '絞り込み',
                        ])
                        ->default('all')
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('filter_rank_card_id')
                        ->label('ランク絞り込み')
                        ->relationship('filterRankCard', 'display_name')
                        ->searchable()
                        ->nullable()
                        ->visible(fn ($get) => $get('filter_type') === 'filtered'),

                    Forms\Components\TextInput::make('filter_days_since_visit')
                        ->label('最終来店からX日以上')
                        ->numeric()
                        ->minValue(1)
                        ->nullable()
                        ->visible(fn ($get) => $get('filter_type') === 'filtered'),

                    Forms\Components\TextInput::make('filter_min_visits')
                        ->label('来店回数X回以上')
                        ->numeric()
                        ->minValue(1)
                        ->nullable()
                        ->visible(fn ($get) => $get('filter_type') === 'filtered'),
                ]),

            // 配信予定日時
            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('配信予定日時')
                ->nullable(),

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
                                ->visible(fn ($get) => $get('bubble_type') === 'coupon'),
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
                Tables\Columns\TextColumn::make('name')->label('配信名')->searchable(),
                Tables\Columns\TextColumn::make('filter_type')->label('対象')
                    ->formatStateUsing(fn (string $state) => $state === 'all' ? '全員' : '絞り込み'),
                Tables\Columns\TextColumn::make('status')->label('ステータス')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => '下書き',
                        'scheduled' => '予約済み',
                        'sent' => '配信済み',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'gray',
                        'scheduled' => 'warning',
                        'sent' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('scheduled_at')->label('配信予定')->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('sent_count')->label('配信数')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send')
                    ->label('配信する')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('配信確認')
                    ->modalDescription('この配信を今すぐ実行しますか？')
                    ->visible(fn (Broadcast $record) => $record->status !== 'sent')
                    ->action(function (Broadcast $record) {
                        $record->update([
                            'status' => 'scheduled',
                            'scheduled_at' => now(),
                        ]);

                        // 即時実行
                        \Illuminate\Support\Facades\Artisan::call('messages:process-broadcasts');
                    }),
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
            'index' => Pages\ListBroadcasts::route('/'),
            'create' => Pages\CreateBroadcast::route('/create'),
            'edit' => Pages\EditBroadcast::route('/{record}/edit'),
        ];
    }
}
