<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->brandName('WAKAMATSU')
            ->path('admin')
            ->login()
            ->authGuard('admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make('メッセージ管理'),
                NavigationGroup::make('スタンプ管理'),
                NavigationGroup::make('クーポン管理'),
                NavigationGroup::make('顧客管理'),
                NavigationGroup::make('店舗管理'),
                NavigationGroup::make('分析'),
                NavigationGroup::make('システム設定')
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                // 来店サマリー
                \App\Filament\Widgets\KpiOverview::class,
                \App\Filament\Widgets\VisitsTrend::class,

                // 分析
                \App\Filament\Widgets\ReturnRateAnalysis::class,
                \App\Filament\Widgets\AttributeAnalysis::class,

                // クーポン状況（今月）
                \App\Filament\Widgets\CouponKpi::class,
                \App\Filament\Widgets\CouponTemplateRanking::class,

                // リッチメニュー
                \App\Filament\Widgets\RichMenuClicksKpi::class,
                \App\Filament\Widgets\RichMenuClicksChart::class,

                // 店舗サマリー
                \App\Filament\Widgets\StoreRanking::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
