<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageService
{
    protected LineBotService $lineBotService;

    public function __construct(LineBotService $lineBotService)
    {
        $this->lineBotService = $lineBotService;
    }

    /**
     * バブル配列を元にユーザーへメッセージ送信
     * クーポンは通知のみ送信（発行はユーザーの「取得」アクション時）
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
                    $this->sendCouponBubble($user, $bubble);
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

    private function sendCouponBubble(object $user, object $bubble): void
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

        $expiresText = $this->buildExpiresText($bubble);

        $postbackData = http_build_query([
            'action' => 'claim_coupon',
            'bubble_id' => $bubble->id,
            'tpl_id' => $tpl->id,
            'sent_at' => now()->timestamp,
        ]);

        $this->lineBotService->pushFlexMessage(
            $user->line_user_id,
            "クーポン: {$tpl->title}",
            $this->buildCouponFlexContents($tpl->title, $tpl->note ?? '', $tpl->image_url, $expiresText, $postbackData)
        );
    }

    private function buildExpiresText(object $bubble): ?string
    {
        if (!empty($bubble->coupon_expires_at)) {
            $dt = \Carbon\Carbon::parse($bubble->coupon_expires_at);
            return $dt->format('Y年n月j日 H:i') . ' まで';
        }

        if (!empty($bubble->coupon_expires_days)) {
            return "取得から{$bubble->coupon_expires_days}日間有効";
        }

        if (!empty($bubble->coupon_expires_text)) {
            return $bubble->coupon_expires_text;
        }

        return null;
    }

    private function buildCouponFlexContents(string $title, string $note, ?string $imageUrl, ?string $expiresText = null, ?string $postbackData = null): array
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

        $buttonAction = $postbackData
            ? [
                'type' => 'postback',
                'label' => 'クーポンを取得する',
                'data' => $postbackData,
                'displayText' => 'クーポンを取得する',
            ]
            : [
                'type' => 'uri',
                'label' => 'クーポンを見る',
                'uri' => 'https://line.me/',
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
                        'style' => 'link',
                        'height' => 'sm',
                        'action' => $buttonAction,
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
