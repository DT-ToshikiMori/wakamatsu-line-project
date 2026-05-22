<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitScenarioResource\Pages;
use App\Models\CouponTemplate;
use App\Models\StampCardDefinition;
use App\Models\VisitScenario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VisitScenarioResource extends Resource
{
    protected static ?string $model = VisitScenario::class;

    protected static ?string $navigationIcon   = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup  = 'メッセージ管理';
    protected static ?string $navigationLabel  = '来店シナリオメッセージ';
    protected static ?int    $navigationSort   = 1;
    protected static ?string $modelLabel       = '来店シナリオ';
    protected static ?string $pluralModelLabel = '来店シナリオ';

    // ─────────────────────────────────────────────────────────────
    // フォーム
    // ─────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\TextInput::make('name')
                ->label('シナリオ名（管理用）')
                ->placeholder('例: 初回来店クーポン')
                ->columnSpanFull()
                ->nullable(),

            Forms\Components\Select::make('stamp_card_definition_id')
                ->label('対象スタンプカード')
                ->options(StampCardDefinition::pluck('display_name', 'id'))
                ->searchable()
                ->required()
                ->columnSpanFull(),

            // ① いつ発火するか
            Forms\Components\Section::make('① いつ発火するか')
                ->description('このシナリオが動き出すタイミングを設定します。')
                ->icon('heroicon-o-bolt')
                ->schema([
                    Forms\Components\Select::make('trigger_type')
                        ->label('発火タイミング')
                        ->options([
                            'checkin'        => '来店スタンプ時（チェックイン後すぐ）',
                            'migration'      => 'LINE移行時（友だち追加・0回目）',
                            'after_days'     => '最終来店からN日後（離脱防止）',
                            'birthday_month' => '誕生月配信（毎月1日に一斉送信）',
                        ])
                        ->default('checkin')
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    // after_days 設定
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('trigger_days')
                                ->label('最終来店からX日後')
                                ->numeric()
                                ->minValue(1)
                                ->suffix('日後')
                                ->nullable(),

                            Forms\Components\TextInput::make('send_hour')
                                ->label('送信時刻')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(23)
                                ->suffix('時')
                                ->nullable(),
                        ])
                        ->hidden(fn ($get) => $get('trigger_type') !== 'after_days'),

                    // birthday_month 設定
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('send_hour')
                                ->label('送信時刻（毎月1日）')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(23)
                                ->default(10)
                                ->suffix('時')
                                ->nullable()
                                ->helperText('毎月1日のこの時刻に誕生月ユーザーへ一斉送信'),

                            Forms\Components\Placeholder::make('birthday_note')
                                ->label('対象ユーザー')
                                ->content('その月が誕生月として登録されているユーザーが対象です。'),
                        ])
                        ->hidden(fn ($get) => $get('trigger_type') !== 'birthday_month'),

                    Forms\Components\TextInput::make('delay_hours')
                        ->label('発火からX時間後に送信')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('時間後')
                        ->required()
                        ->hidden(fn ($get) => in_array($get('trigger_type'), ['after_days', 'birthday_month']))
                        ->helperText('0 = 即時送信'),
                ]),

            // ② 誰に送るか（来店回数条件）
            Forms\Components\Section::make('② 誰に送るか（来店回数）')
                ->description('何回目の来店のユーザーに送るかを設定します。空欄は「制限なし」扱いです。')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('visit_count_min')
                                ->label('最小（N回目以上）')
                                ->numeric()
                                ->minValue(0)
                                ->suffix('回目〜')
                                ->nullable()
                                ->helperText('例: 1 → 1回目以上'),

                            Forms\Components\TextInput::make('visit_count_max')
                                ->label('最大（空欄=上限なし）')
                                ->numeric()
                                ->minValue(0)
                                ->suffix('回目まで')
                                ->nullable()
                                ->helperText('空欄 = 上限なし'),

                            Forms\Components\Toggle::make('repeat')
                                ->label('条件を満たすたびに毎回発火')
                                ->default(false)
                                ->helperText('OFFにすると初回のみ'),
                        ]),

                    Forms\Components\Placeholder::make('visit_count_example')
                        ->label('設定例')
                        ->content(
                            '• 1回目のみ: 最小=1, 最大=1, 毎回=OFF' . "\n" .
                            '• 2〜3回目: 最小=2, 最大=3, 毎回=ON' . "\n" .
                            '• 4回目以降ずっと: 最小=4, 最大=空欄, 毎回=ON'
                        ),
                ]),

            // ③ メッセージ内容
            Forms\Components\Section::make('③ メッセージ内容')
                ->description('LINEで送信するメッセージを設定します。テキストとクーポンを1〜3つ組み合わせられます。')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->schema([
                    Forms\Components\Repeater::make('bubbles')
                        ->label('バブル（メッセージのかたまり）')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('bubble_type')
                                ->label('種別')
                                ->options([
                                    'text'   => 'テキスト',
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
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        $coupon = CouponTemplate::find($state);
                                        $set('coupon_template_title', $coupon?->title ?? '');
                                        $set('coupon_template_note', $coupon?->note ?? '');
                                        $set('coupon_template_image_url', CouponTemplate::resolveImageUrl($coupon?->image_url) ?? '');
                                    }
                                })
                                ->visible(fn ($get) => $get('bubble_type') === 'coupon'),

                            Forms\Components\TextInput::make('coupon_expires_days')
                                ->label('クーポン有効期限')
                                ->numeric()
                                ->minValue(1)
                                ->suffix('日間')
                                ->nullable()
                                ->helperText('空欄 = 無期限')
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

                    // LINE公式風プレビュー
                    Forms\Components\View::make('filament.forms.components.bubble-preview')
                        ->label('プレビュー'),
                ]),

            // ④ リマインド（クーポンバブルがある場合のみ表示）
            Forms\Components\Section::make('④ リマインド（有効期限前の通知）')
                ->description('クーポンの期限が近づいたら自動でリマインドを送ります。複数タイミングで設定できます。')
                ->icon('heroicon-o-bell')
                ->hidden(function ($get): bool {
                    foreach (array_values($get('bubbles') ?? []) as $bubble) {
                        if (($bubble['bubble_type'] ?? '') === 'coupon') {
                            return false;
                        }
                    }
                    return true;
                })
                ->schema([
                    Forms\Components\Repeater::make('reminders')
                        ->label('リマインド設定')
                        ->relationship()
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('before_days')
                                        ->label('期限の何日前')
                                        ->numeric()
                                        ->minValue(1)
                                        ->suffix('日前')
                                        ->required(),

                                    Forms\Components\TextInput::make('send_hour')
                                        ->label('送信時刻')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(23)
                                        ->default(10)
                                        ->suffix('時')
                                        ->required(),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('有効')
                                        ->default(true)
                                        ->inline(false),
                                ]),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('+ リマインドを追加')
                        ->reorderable(false),

                    // リマインドプレビュー
                    Forms\Components\View::make('filament.forms.components.reminder-preview')
                        ->label('リマインドプレビュー'),
                ])
                ->collapsible(),

            Forms\Components\Toggle::make('is_active')
                ->label('このシナリオを有効にする')
                ->default(true)
                ->columnSpanFull(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // テーブル
    // ─────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_active')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->width('40px'),

                Tables\Columns\TextColumn::make('name')
                    ->label('シナリオ名')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (VisitScenario $r): string => self::buildSummary($r))
                    ->wrap(),

                Tables\Columns\TextColumn::make('stampCardDefinition.display_name')
                    ->label('対象カード')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('couponTemplate.title')
                    ->label('クーポン')
                    ->searchable(),

                Tables\Columns\TextColumn::make('expires_days')
                    ->label('期限')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}日" : '無期限')
                    ->color(fn ($state) => $state ? 'warning' : 'gray'),

                Tables\Columns\IconColumn::make('reminder_enabled')
                    ->label('通知')
                    ->boolean()
                    ->trueIcon('heroicon-o-bell')
                    ->falseIcon('heroicon-o-bell-slash')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('有効'),
            ])
            ->defaultSort('stamp_card_definition_id')
            ->groups([
                Tables\Grouping\Group::make('stampCardDefinition.display_name')
                    ->label('スタンプカード')
                    ->collapsible(),
            ])
            ->actions([
                Tables\Actions\Action::make('analytics')
                    ->label('分析')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->url(fn (VisitScenario $r) => static::getUrl('view', ['record' => $r])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ─────────────────────────────────────────────────────────────
    // サマリー文生成（テーブルのdescription用）
    // ─────────────────────────────────────────────────────────────
    private static function buildSummary(VisitScenario $r): string
    {
        // トリガー
        $trigger = match ($r->trigger_type) {
            'migration'      => 'LINE移行時',
            'after_days'     => "最終来店から{$r->trigger_days}日後 {$r->send_hour}時",
            'birthday_month' => "誕生月配信（毎月1日 {$r->send_hour}時）",
            default          => '来店スタンプ時',
        };

        // 来店回数条件
        if ($r->visit_count_min !== null) {
            $max     = $r->visit_count_max !== null ? "{$r->visit_count_max}回目" : '以降';
            $count   = "{$r->visit_count_min}〜{$max}";
            $repeat  = $r->repeat ? '（毎回）' : '（初回のみ）';
            $who     = "{$count}{$repeat}";
        } else {
            $who = '全員';
        }

        // 遅延
        $delay = ($r->delay_hours ?? 0) > 0 ? " → {$r->delay_hours}h後に送信" : '';

        return "{$trigger}  |  {$who}{$delay}";
    }

    // ─────────────────────────────────────────────────────────────
    // ページ
    // ─────────────────────────────────────────────────────────────
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVisitScenarios::route('/'),
            'create' => Pages\CreateVisitScenario::route('/create'),
            'edit'   => Pages\EditVisitScenario::route('/{record}/edit'),
            'view'   => Pages\ViewVisitScenario::route('/{record}/analytics'),
        ];
    }
}
