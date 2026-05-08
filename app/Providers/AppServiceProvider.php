<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'broadcast' => \App\Models\Broadcast::class,
            'churn_scenario' => \App\Models\ChurnScenario::class,
        ]);

        // DB の LINE 設定を config に上書き（設定されている場合のみ）
        try {
            $keys = [
                'line_channel_access_token' => 'services.line.bot_channel_access_token',
                'line_channel_secret'       => 'services.line.bot_channel_secret',
                'line_login_channel_id'     => 'services.line.login_channel_id',
                'line_bot_channel_id'       => 'services.line.bot_channel_id',
                'liff_id'                   => 'services.line.liff_id',
            ];
            foreach ($keys as $settingKey => $configKey) {
                $val = \App\Models\AppSetting::get($settingKey);
                if ($val) {
                    config([$configKey => $val]);
                }
            }
        } catch (\Throwable $e) {
            // DBが未準備の場合は無視
        }
    }
}
