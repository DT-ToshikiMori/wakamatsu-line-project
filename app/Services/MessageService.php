<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageService
{
    protected LineBotService $lineBotService;
    protected LotteryService $lotteryService;

    public function __construct(LineBotService $lineBotService, LotteryService $lotteryService)
    {
        $this->lineBotService = $lineBotService;
        $this->lotteryService = $lotteryService;
    }

    /**
     * バブル配列を元にユーザーへメッセージ送信
     *
     * @param int $userId users.id
     * @param array $bubbles message_bubbles のレコード配列
     * @param string $triggerType 'inactive' or 'manual'
     */
    public function sendToUser(int $userId, array $bubbles, string $triggerType = 'manual'): void
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user || empty($user->line_user_id)) {
            Log::warning('MessageService: user not found or no line_user_id', ['user_id' => $userId]);
            return;
        }

        foreach ($bubbles as $bubble) {
            try {
                if ($bubble->bubble_type === 'text') {
                    $this->sendTextBubble($user, $bubble);
                } elseif ($bubble->bubble_type === 'coupon') {
                    $this->sendCouponBubble($user, $bubble, $triggerType);
                }
            } catch (\Throwable $e) {
                Log::error('MessageService: bubble send failed', [
                    'user_id' => $userId,
                    'bubble_id' => $bubble->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendTextBubble(object $user, object $bubble): void
    {
        if (empty($bubble->text_content)) {
            return;
        }

        $this->lineBotService->pushText($user->line_user_id, $bubble->text_content);
    }

    private function sendCouponBubble(object $user, object $bubble, string $triggerType): void
    {
        if (empty($bubble->coupon_template_id)) {
            return;
        }

        $tpl = DB::table('coupon_templates')
            ->where('id', $bubble->coupon_template_id)
            ->where('is_active', true)
            ->first();

        if (!$tpl) {
            return;
        }

        $now = now();

        if (($tpl->mode ?? 'normal') === 'lottery') {
            // 抽選実行
            $result = $this->lotteryService->draw($user->store_id, $user->id, $tpl->id, $triggerType);

            if ($result['is_win']) {
                $prize = $result['prize'];
                $this->lineBotService->pushFlexMessage(
                    $user->line_user_id,
                    "抽選結果: {$prize['title']}",
                    $this->buildCouponFlexContents("🎉 当選！{$prize['title']}", $tpl->note ?? '', $prize['image_url'] ?? $tpl->image_url)
                );
            } else {
                $this->lineBotService->pushText(
                    $user->line_user_id,
                    "抽選の結果...残念！ハズレでした。また挑戦してね！"
                );
            }
        } else {
            // 通常クーポン付与
            $userCouponId = DB::table('user_coupons')->insertGetId([
                'store_id' => $user->store_id,
                'user_id' => $user->id,
                'coupon_template_id' => $tpl->id,
                'status' => 'issued',
                'issued_at' => $now,
                'used_at' => null,
                'expires_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('coupon_events')->insert([
                'user_coupon_id' => $userCouponId,
                'event' => 'issued',
                'actor' => 'system',
                'created_at' => $now,
            ]);

            $this->lineBotService->pushFlexMessage(
                $user->line_user_id,
                "クーポン: {$tpl->title}",
                $this->buildCouponFlexContents($tpl->title, $tpl->note ?? '', $tpl->image_url, $bubble->coupon_expires_text ?? null)
            );
        }
    }

    /**
     * クーポン通知用 Flex Message の contents を組み立て
     */
    private function buildCouponFlexContents(string $title, string $note, ?string $imageUrl, ?string $expiresText = null): array
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
                            'label' => 'クーポンを取得する',
                            'uri' => 'https://line.me/',
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
