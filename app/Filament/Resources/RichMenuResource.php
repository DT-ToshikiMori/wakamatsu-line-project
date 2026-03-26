<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RichMenuResource\Pages;
use App\Models\RichMenu;
use App\Services\RichMenuService;
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
            Forms\Components\TextInput::make('name')
                ->label('管理名')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('chat_bar_text')
                ->label('チャットバーテキスト')
                ->required()
                ->maxLength(50)
                ->helperText('チャット画面下部に表示されるテキスト（最大50文字）'),

            Forms\Components\Select::make('size_type')
                ->label('サイズ')
                ->options([
                    'full' => 'フル（2500×1686）',
                    'half' => 'ハーフ（2500×843）',
                ])
                ->default('full')
                ->required(),

            Forms\Components\Toggle::make('selected')
                ->label('デフォルト展開')
                ->helperText('ONにするとリッチメニューがデフォルトで展開された状態になります'),

            Forms\Components\FileUpload::make('image_path')
                ->label('メニュー画像')
                ->image()
                ->disk('public')
                ->directory('rich-menus')
                ->helperText('フル: 2500×1686px / ハーフ: 2500×843px（PNG or JPEG）'),

            // エリア設定
            Forms\Components\Section::make('タップエリア設定')
                ->description('リッチメニュー画像上のタップ領域を設定（最大20個）')
                ->schema([
                    Forms\Components\Repeater::make('areas')
                        ->label('エリア')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('label')
                                ->label('ラベル')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Grid::make(4)
                                ->schema([
                                    Forms\Components\TextInput::make('x')
                                        ->label('X座標')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0),
                                    Forms\Components\TextInput::make('y')
                                        ->label('Y座標')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0),
                                    Forms\Components\TextInput::make('width')
                                        ->label('幅')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1),
                                    Forms\Components\TextInput::make('height')
                                        ->label('高さ')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1),
                                ]),

                            Forms\Components\Select::make('action_type')
                                ->label('アクション種別')
                                ->options([
                                    'postback' => 'Postback（クリック計測可能）',
                                    'uri' => 'URI（外部リンク）',
                                    'message' => 'メッセージ送信',
                                ])
                                ->default('postback')
                                ->required()
                                ->live(),

                            Forms\Components\TextInput::make('action_data')
                                ->label(fn ($get) => match ($get('action_type')) {
                                    'uri' => 'URL',
                                    'message' => 'メッセージテキスト',
                                    default => 'Postbackデータ（空欄で自動生成）',
                                })
                                ->helperText(fn ($get) => $get('action_type') === 'postback'
                                    ? '空欄の場合 action=richmenu_click&area_id={id} が自動設定されます'
                                    : null)
                                ->required(fn ($get) => in_array($get('action_type'), ['uri', 'message'])),
                        ])
                        ->orderColumn('position')
                        ->maxItems(20)
                        ->defaultItems(0)
                        ->addActionLabel('エリアを追加')
                        ->reorderable(true),
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
                    ->url(fn (RichMenu $record) => static::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('sync')
                    ->label('LINEに同期')
                    ->icon('heroicon-o-arrow-up-tray')
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
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('デフォルト設定確認')
                    ->modalDescription('このリッチメニューを全ユーザーのデフォルトに設定しますか？')
                    ->visible(fn (RichMenu $record) => $record->line_rich_menu_id && !$record->is_default)
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
            'index' => Pages\ListRichMenus::route('/'),
            'create' => Pages\CreateRichMenu::route('/create'),
            'edit' => Pages\EditRichMenu::route('/{record}/edit'),
            'view' => Pages\ViewRichMenu::route('/{record}'),
        ];
    }
}
