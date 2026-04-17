<?php

namespace App\Console\Commands;

use App\Models\CouponTemplate;
use App\Services\LineBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendVisitScenarioBatch extends Command
{
    protected $signature = 'visit-scenario:send';
    protected $description = '来店シナリオの通知をバッチ送信（5分毎実行）';

    public function handle(LineBotService $lineBot): int
    {
        // scheduled_at <= now AND sent_at IS NULL のレコードを取得
        $pending = DB::table('visit_scenario_sends')
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at')
            ->get();

        if ($pending->isEmpty()) {
            return self::SUCCESS;
        }

        // scenario_id でグループ化
        $grouped = $pending->groupBy('scenario_id');

        foreach ($grouped as $scenarioId => $sends) {
            $this->processScenarioGroup($lineBot, (int) $scenarioId, $sends);
        }

        return self::SUCCESS;
    }

    private function processScenarioGroup(LineBotService $lineBot, int $scenarioId, $sends): void
    {
        $scenario = DB::table('visit_scenarios')
            ->where('id', $scenarioId)
            ->first();

        if (!$scenario) {
            Log::warning('SendVisitScenarioBatch: scenario not found', ['scenario_id' => $scenarioId]);
            return;
        }

        $tpl = DB::table('coupon_templates')
            ->where('id', $scenario->coupon_template_id)
            ->where('is_active', true)
            ->first();

        if (!$tpl) {
            Log::warning('SendVisitScenarioBatch: coupon template not found or inactive', [
                'scenario_id' => $scenarioId,
                'coupon_template_id' => $scenario->coupon_template_id,
            ]);
            return;
        }

        // user_id リストから line_user_id を取得
        $userIds = $sends->pluck('user_id')->unique()->values()->all();
        $users = DB::table('users')
            ->whereIn('id', $userIds)
            ->whereNotNull('line_user_id')
            ->pluck('line_user_id', 'id'); // [user_id => line_user_id]

        if ($users->isEmpty()) {
            return;
        }

        // Flexメッセージ構築
        $liffId = config('services.line.liff_id');
        $liffUrl = "https://liff.line.me/{$liffId}/coupons?scenario_id={$scenarioId}&ts=" . now()->timestamp;

        $imageUrl = CouponTemplate::resolveImageUrl($tpl->image_url);
        $expiresText = $scenario->expires_days
            ? "取得から{$scenario->expires_days}日間有効"
            : null;

        $flexContents = $this->buildFlexBubble($tpl->title, $tpl->note ?? '', $imageUrl, $expiresText, $liffUrl);

        $messages = [
            [
                'type' => 'flex',
                'altText' => "クーポンが届きました: {$tpl->title}",
                'contents' => $flexContents,
            ],
        ];

        // line_user_id を 500件ずつ multicast
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
                Log::error('SendVisitScenarioBatch: multicast failed', [
                    'scenario_id' => $scenarioId,
                    'chunk_size' => count($chunk),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 送信成功: sent_at を更新
        $sendIds = $sends->pluck('id')->all();
        DB::table('visit_scenario_sends')
            ->whereIn('id', $sendIds)
            ->update(['sent_at' => now(), 'updated_at' => now()]);

        $this->info("Scenario #{$scenarioId} '{$tpl->title}': multicast to {$sentCount} users ({$sends->count()} sends)");
    }

    private function buildFlexBubble(string $title, string $note, ?string $imageUrl, ?string $expiresText, string $claimUrl): array
    {
        $bodyContents = [
            [
                'type' => 'text',
                'text' => $title,
                'weight' => 'bold',
                'size' => 'xl',
            ],
        ];

        $detailRows = [];

        if ($expiresText) {
            $detailRows[] = [
                'type' => 'box',
                'layout' => 'baseline',
                'spacing' => 'sm',
                'contents' => [
                    ['type' => 'text', 'text' => '有効期限', 'color' => '#aaaaaa', 'size' => 'sm', 'flex' => 2],
                    ['type' => 'text', 'text' => $expiresText, 'wrap' => true, 'color' => '#666666', 'size' => 'sm', 'flex' => 5],
                ],
            ];
        }

        if ($note) {
            $detailRows[] = [
                'type' => 'box',
                'layout' => 'baseline',
                'spacing' => 'sm',
                'contents' => [
                    ['type' => 'text', 'text' => '備考', 'color' => '#aaaaaa', 'size' => 'sm', 'flex' => 2],
                    ['type' => 'text', 'text' => $note, 'wrap' => true, 'color' => '#666666', 'size' => 'sm', 'flex' => 5],
                ],
            ];
        }

        if (!empty($detailRows)) {
            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => 'lg',
                'spacing' => 'sm',
                'contents' => $detailRows,
            ];
        }

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
                        'style' => 'link',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => 'クーポンを受け取る',
                            'uri' => $claimUrl,
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
