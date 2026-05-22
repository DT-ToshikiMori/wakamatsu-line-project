<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LineSettings extends Page
{
    protected static ?string $navigationIcon    = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel   = 'LINE設定';
    protected static ?string $navigationGroup   = 'システム設定';
    protected static ?string $title             = 'LINE設定';
    protected static ?int    $navigationSort    = 10;
    protected static string  $view              = 'filament.pages.line-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $keys = [
            'line_channel_access_token',
            'line_channel_secret',
            'line_bot_channel_id',
            'line_login_channel_id',
            'liff_id',
        ];
        $this->data = [];
        foreach ($keys as $key) {
            $this->data[$key] = AppSetting::get($key, '');
        }

        // LIFF URL はLIFF IDから自動計算（保存対象外）
        $this->data['liff_url_preview'] = $this->data['liff_id']
            ? 'https://liff.line.me/' . $this->data['liff_id']
            : '';

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // ─── Messaging API ───────────────────────────────────────────
                Forms\Components\Section::make('Messaging API')
                    ->description('LINE公式アカウントのBot設定。LINE Developersコンソール > Messaging APIチャネルで確認できます。')
                    ->schema([
                        Forms\Components\TextInput::make('line_channel_access_token')
                            ->label('Channel Access Token')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(500)
                            ->helperText('Messaging API設定 > チャネルアクセストークン（長期）'),
                        Forms\Components\TextInput::make('line_channel_secret')
                            ->label('Channel Secret')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255)
                            ->helperText('チャネル基本設定 > チャネルシークレット'),
                        Forms\Components\TextInput::make('line_bot_channel_id')
                            ->label('Bot チャネルID')
                            ->maxLength(255)
                            ->helperText('チャネル基本設定 > チャネルID'),
                    ]),

                // ─── LINEミニアプリ / LIFF ───────────────────────────────────
                Forms\Components\Section::make('LINEミニアプリ / LIFF')
                    ->description('LINEミニアプリ（LIFF）の設定。LINE Developersコンソール > LINEログインチャネルで確認できます。')
                    ->schema([
                        Forms\Components\TextInput::make('line_login_channel_id')
                            ->label('LINEログイン チャネルID')
                            ->maxLength(255)
                            ->helperText('チャネル基本設定 > チャネルID。LIFFトークン検証に使用します。'),
                        Forms\Components\TextInput::make('liff_id')
                            ->label('LIFF ID')
                            ->required()
                            ->maxLength(255)
                            ->helperText('例: 1234567890-AbCdEfGh　（LIFFアプリ管理 > LIFF ID）')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                $set('liff_url_preview', $state ? 'https://liff.line.me/' . $state : '');
                            }),
                        Forms\Components\TextInput::make('liff_url_preview')
                            ->label('LIFF URL（自動生成）')
                            ->readOnly()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('このURLがLINEミニアプリのエントリーポイントです。LINE DevelopersコンソールのエンドポイントURLに登録してください。')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('copy_liff_url')
                                    ->label('コピー')
                                    ->icon('heroicon-o-clipboard')
                                    ->action(fn () => null) // クリップボードコピーはJSで処理（Filamentの標準動作）
                                    ->extraAttributes([
                                        'x-on:click' => 'navigator.clipboard.writeText($el.closest(\'[data-field]\')?.querySelector(\'input\')?.value ?? \'\')',
                                    ])
                            ),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $group = 'line';

        // liff_url_preview は計算値なので保存しない
        unset($state['liff_url_preview']);

        foreach ($state as $key => $value) {
            AppSetting::set($key, $value ?? '', $group);
        }

        // config を即時反映（再起動なしで適用）
        $configMap = [
            'line_channel_access_token' => 'services.line.bot_channel_access_token',
            'line_channel_secret'       => 'services.line.bot_channel_secret',
            'line_login_channel_id'     => 'services.line.login_channel_id',
            'line_bot_channel_id'       => 'services.line.bot_channel_id',
            'liff_id'                   => 'services.line.liff_id',
        ];
        foreach ($configMap as $settingKey => $configKey) {
            if (!empty($state[$settingKey])) {
                config([$configKey => $state[$settingKey]]);
            }
        }

        Notification::make()
            ->title('LINE設定を保存しました')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('保存する')
                ->submit('save'),
        ];
    }
}
