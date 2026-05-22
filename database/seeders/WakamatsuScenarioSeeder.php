<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * WAKAMATSUロイヤリティプログラム シナリオ初期データ
 *
 * シナリオ構成:
 *   0回目 (LINE移行時)  : ご登録記念クーポン、期限なし
 *   1回目 来店           : 次回使えるクーポン、40日期限、スタンプ1h後送信
 *   2回目 来店           : 同上
 *   3回目 来店           : 同上
 *   4回目以降 来店 (毎回): ゴールドクーポン ¥100 OFF、期限なし
 */
class WakamatsuScenarioSeeder extends Seeder
{
    // 対象スタンプカード（BEGINNER）
    private const CARD_ID = 1;

    public function run(): void
    {
        // ── 0. 既存データをクリア（再実行安全）──────────────────
        $scenarioIds = DB::table('visit_scenarios')
            ->where('stamp_card_definition_id', self::CARD_ID)
            ->pluck('id');

        if ($scenarioIds->isNotEmpty()) {
            DB::table('message_bubbles')
                ->where('parent_type', 'visit_scenario')
                ->whereIn('parent_id', $scenarioIds)
                ->delete();
            DB::table('visit_scenario_reminders')
                ->whereIn('visit_scenario_id', $scenarioIds)
                ->delete();
            DB::table('visit_scenarios')
                ->whereIn('id', $scenarioIds)
                ->delete();
        }

        // クーポンテンプレートも再作成
        DB::table('coupon_templates')
            ->whereIn('title', [
                'ご登録記念クーポン',
                '次回使えるクーポン',
                'ゴールドクーポン ¥100 OFF',
            ])
            ->delete();

        // ── 1. クーポンテンプレート作成 ─────────────────────────
        $now = Carbon::now();

        $couponMigration = DB::table('coupon_templates')->insertGetId([
            'store_id'   => null,
            'type'       => 'stamp',  // DBのenum制約を満たすため残置
            'mode'       => 'normal',
            'title'      => 'ご登録記念クーポン',
            'note'       => '新システム移行記念クーポンです。次回のご来店にぜひご利用ください。',
            'image_url'  => null,
            'is_active'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $couponVisit = DB::table('coupon_templates')->insertGetId([
            'store_id'   => null,
            'type'       => 'stamp',
            'mode'       => 'normal',
            'title'      => '次回使えるクーポン',
            'note'       => '次回ご来店時にお使いいただけます。',
            'image_url'  => null,
            'is_active'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $couponGold = DB::table('coupon_templates')->insertGetId([
            'store_id'   => null,
            'type'       => 'stamp',
            'mode'       => 'normal',
            'title'      => 'ゴールドクーポン ¥100 OFF',
            'note'       => 'いつでも何度でもずっと ¥100 OFF。ご来店のたびにお使いいただけます。',
            'image_url'  => null,
            'is_active'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ── 2. シナリオ + バブル作成 ────────────────────────────
        $scenarios = [
            // 0回目: LINE移行時
            [
                'scenario' => [
                    'name'                    => '0回目 ご登録記念クーポン',
                    'stamp_card_definition_id' => self::CARD_ID,
                    'trigger_type'            => 'migration',
                    'trigger_days'            => null,
                    'send_hour'               => null,
                    'delay_hours'             => 0,
                    'visit_count_min'         => null,
                    'visit_count_max'         => null,
                    'repeat'                  => false,
                    'is_active'               => true,
                ],
                'coupon_template_id' => $couponMigration,
                'expires_days'       => null,   // 期限なし
                'reminders'          => [],
            ],

            // 1回目
            [
                'scenario' => [
                    'name'                    => '1回目 次回使えるクーポン',
                    'stamp_card_definition_id' => self::CARD_ID,
                    'trigger_type'            => 'checkin',
                    'trigger_days'            => null,
                    'send_hour'               => null,
                    'delay_hours'             => 1,   // ← 1時間後（後で変更可）
                    'visit_count_min'         => 1,
                    'visit_count_max'         => 1,
                    'repeat'                  => false,
                    'is_active'               => true,
                ],
                'coupon_template_id' => $couponVisit,
                'expires_days'       => 40,
                'reminders'          => [
                    ['before_days' => 7, 'send_hour' => 10],
                    ['before_days' => 3, 'send_hour' => 10],
                ],
            ],

            // 2回目
            [
                'scenario' => [
                    'name'                    => '2回目 次回使えるクーポン',
                    'stamp_card_definition_id' => self::CARD_ID,
                    'trigger_type'            => 'checkin',
                    'trigger_days'            => null,
                    'send_hour'               => null,
                    'delay_hours'             => 1,
                    'visit_count_min'         => 2,
                    'visit_count_max'         => 2,
                    'repeat'                  => false,
                    'is_active'               => true,
                ],
                'coupon_template_id' => $couponVisit,
                'expires_days'       => 40,
                'reminders'          => [
                    ['before_days' => 7, 'send_hour' => 10],
                    ['before_days' => 3, 'send_hour' => 10],
                ],
            ],

            // 3回目（3スタンプでGOLDにランクアップ → current_card_id=2）
            [
                'scenario' => [
                    'name'                    => '3回目 次回使えるクーポン',
                    'stamp_card_definition_id' => 2, // GOLD
                    'trigger_type'            => 'checkin',
                    'trigger_days'            => null,
                    'send_hour'               => null,
                    'delay_hours'             => 1,
                    'visit_count_min'         => 3,
                    'visit_count_max'         => 3,
                    'repeat'                  => false,
                    'is_active'               => true,
                ],
                'coupon_template_id' => $couponVisit,
                'expires_days'       => 40,
                'reminders'          => [
                    ['before_days' => 7, 'send_hour' => 10],
                    ['before_days' => 3, 'send_hour' => 10],
                ],
            ],

            // 4回目以降（毎回・GOLD）
            [
                'scenario' => [
                    'name'                    => '4回目以降 ゴールドクーポン',
                    'stamp_card_definition_id' => 2, // GOLD
                    'trigger_type'            => 'checkin',
                    'trigger_days'            => null,
                    'send_hour'               => null,
                    'delay_hours'             => 1,
                    'visit_count_min'         => 4,
                    'visit_count_max'         => null,  // 上限なし
                    'repeat'                  => true,  // 毎回発火
                    'is_active'               => true,
                ],
                'coupon_template_id' => $couponGold,
                'expires_days'       => null,   // 無期限
                'reminders'          => [],      // ゴールドは期限なしのためリマインドなし
            ],
        ];

        foreach ($scenarios as $def) {
            // シナリオ挿入
            $scenarioId = DB::table('visit_scenarios')->insertGetId(
                array_merge($def['scenario'], [
                    'coupon_template_id' => null, // バブルで管理するためnull
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ])
            );

            // クーポンバブル挿入
            DB::table('message_bubbles')->insert([
                'parent_type'        => 'visit_scenario',
                'parent_id'          => $scenarioId,
                'position'           => 0,
                'bubble_type'        => 'coupon',
                'text_content'       => null,
                'coupon_template_id' => $def['coupon_template_id'],
                'coupon_expires_days' => $def['expires_days'],
                'coupon_expires_at'  => null,
                'coupon_expires_text' => $def['expires_days']
                    ? "取得から{$def['expires_days']}日間有効"
                    : null,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            // リマインド挿入
            foreach ($def['reminders'] as $reminder) {
                DB::table('visit_scenario_reminders')->insert([
                    'visit_scenario_id' => $scenarioId,
                    'before_days'       => $reminder['before_days'],
                    'send_hour'         => $reminder['send_hour'],
                    'is_active'         => true,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
        }

        $this->command->info('✅ WakamatsuScenarioSeeder: 5シナリオ作成完了');
        $this->command->table(
            ['シナリオ名', 'トリガー', '来店回数', '遅延', '期限'],
            collect($scenarios)->map(fn ($d) => [
                $d['scenario']['name'],
                $d['scenario']['trigger_type'],
                ($d['scenario']['visit_count_min'] ?? '-') . '〜' . ($d['scenario']['visit_count_max'] ?? '∞'),
                $d['scenario']['delay_hours'] . 'h後',
                $d['expires_days'] ? $d['expires_days'] . '日' : '無期限',
            ])->toArray()
        );
    }
}
