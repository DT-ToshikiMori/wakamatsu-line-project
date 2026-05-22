<?php

namespace App\Console\Commands;

use App\Models\CouponTemplate;
use App\Services\LineBotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendVisitScenarioBatch extends Command
{
    protected $signature = 'visit-scenario:send';
    protected $description = '来店シナリオの通知をバッチ送信（5分毎実行）';

    public function handle(LineBotService $lineBot): int
    {
        $pending = DB::table('visit_scenario_sends')
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at')
            ->get();

        if ($pending->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($pending->groupBy('scenario_id') as $scenarioId => $sends) {
            $this->processScenarioGroup($lineBot, (int) $scenarioId, $sends);
        }

        $this->processReminders($lineBot);

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────
    // シナリオグループ処理
    // ─────────────────────────────────────────────────────────────
    private function processScenarioGroup(LineBotService $lineBot, int $scenarioId, Collection $sends): void
    {
        $scenario = DB::table('visit_scenarios')->where('id', $scenarioId)->first();
        if (!$scenario) {
            Log::warning('SendVisitScenarioBatch: scenario not found', ['scenario_id' => $scenarioId]);
            return;
        }

        // ── バブルがあればバブルベース、なければ旧来のcoupon_template直接方式 ──
        $bubbles = DB::table('message_bubbles')
            ->where('parent_type', 'visit_scenario')
            ->where('parent_id', $scenarioId)
            ->orderBy('position')
            ->get();

        if ($bubbles->isNotEmpty()) {
            $messages = $this->buildMessagesFromBubbles($bubbles, $scenarioId);
            $altText  = $this->resolveAltText($bubbles);
        } else {
            // 後方互換: coupon_template_id から直接構築
            $messages = $this->buildLegacyMessages($scenario, $scenarioId);
            if ($messages === null) {
                return; // テンプレが見つからない場合はスキップ
            }
            $altText = ''; // legacyは内部でセット済み
        }

        if (empty($messages)) {
            Log::warning('SendVisitScenarioBatch: no messages built', ['scenario_id' => $scenarioId]);
            return;
        }

        // ── multicast ──
        $userIds     = $sends->pluck('user_id')->unique()->values()->all();
        $users       = DB::table('users')
            ->whereIn('id', $userIds)
            ->whereNotNull('line_user_id')
            ->pluck('line_user_id', 'id');

        if ($users->isEmpty()) {
            return;
        }

        $lineUserIds = $users->values()->all();
        $sentCount   = 0;

        foreach (array_chunk($lineUserIds, 500) as $chunk) {
            try {
                if ($lineBot->multicast($chunk, $messages)) {
                    $sentCount += count($chunk);
                }
            } catch (\Throwable $e) {
                Log::error('SendVisitScenarioBatch: multicast failed', [
                    'scenario_id' => $scenarioId,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        DB::table('visit_scenario_sends')
            ->whereIn('id', $sends->pluck('id')->all())
            ->update(['sent_at' => now(), 'updated_at' => now()]);

        $this->info("Scenario #{$scenarioId}: multicast to {$sentCount} users ({$sends->count()} sends)");
    }

    // ─────────────────────────────────────────────────────────────
    // バブルからメッセージ配列を構築
    // ─────────────────────────────────────────────────────────────
    private function buildMessagesFromBubbles(Collection $bubbles, int $scenarioId): array
    {
        $liffUrl  = $this->buildLiffUrl($scenarioId);
        $messages = [];

        foreach ($bubbles as $bubble) {
            if ($bubble->bubble_type === 'text' && $bubble->text_content) {
                $messages[] = ['type' => 'text', 'text' => $bubble->text_content];
                continue;
            }

            if ($bubble->bubble_type === 'coupon' && $bubble->coupon_template_id) {
                $tpl = DB::table('coupon_templates')
                    ->where('id', $bubble->coupon_template_id)
                    ->where('is_active', true)
                    ->first();

                if (!$tpl) {
                    continue;
                }

                $imageUrl    = CouponTemplate::resolveImageUrl($tpl->image_url);
                $expiresText = $bubble->coupon_expires_days
                    ? "取得から{$bubble->coupon_expires_days}日間有効"
                    : null;

                $messages[] = [
                    'type'     => 'flex',
                    'altText'  => "クーポンが届きました: {$tpl->title}",
                    'contents' => $this->buildFlexBubble($tpl->title, $tpl->note ?? '', $imageUrl, $expiresText, $liffUrl),
                ];
            }
        }

        return $messages;
    }

    private function resolveAltText(Collection $bubbles): string
    {
        $first = $bubbles->first();
        if (!$first) {
            return 'メッセージが届きました';
        }
        if ($first->bubble_type === 'text' && $first->text_content) {
            return mb_strimwidth($first->text_content, 0, 40, '…');
        }
        return 'クーポンが届きました';
    }

    // ─────────────────────────────────────────────────────────────
    // 後方互換: coupon_template_id ベースのメッセージ構築
    // ─────────────────────────────────────────────────────────────
    private function buildLegacyMessages(object $scenario, int $scenarioId): ?array
    {
        if (empty($scenario->coupon_template_id)) {
            return null;
        }

        $tpl = DB::table('coupon_templates')
            ->where('id', $scenario->coupon_template_id)
            ->where('is_active', true)
            ->first();

        if (!$tpl) {
            Log::warning('SendVisitScenarioBatch: coupon template not found or inactive', [
                'scenario_id'        => $scenarioId,
                'coupon_template_id' => $scenario->coupon_template_id,
            ]);
            return null;
        }

        $liffUrl     = $this->buildLiffUrl($scenarioId);
        $imageUrl    = CouponTemplate::resolveImageUrl($tpl->image_url);
        $expiresText = $scenario->expires_days ? "取得から{$scenario->expires_days}日間有効" : null;

        return [[
            'type'     => 'flex',
            'altText'  => "クーポンが届きました: {$tpl->title}",
            'contents' => $this->buildFlexBubble($tpl->title, $tpl->note ?? '', $imageUrl, $expiresText, $liffUrl),
        ]];
    }

    private function buildLiffUrl(int $scenarioId): string
    {
        $liffId = config('services.line.liff_id');
        return "https://liff.line.me/{$liffId}/coupons?scenario_id={$scenarioId}&ts=" . now()->timestamp;
    }

    // ─────────────────────────────────────────────────────────────
    // Flex バブル構築
    // ─────────────────────────────────────────────────────────────
    private function buildFlexBubble(string $title, string $note, ?string $imageUrl, ?string $expiresText, string $claimUrl): array
    {
        $bodyContents = [
            ['type' => 'text', 'text' => $title, 'weight' => 'bold', 'size' => 'xl'],
        ];

        $detailRows = [];
        if ($expiresText) {
            $detailRows[] = [
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm',
                'contents' => [
                    ['type' => 'text', 'text' => '有効期限', 'color' => '#aaaaaa', 'size' => 'sm', 'flex' => 2],
                    ['type' => 'text', 'text' => $expiresText, 'wrap' => true, 'color' => '#666666', 'size' => 'sm', 'flex' => 5],
                ],
            ];
        }
        if ($note) {
            $detailRows[] = [
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm',
                'contents' => [
                    ['type' => 'text', 'text' => '備考', 'color' => '#aaaaaa', 'size' => 'sm', 'flex' => 2],
                    ['type' => 'text', 'text' => $note, 'wrap' => true, 'color' => '#666666', 'size' => 'sm', 'flex' => 5],
                ],
            ];
        }
        if (!empty($detailRows)) {
            $bodyContents[] = ['type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'spacing' => 'sm', 'contents' => $detailRows];
        }

        $bubble = [
            'type' => 'bubble',
            'body' => ['type' => 'box', 'layout' => 'vertical', 'contents' => $bodyContents],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'flex' => 0,
                'contents' => [[
                    'type' => 'button', 'style' => 'link', 'height' => 'sm',
                    'action' => ['type' => 'uri', 'label' => 'クーポンを受け取る', 'uri' => $claimUrl],
                ]],
            ],
        ];

        if ($imageUrl) {
            $bubble['hero'] = ['type' => 'image', 'url' => $imageUrl, 'size' => 'full', 'aspectRatio' => '1:1', 'aspectMode' => 'cover'];
        }

        return $bubble;
    }

    // ─────────────────────────────────────────────────────────────
    // リマインド処理
    // ─────────────────────────────────────────────────────────────
    private function processReminders(LineBotService $lineBot): void
    {
        // 新方式: visit_scenario_reminders テーブルを使った複数リマインド
        $this->processNewReminders($lineBot);

        // 旧方式: reminder_enabled カラムを使った単一リマインド（後方互換）
        $this->processLegacyReminders($lineBot);
    }

    /** 新方式: visit_scenario_reminders ベースの複数リマインド */
    private function processNewReminders(LineBotService $lineBot): void
    {
        // 各リマインド設定に対して、期限を迎えた user_coupon を検索して送信
        $reminders = DB::table('visit_scenario_reminders')
            ->where('is_active', true)
            ->get();

        if ($reminders->isEmpty()) {
            return;
        }

        $liffBase = config('services.line.liff_url', 'https://liff.line.me/' . config('services.line.liff_id'));
        $sentCount = 0;

        foreach ($reminders as $reminderDef) {
            // 期限 before_days 日前かつ send_hour 時に送信すべき user_coupon を取得
            $targetDate = now()->addDays($reminderDef->before_days)->toDateString();
            $sendHour   = (int) $reminderDef->send_hour;

            if (now()->hour !== $sendHour) {
                continue;
            }

            $targets = DB::table('user_coupons as uc')
                ->join('coupon_templates as ct', 'ct.id', '=', 'uc.coupon_template_id')
                ->join('users as u', 'u.id', '=', 'uc.user_id')
                ->join('visit_scenario_sends as vss', function ($join) use ($reminderDef) {
                    $join->on('vss.user_id', '=', 'uc.user_id')
                        ->on('vss.user_coupon_id', '=', 'uc.id')
                        ->where('vss.scenario_id', '=', $reminderDef->visit_scenario_id);
                })
                ->where('uc.status', 'issued')
                ->whereNotNull('u.line_user_id')
                ->whereRaw('DATE(uc.expires_at) = ?', [$targetDate])
                ->whereNotExists(function ($q) use ($reminderDef) {
                    // 同一リマインド設定で送信済みのものは除外
                    $q->from('visit_scenario_reminder_logs')
                        ->whereColumn('visit_scenario_reminder_logs.user_coupon_id', 'uc.id')
                        ->where('visit_scenario_reminder_logs.reminder_id', $reminderDef->id);
                })
                ->select(['uc.id as coupon_id', 'u.line_user_id', 'ct.title', 'ct.image_url', 'uc.expires_at'])
                ->get();

            foreach ($targets as $target) {
                $daysLeft     = (int) now()->diffInDays(Carbon::parse($target->expires_at), false);
                $imageUrl     = CouponTemplate::resolveImageUrl($target->image_url);
                $liffUrl      = $liffBase . '/coupons?remind=1';
                $flexContents = $this->buildReminderFlexBubble($target->title, $daysLeft, $imageUrl, $liffUrl);

                try {
                    $lineBot->multicast([$target->line_user_id], [[
                        'type'    => 'flex',
                        'altText' => "クーポンの期限が近づいています: {$target->title}",
                        'contents' => $flexContents,
                    ]]);

                    // 送信ログを残す
                    DB::table('visit_scenario_reminder_logs')->insertOrIgnore([
                        'reminder_id'    => $reminderDef->id,
                        'user_coupon_id' => $target->coupon_id,
                        'sent_at'        => now(),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);

                    $sentCount++;
                } catch (\Throwable $e) {
                    Log::error('NewReminder send failed', [
                        'reminder_id' => $reminderDef->id,
                        'error'       => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($sentCount > 0) {
            $this->info("New reminders sent: {$sentCount}");
        }
    }

    /** 旧方式: reminder_enabled カラムを使った単一リマインド（後方互換） */
    private function processLegacyReminders(LineBotService $lineBot): void
    {
        $pendingReminders = DB::table('visit_scenario_sends as vss')
            ->join('visit_scenarios as vs', 'vs.id', '=', 'vss.scenario_id')
            ->join('user_coupons as uc', 'uc.id', '=', 'vss.user_coupon_id')
            ->join('coupon_templates as ct', 'ct.id', '=', 'uc.coupon_template_id')
            ->join('users as u', 'u.id', '=', 'vss.user_id')
            ->where('vs.reminder_enabled', true)
            ->whereNotNull('vss.reminder_scheduled_at')
            ->where('vss.reminder_scheduled_at', '<=', now())
            ->whereNull('vss.reminder_sent_at')
            ->whereNotNull('vss.user_coupon_id')
            ->where('uc.status', 'issued')
            ->whereNotNull('u.line_user_id')
            ->select(['vss.id as send_id', 'u.line_user_id', 'ct.title', 'ct.image_url', 'uc.expires_at'])
            ->get();

        if ($pendingReminders->isEmpty()) {
            return;
        }

        $liffUrl = config('services.line.liff_url', 'https://liff.line.me/' . config('services.line.liff_id') . '/coupons?remind=1');

        foreach ($pendingReminders as $reminder) {
            $daysLeft     = $reminder->expires_at
                ? (int) now()->diffInDays(Carbon::parse($reminder->expires_at), false)
                : null;
            $imageUrl     = CouponTemplate::resolveImageUrl($reminder->image_url);
            $flexContents = $this->buildReminderFlexBubble($reminder->title, $daysLeft, $imageUrl, $liffUrl);

            try {
                $lineBot->multicast([$reminder->line_user_id], [[
                    'type'     => 'flex',
                    'altText'  => "クーポンの期限が近づいています: {$reminder->title}",
                    'contents' => $flexContents,
                ]]);
            } catch (\Throwable $e) {
                Log::error('LegacyReminder send failed', ['send_id' => $reminder->send_id, 'error' => $e->getMessage()]);
            }

            DB::table('visit_scenario_sends')
                ->where('id', $reminder->send_id)
                ->update(['reminder_sent_at' => now(), 'updated_at' => now()]);
        }

        $this->info("Legacy reminders sent: {$pendingReminders->count()}");
    }

    private function buildReminderFlexBubble(string $title, ?int $daysLeft, ?string $imageUrl, string $url): array
    {
        $daysText     = $daysLeft !== null ? "あと{$daysLeft}日" : 'まもなく期限切れ';
        $bodyContents = [
            ['type' => 'text', 'text' => "\u{23F0} クーポンの期限が近づいています", 'weight' => 'bold', 'size' => 'sm', 'color' => '#e74c3c'],
            ['type' => 'text', 'text' => $title, 'weight' => 'bold', 'size' => 'xl', 'margin' => 'md'],
            ['type' => 'box', 'layout' => 'vertical', 'margin' => 'lg', 'spacing' => 'sm', 'contents' => [[
                'type' => 'box', 'layout' => 'baseline', 'spacing' => 'sm',
                'contents' => [
                    ['type' => 'text', 'text' => '有効期限', 'color' => '#aaaaaa', 'size' => 'sm', 'flex' => 2],
                    ['type' => 'text', 'text' => $daysText, 'wrap' => true, 'color' => '#e74c3c', 'size' => 'sm', 'flex' => 5],
                ],
            ]]],
        ];

        $bubble = [
            'type'   => 'bubble',
            'body'   => ['type' => 'box', 'layout' => 'vertical', 'contents' => $bodyContents],
            'footer' => [
                'type' => 'box', 'layout' => 'vertical', 'spacing' => 'sm', 'flex' => 0,
                'contents' => [[
                    'type' => 'button', 'style' => 'primary', 'color' => '#e74c3c', 'height' => 'sm',
                    'action' => ['type' => 'uri', 'label' => 'クーポンを確認する', 'uri' => $url],
                ]],
            ],
        ];

        if ($imageUrl) {
            $bubble['hero'] = ['type' => 'image', 'url' => $imageUrl, 'size' => 'full', 'aspectRatio' => '1:1', 'aspectMode' => 'cover'];
        }

        return $bubble;
    }
}
