<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Faker\Factory as Faker;

/**
 * LINE ユーザー 10,000人 シーダー
 *
 * 分布:
 *   BEGINNER (current_card_id=1): ~70%  visit_count 1-2
 *   GOLD     (current_card_id=2): ~30%  visit_count 3+
 */
class LineUserSeeder extends Seeder
{
    private const BATCH  = 500;
    private const TOTAL  = 10_000;

    public function run(): void
    {
        // 既存の偽ユーザーを削除（line_user_id が U で始まるもの）
        DB::table('users')->where('line_user_id', 'like', 'U%')->delete();

        $faker = Faker::create('ja_JP');
        $now   = Carbon::now();
        $rows  = [];
        $done  = 0;

        $this->command->info('LINEユーザー 10,000人 生成中...');
        $bar = $this->command->getOutput()->createProgressBar(self::TOTAL);
        $bar->start();

        for ($i = 0; $i < self::TOTAL; $i++) {
            // カード分布: BEGINNER 70% / GOLD 30%
            $isGold = $faker->boolean(30);

            if ($isGold) {
                $visitCount  = $faker->numberBetween(3, 30);
                $cardId      = 2;
                $cardProgress = $faker->numberBetween(0, 4);
                $stampTotal  = $faker->numberBetween(3, 60);
            } else {
                $visitCount  = $faker->numberBetween(0, 2);
                $cardId      = 1;
                $cardProgress = $faker->numberBetween(0, 2);
                $stampTotal  = $cardProgress;
            }

            // 来店日時
            $firstVisit = $visitCount > 0
                ? $faker->dateTimeBetween('-2 years', '-1 day')
                : null;
            $lastVisit  = $visitCount > 0
                ? $faker->dateTimeBetween($firstVisit ?? '-1 year', 'now')
                : null;

            // 来店頻度
            $frequency = match(true) {
                $visitCount >= 10 => 'frequent',
                $visitCount >= 4  => 'regular',
                $visitCount >= 1  => 'occasional',
                default           => 'new',
            };

            $rows[] = [
                'store_id'        => $faker->randomElement([1, 2]),
                'line_user_id'    => 'U' . $faker->unique()->regexify('[a-f0-9]{32}'),
                'display_name'    => $faker->name(),
                'gender'          => $faker->randomElement(['male', 'female', null, null]), // nullが多め
                'birth_year'      => $faker->optional(0.6)->numberBetween(1960, 2005),
                'birth_month'     => $faker->optional(0.6)->numberBetween(1, 12),
                'visit_count'     => $visitCount,
                'stamp_count'     => $cardProgress,
                'stamp_total'     => $stampTotal,
                'current_card_id' => $visitCount > 0 ? $cardId : 1,
                'card_progress'   => $cardProgress,
                'card_updated_at' => $lastVisit,
                'first_visit_at'  => $firstVisit,
                'last_visit_at'   => $lastVisit,
                'visit_frequency' => $frequency,
                'profile_image_url' => null,
                'status_message'  => null,
                'created_at'      => $firstVisit ?? $now,
                'updated_at'      => $now,
            ];

            if (count($rows) >= self::BATCH) {
                DB::table('users')->insert($rows);
                $done += count($rows);
                $rows = [];
                $bar->advance(self::BATCH);
            }
        }

        // 残り
        if (!empty($rows)) {
            DB::table('users')->insert($rows);
            $done += count($rows);
            $bar->advance(count($rows));
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("✅ {$done}人のLINEユーザーを作成しました");

        // サマリー
        $beginner = DB::table('users')->where('current_card_id', 1)->where('line_user_id', 'like', 'U%')->count();
        $gold     = DB::table('users')->where('current_card_id', 2)->where('line_user_id', 'like', 'U%')->count();
        $this->command->table(
            ['カード', '件数', '割合'],
            [
                ['BEGINNER', number_format($beginner), round($beginner / $done * 100, 1) . '%'],
                ['GOLD',     number_format($gold),     round($gold     / $done * 100, 1) . '%'],
            ]
        );
    }
}
