<?php

namespace App\Console\Commands;

use App\Models\CouponTemplate;
use App\Services\LineBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendCouponReminderBatch extends Command
{
    protected $signature = 'coupon:remind';
    protected $description = '期限が近いクーポンのリマインド通知をバッチ送信';

    public function handle(LineBotService $lineBot): int
    {
        $templates = DB::table('coupon_templates')
            ->whereNotNull('reminder_hours_before_expiry')
            ->where('is_active', true)
            ->get();

        if ($templates->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($templates as $tpl) {
            $this->processTemplate($lineBot, $tpl);
        }

        return self::SUCCESS;
    }

    private function processTemplate(LineBotService $lineBot, object $tpl): void
    {
        $now = now();
        $threshold = $now->copy()->addHours($tpl->reminder_hours_before_expiry);

        // expires_at が now〜now+reminder_hours のクーポンで、まだリマインド未送信
        $coupons = DB::table('user_coupons')
            ->where('coupon_template_id', $tpl->id)
            ->where('status', 'issued')
            ->whereNull('reminder_sent_at')
            ->where('expires_at', '>=', $now)
            ->where('expires_at', '<=', $threshold)
            ->get();

        if ($coupons->isEmpty()) {
            return;
        }

        // user_id → line_user_id を取得
        $userIds = $coupons->pluck('user_id')->unique()->values()->all();
        $users = DB::table('users')
            ->whereIn('id', $userIds)
            ->whereNotNull('line_user_id')
            ->pluck('line_user_id', 'id');

        if ($users->isEmpty()) {
            return;
        }

        // Flexメッセージ構築
        $liffId = config('services.line.liff_id');
        $liffUrl = "https://liff.line.me/{$liffId}/coupons?remind=1";

        $imageUrl = CouponTemplate::resolveImageUrl($tpl->image_url);
        $flexContents = $this->buildFlexBubble($tpl->title, $tpl->reminder_hours_before_expiry, $imageUrl, $liffUrl);

        $messages = [
            [
                'type' => 'flex',
                'altText' => "クーポンの期限が近づいています: {$tpl->title}",
                'contents' => $flexContents,
            ],
        ];

        // 500件ずつ multicast
        $lineUserIds = $users->values()->all();
        $chunks = array_chunk($lineUserIds, 500);
        $sentCount = 0;

        foreach ($chunks as $chunk) {
            try {
                $success = $lineBot->multicast($chunk, $messages);
                if ($success) {
                    $sentCount += count($chunk);
                }
            } catch (\Throwable $e) {
                Log::error('SendCouponReminderBatch: multicast failed', [
                    'coupon_template_id' => $tpl->id,
                    'chunk_size' => count($chunk),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // reminder_sent_at を更新
        $couponIds = $coupons->pluck('id')->all();
        DB::table('user_coupons')
            ->whereIn('id', $couponIds)
            ->update(['reminder_sent_at' => now()]);

        $this->info("Coupon #{$tpl->id} '{$tpl->title}': reminded {$sentCount} users ({$coupons->count()} coupons)");
    }

    private function buildFlexBubble(string $title, int $hoursBefore, ?string $imageUrl, string $url): array
    {
        $bodyContents = [
            [
                'type' => 'text',
                'text' => 'クーポンの期限が近づいています',
                'weight' => 'bold',
                'size' => 'lg',
                'color' => '#e74c3c',
            ],
            [
                'type' => 'text',
                'text' => $title,
                'weight' => 'bold',
                'size' => 'xl',
                'margin' => 'md',
            ],
            [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => 'lg',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'box',
                        'layout' => 'baseline',
                        'spacing' => 'sm',
                        'contents' => [
                            ['type' => 'text', 'text' => '残り', 'color' => '#aaaaaa', 'size' => 'sm', 'flex' => 2],
                            ['type' => 'text', 'text' => "{$hoursBefore}時間以内に期限切れ", 'wrap' => true, 'color' => '#e74c3c', 'size' => 'sm', 'flex' => 5],
                        ],
                    ],
                ],
            ],
        ];

        $bubble = [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => $bodyContents,
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#e74c3c',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => 'クーポンを確認する',
                            'uri' => $url,
                        ],
                    ],
                ],
                'flex' => 0,
            ],
        ];

        if ($imageUrl) {
            $bubble['hero'] = [
                'type' => 'image',
                'url' => $imageUrl,
                'size' => 'full',
                'aspectRatio' => '7:3',
                'aspectMode' => 'cover',
            ];
        }

        return $bubble;
    }
}
