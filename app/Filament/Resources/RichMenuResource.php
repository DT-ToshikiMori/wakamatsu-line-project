<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RichMenuResource\Pages;
use App\Models\RichMenu;
use App\Models\StampCardDefinition;
use App\Services\RichMenuService;
use App\Support\RichMenuTemplates;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class RichMenuResource extends Resource
{
    protected static ?string $model = RichMenu::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'メッセージ管理';
    protected static ?string $navigationLabel = 'リッチメニュー';
    protected static ?string $modelLabel = 'リッチメニュー';
    protected static ?string $pluralModelLabel = 'リッチメニュー';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ① 基本情報
            Forms\Components\Section::make('基本設定')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('管理名')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('chat_bar_text')
                        ->label('メニューバーテキスト')
                        ->required()
                        ->maxLength(50)
                        ->default('メニュー')
                        ->helperText('トーク画面下部でリッチメニューが閉じているときに表示されるバーのテキスト（例: メニュー・お得情報・クーポン）')
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('selected')
                        ->label('デフォルト展開')
                        ->helperText('ONにするとリッチメニューがデフォルトで展開された状態になります')
                        ->columnSpanFull(),

                    Forms\Components\Radio::make('target_stamp_card_definition_id')
                        ->label('表示対象ランク')
                        ->options(fn (): array => ['' => '共通（全ユーザー）'] + StampCardDefinition::query()
                            ->where('is_active', true)
                            ->orderBy('priority')
                            ->get()
                            ->mapWithKeys(fn (StampCardDefinition $card): array => [
                                $card->id => $card->display_name === $card->name
                                    ? $card->display_name
                                    : "{$card->display_name}（{$card->name}）",
                            ])
                            ->all())
                        ->default('')
                        ->afterStateHydrated(fn (Forms\Components\Radio $component, $state) => $component->state($state ?? ''))
                        ->dehydrateStateUsing(fn ($state) => blank($state) ? null : $state)
                        ->helperText('未選択の場合は共通メニューとして扱います。ランクを選ぶとチェックイン後や同期コマンドで対象ユーザーへ個別リンクされます。')
                        ->columnSpanFull(),
                ]),

            // ② テンプレート選択（画像カード）
            Forms\Components\Section::make('テンプレート')
                ->icon('heroicon-o-squares-2x2')
                ->schema([
                    // 画像ピッカー（Alpine.js + $wire.set）
                    Forms\Components\View::make('filament.forms.components.rich-menu-template-picker'),

                    // テンプレートキー（Hidden相当、liveでエリア自動設定）
                    Forms\Components\Hidden::make('template_key')
                        ->live()
                        ->afterStateUpdated(function (?string $state, Forms\Set $set) {
                            if (!$state) return;
                            $tpl = RichMenuTemplates::get($state);
                            if (!$tpl) return;
                            // size_type を自動設定
                            $set('size_type', $tpl['size_type']);
                            // エリアを自動生成
                            $set('areas', RichMenuTemplates::buildAreaRows($state));
                        }),

                    // size_type は自動設定（隠し）
                    Forms\Components\Hidden::make('size_type')->default('full'),
                ]),

            // ③ メニュー画像アップロード
            Forms\Components\Section::make('メニュー画像')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\FileUpload::make('image_path')
                        ->label('')
                        ->image()
                        ->disk('public')
                        ->directory('rich-menus')
                        ->helperText('フル: 2500×1686px / ハーフ: 2500×843px（PNG or JPEG）')
                        ->columnSpanFull(),
                ]),

            // ④ タップエリア設定
            Forms\Components\Section::make('タップエリア設定')
                ->description('テンプレートを選択すると自動生成されます。ラベル・アクションを編集してください。')
                ->icon('heroicon-o-cursor-arrow-rays')
                ->schema([
                    Forms\Components\Repeater::make('areas')
                        ->label('')
                        ->relationship()
                        ->schema([
                            Forms\Components\Grid::make(5)
                                ->schema([
                                    Forms\Components\TextInput::make('label')
                                        ->label('ラベル')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('action_type')
                                        ->label('アクション')
                                        ->options([
                                            'uri'      => 'URL（クリック計測あり）',
                                            'postback' => 'Postback（カスタム）',
                                            'message'  => 'メッセージ送信',
                                        ])
                                        ->default('uri')
                                        ->required()
                                        ->live()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('action_data')
                                        ->label(fn ($get) => match ($get('action_type')) {
                                            'uri'     => 'URL',
                                            'message' => 'テキスト',
                                            default   => 'Postbackデータ（空=自動）',
                                        })
                                        ->required(fn ($get) => in_array($get('action_type'), ['uri', 'message']))
                                        ->columnSpan(2),
                                ]),

                            // 座標（デフォルト非表示）
                            Forms\Components\Section::make('座標（自動設定済み）')
                                ->schema([
                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\TextInput::make('x')->label('X')->numeric()->required()->minValue(0),
                                            Forms\Components\TextInput::make('y')->label('Y')->numeric()->required()->minValue(0),
                                            Forms\Components\TextInput::make('width')->label('幅')->numeric()->required()->minValue(1),
                                            Forms\Components\TextInput::make('height')->label('高さ')->numeric()->required()->minValue(1),
                                        ]),
                                ])
                                ->collapsed()
                                ->collapsible()
                                ->compact(),
                        ])
                        ->orderColumn('position')
                        ->maxItems(20)
                        ->defaultItems(0)
                        ->addActionLabel('エリアを手動追加')
                        ->reorderable(true)
                        ->collapsible()
                        ->itemLabel(fn (array $state) => $state['label'] ?? 'エリア'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('管理名')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('ステータス')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => '下書き',
                        'synced' => '同期済み',
                        'active' => '有効',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'gray',
                        'synced' => 'info',
                        'active' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('デフォルト')
                    ->boolean(),

                Tables\Columns\TextColumn::make('targetStampCardDefinition.display_name')
                    ->label('表示対象')
                    ->formatStateUsing(fn (?string $state) => $state ?: '共通')
                    ->placeholder('共通')
                    ->badge()
                    ->color(fn (RichMenu $record) => $record->target_stamp_card_definition_id ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('areas_count')
                    ->label('エリア数')
                    ->counts('areas'),

                Tables\Columns\TextColumn::make('total_clicks')
                    ->label('総クリック数')
                    ->getStateUsing(fn (RichMenu $record) => DB::table('rich_menu_clicks')
                        ->join('rich_menu_areas', 'rich_menu_areas.id', '=', 'rich_menu_clicks.rich_menu_area_id')
                        ->where('rich_menu_areas.rich_menu_id', $record->id)
                        ->count()
                    ),

                Tables\Columns\TextColumn::make('synced_at')
                    ->label('最終同期')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('-'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('分析')
                    ->icon('heroicon-o-chart-bar')
                    ->iconButton()
                    ->tooltip('分析')
                    ->url(fn (RichMenu $record) => static::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('sync')
                    ->label('LINEに同期')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->iconButton()
                    ->tooltip('LINEに同期')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('LINE同期確認')
                    ->modalDescription('このリッチメニューをLINE APIに同期しますか？')
                    ->action(function (RichMenu $record) {
                        $service = app(RichMenuService::class);
                        if ($service->syncToLine($record)) {
                            Notification::make()
                                ->title('LINE同期完了')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('LINE同期に失敗しました')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('setDefault')
                    ->label('デフォルトに設定')
                    ->icon('heroicon-o-star')
                    ->iconButton()
                    ->tooltip('デフォルトに設定')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('デフォルト設定確認')
                    ->modalDescription('この共通リッチメニューを全ユーザーのデフォルトに設定しますか？')
                    ->visible(fn (RichMenu $record) => $record->line_rich_menu_id && !$record->is_default && !$record->target_stamp_card_definition_id)
                    ->action(function (RichMenu $record) {
                        $service = app(RichMenuService::class);
                        if ($service->setDefault($record->line_rich_menu_id)) {
                            // 他のデフォルトを解除
                            RichMenu::where('id', '!=', $record->id)->update([
                                'is_default' => false,
                                'status' => DB::raw("CASE WHEN status = 'active' THEN 'synced' ELSE status END"),
                            ]);
                            $record->update([
                                'is_default' => true,
                                'status' => 'active',
                            ]);
                            Notification::make()
                                ->title('デフォルトに設定しました')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('デフォルト設定に失敗しました')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('編集'),
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
            'index' => Pages\ListRichMenus::route('/'),
            'create' => Pages\CreateRichMenu::route('/create'),
            'edit' => Pages\EditRichMenu::route('/{record}/edit'),
            'view' => Pages\ViewRichMenu::route('/{record}'),
        ];
    }
}
