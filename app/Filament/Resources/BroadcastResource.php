<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BroadcastResource\Pages;
use App\Models\Broadcast;
use App\Models\CouponTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class BroadcastResource extends Resource
{
    protected static ?string $model = Broadcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'メッセージ管理';
    protected static ?string $navigationLabel = '自由配信';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $modelLabel = '配信';
    protected static ?string $pluralModelLabel = '配信';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ① 配信名（最初）
            Forms\Components\TextInput::make('name')
                ->label('配信名')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            // ② 対象店舗（チェックボックス複数選択）
            Forms\Components\Section::make('対象店舗')
                ->description('未選択の場合は全店舗が対象になります。')
                ->icon('heroicon-o-building-storefront')
                ->schema([
                    Forms\Components\CheckboxList::make('store_ids')
                        ->label('')
                        ->options(fn () => \App\Models\Store::orderBy('name')->pluck('name', 'id'))
                        ->columns(3)
                        ->gridDirection('row')
                        ->live(),
                ])
                ->collapsible(),

            // ③ 配信対象フィルター（チェックボックス）
            Forms\Components\Section::make('配信対象フィルター')
                ->description('条件を設定しない場合は全ユーザーが対象です。')
                ->icon('heroicon-o-funnel')
                ->schema([
                    Forms\Components\CheckboxList::make('filter_rank_card_id')
                        ->label('ランク')
                        ->options(fn () => \App\Models\StampCardDefinition::orderBy('priority')->pluck('display_name', 'id'))
                        ->columns(3)
                        ->gridDirection('row')
                        ->live(),

                    Forms\Components\CheckboxList::make('filter_gender')
                        ->label('性別')
                        ->options([
                            'male'   => '男性',
                            'female' => '女性',
                            'other'  => 'その他',
                        ])
                        ->columns(3)
                        ->gridDirection('row')
                        ->live(),

                    Forms\Components\CheckboxList::make('filter_birth_month')
                        ->label('誕生月')
                        ->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => "{$m}月"]))
                        ->columns(6)
                        ->gridDirection('row')
                        ->live(),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('filter_min_visits')
                                ->label('来店回数 X回以上')
                                ->numeric()
                                ->minValue(1)
                                ->nullable()
                                ->suffix('回以上')
                                ->live(onBlur: true),

                            Forms\Components\TextInput::make('filter_max_visits')
                                ->label('来店回数 X回以下')
                                ->numeric()
                                ->minValue(1)
                                ->nullable()
                                ->suffix('回以下')
                                ->live(onBlur: true),

                            Forms\Components\TextInput::make('filter_days_since_visit')
                                ->label('最終来店からX日以上経過')
                                ->numeric()
                                ->minValue(1)
                                ->nullable()
                                ->suffix('日以上前')
                                ->live(onBlur: true),
                        ]),

                    // 配信対象数（リアルタイム）
                    Forms\Components\Placeholder::make('target_count_display')
                        ->label('')
                        ->content(function ($get): HtmlString {
                            $count = self::calcTargetCount($get);
                            $color = $count === 0 ? '#ef4444' : '#06c755';
                            return new HtmlString(
                                '<div style="display:inline-flex;align-items:center;gap:8px;padding:10px 16px;background:#f0faf4;border:1px solid #bbf7d0;border-radius:8px;">'
                                . '<svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;color:' . $color . ';" viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>'
                                . '<span style="font-size:14px;color:#374151;">配信対象:</span>'
                                . '<span style="font-size:20px;font-weight:700;color:' . $color . ';">' . number_format($count) . '</span>'
                                . '<span style="font-size:14px;color:#6b7280;">人</span>'
                                . '</div>'
                            );
                        }),
                ])
                ->collapsible(),

            // ④ 配信予定日時
            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('配信予定日時')
                ->nullable()
                ->columnSpanFull(),

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

                            Forms\Components\DateTimePicker::make('coupon_expires_at')
                                ->label('有効期限（日時）')
                                ->helperText('この日時を過ぎるとクーポンは失効します')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('配信名')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Broadcast $r): string => self::buildFilterSummary($r)),
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
                Tables\Columns\TextColumn::make('claim_rate')
                    ->label('取得率')
                    ->getStateUsing(function (Broadcast $record) {
                        if ($record->sent_count <= 0 || $record->status !== 'sent') {
                            return '-';
                        }
                        $bubbleIds = DB::table('message_bubbles')
                            ->where('parent_type', 'broadcast')
                            ->where('parent_id', $record->id)
                            ->where('bubble_type', 'coupon')
                            ->pluck('id');
                        if ($bubbleIds->isEmpty()) {
                            return '-';
                        }
                        $claimed = DB::table('user_coupons')
                            ->whereIn('message_bubble_id', $bubbleIds)
                            ->count();
                        $rate = round(($claimed / $record->sent_count) * 100, 1);
                        return "{$claimed}/{$record->sent_count} ({$rate}%)";
                    }),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('分析')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Broadcast $record) => static::getUrl('view', ['record' => $record]))
                    ->visible(fn (Broadcast $record) => $record->status === 'sent'),
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

    private static function calcTargetCount(callable $get): int
    {
        try {
            $query = DB::table('users')->whereNotNull('line_user_id');

            $storeIds = $get('store_ids') ?? [];
            if (!empty($storeIds)) {
                $query->whereExists(fn ($q) =>
                    $q->from('visits')
                        ->whereColumn('visits.user_id', 'users.id')
                        ->whereIn('visits.store_id', $storeIds)
                );
            }

            $rankIds = $get('filter_rank_card_id') ?? [];
            if (!empty($rankIds)) {
                $query->whereIn('current_card_id', $rankIds);
            }

            $genders = $get('filter_gender') ?? [];
            if (!empty($genders)) {
                $query->whereIn('gender', $genders);
            }

            $months = $get('filter_birth_month') ?? [];
            if (!empty($months)) {
                $query->whereIn('birth_month', array_map('intval', $months));
            }

            $minVisits = $get('filter_min_visits');
            if (!empty($minVisits)) {
                $query->where('visit_count', '>=', (int) $minVisits);
            }

            $maxVisits = $get('filter_max_visits');
            if (!empty($maxVisits)) {
                $query->where('visit_count', '<=', (int) $maxVisits);
            }

            $daysSince = $get('filter_days_since_visit');
            if (!empty($daysSince)) {
                $cutoff = now()->subDays((int) $daysSince)->startOfDay();
                $query->whereNotNull('first_visit_at')
                      ->whereNotNull('last_visit_at')
                      ->where('last_visit_at', '<', $cutoff);
            }

            return $query->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function buildFilterSummary(Broadcast $r): string
    {
        $parts = [];

        $storeIds = is_array($r->store_ids) ? $r->store_ids : json_decode($r->store_ids ?? '[]', true);
        if (!empty($storeIds)) {
            $names = \App\Models\Store::whereIn('id', $storeIds)->pluck('name')->implode('・');
            $parts[] = "店舗: {$names}";
        } else {
            $parts[] = '全店舗';
        }

        $rankIds = is_string($r->filter_rank_card_id)
            ? json_decode($r->filter_rank_card_id, true)
            : (array) ($r->filter_rank_card_id ?? []);
        if (!empty($rankIds)) {
            $names = \App\Models\StampCardDefinition::whereIn('id', $rankIds)->pluck('display_name')->implode('・');
            $parts[] = "ランク: {$names}";
        }

        if (!empty($r->filter_min_visits) || !empty($r->filter_max_visits)) {
            $parts[] = "来店 {$r->filter_min_visits}〜{$r->filter_max_visits}回";
        }

        return implode('  |  ', $parts);
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
            'view' => Pages\ViewBroadcast::route('/{record}'),
        ];
    }
}
