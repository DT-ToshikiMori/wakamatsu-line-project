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
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'LINE設定';
    protected static ?string $navigationGroup = 'システム設定';
    protected static ?string $title = 'LINE設定';
    protected static string $view = 'filament.pages.line-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $keys = [
            'line_channel_access_token',
            'line_channel_secret',
            'line_login_channel_id',
            'line_bot_channel_id',
            'liff_id',
        ];
        $this->data = [];
        foreach ($keys as $key) {
            $this->data[$key] = AppSetting::get($key, '');
        }
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Messaging API')
                    ->schema([
                        Forms\Components\TextInput::make('line_channel_access_token')
                            ->label('Channel Access Token')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(500),
                        Forms\Components\TextInput::make('line_channel_secret')
                            ->label('Channel Secret')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('line_bot_channel_id')
                            ->label('Bot Channel ID')
                            ->maxLength(255),
                    ]),
                Forms\Components\Section::make('LINEログイン / LIFF')
                    ->schema([
                        Forms\Components\TextInput::make('line_login_channel_id')
                            ->label('LINEログイン Channel ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('liff_id')
                            ->label('LIFF ID')
                            ->required()
                            ->maxLength(255)
                            ->helperText('例: 1234567890-AbCdEfGh'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $group = 'line';
        foreach ($state as $key => $value) {
            AppSetting::set($key, $value, $group);
        }
        Notification::make()->title('LINE設定を保存しました')->success()->send();
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
