<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LineSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'LINE設定';
    protected static ?string $navigationGroup = '設定';
    protected static ?string $title = 'LINE設定';
    protected static string $view = 'filament.pages.line-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'line_bot_channel_access_token' => AppSetting::get('line_bot_channel_access_token', config('services.line.bot_channel_access_token')),
            'line_bot_channel_secret'       => AppSetting::get('line_bot_channel_secret', config('services.line.bot_channel_secret')),
            'line_login_channel_id'         => AppSetting::get('line_login_channel_id', config('services.line.login_channel_id')),
            'line_bot_channel_id'           => AppSetting::get('line_bot_channel_id', config('services.line.bot_channel_id')),
            'liff_id'                       => AppSetting::get('liff_id', config('services.line.liff_id')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Messaging API（LINE OA）')
                    ->schema([
                        TextInput::make('line_bot_channel_access_token')
                            ->label('Channel Access Token')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(500),
                        TextInput::make('line_bot_channel_secret')
                            ->label('Channel Secret')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('line_bot_channel_id')
                            ->label('Bot Channel ID')
                            ->maxLength(255),
                    ]),
                Section::make('LINEログイン / ミニアプリ（LIFF）')
                    ->schema([
                        TextInput::make('line_login_channel_id')
                            ->label('LINEログイン Channel ID')
                            ->maxLength(255),
                        TextInput::make('liff_id')
                            ->label('LIFF ID')
                            ->required()
                            ->placeholder('1234567890-xxxxxxxx')
                            ->maxLength(255),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            AppSetting::set($key, $value, 'line');
        }
        // configも即時反映
        config([
            'services.line.bot_channel_access_token' => $data['line_bot_channel_access_token'],
            'services.line.bot_channel_secret'       => $data['line_bot_channel_secret'],
            'services.line.login_channel_id'         => $data['line_login_channel_id'],
            'services.line.bot_channel_id'           => $data['line_bot_channel_id'],
            'services.line.liff_id'                  => $data['liff_id'],
        ]);
        Notification::make()->title('設定を保存しました')->success()->send();
    }
}
