<?php

namespace App\Filament\Resources\BroadcastResource\Pages;

use App\Filament\Resources\BroadcastResource;
use App\Models\Broadcast;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class ViewBroadcast extends Page
{
    protected static string $resource = BroadcastResource::class;

    protected static string $view = 'filament.resources.broadcast-resource.pages.view-broadcast';

    protected static ?string $title = '配信分析';

    public Broadcast $record;

    public function mount(int | string $record): void
    {
        $this->record = Broadcast::findOrFail($record);
    }

    protected function getViewData(): array
    {
        $broadcast = $this->record;

        // バブル別の分析データ
        $bubbles = DB::table('message_bubbles as mb')
            ->where('mb.parent_type', 'broadcast')
            ->where('mb.parent_id', $broadcast->id)
            ->leftJoin('coupon_templates as ct', 'ct.id', '=', 'mb.coupon_template_id')
            ->select([
                'mb.id',
                'mb.position',
                'mb.bubble_type',
                'mb.text_content',
                'ct.title as coupon_title',
            ])
            ->orderBy('mb.position')
            ->get();

        $bubbleStats = $bubbles->map(function ($bubble) use ($broadcast) {
            $stats = (object) [
                'id' => $bubble->id,
                'position' => $bubble->position + 1,
                'type' => $bubble->bubble_type,
                'label' => $bubble->bubble_type === 'coupon'
                    ? ($bubble->coupon_title ?? 'クーポン')
                    : mb_substr($bubble->text_content ?? 'テキスト', 0, 30),
                'claimed' => 0,
                'claimRate' => 0.0,
                'used' => 0,
                'usageRate' => 0.0,
            ];

            if ($bubble->bubble_type === 'coupon') {
                $claimed = DB::table('user_coupons')
                    ->where('message_bubble_id', $bubble->id)
                    ->count();

                $used = DB::table('user_coupons')
                    ->where('message_bubble_id', $bubble->id)
                    ->where(function ($q) {
                        $q->whereNotNull('used_at')->orWhere('status', 'used');
                    })
                    ->count();

                $stats->claimed = $claimed;
                $stats->claimRate = $broadcast->sent_count > 0
                    ? round(($claimed / $broadcast->sent_count) * 100, 1)
                    : 0.0;
                $stats->used = $used;
                $stats->usageRate = $claimed > 0
                    ? round(($used / $claimed) * 100, 1)
                    : 0.0;
            }

            return $stats;
        });

        return [
            'broadcast' => $broadcast,
            'bubbleStats' => $bubbleStats,
        ];
    }
}
