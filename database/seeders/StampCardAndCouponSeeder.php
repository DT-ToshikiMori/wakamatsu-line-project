<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StampCardAndCouponSeeder extends Seeder
{
    public function run(): void
    {
        // 全店舗を対象にする
        $stores = DB::table('stores')->get();

        foreach ($stores as $store) {

            // ① スタンプカード定義（ランク）
            $beginnerId = DB::table('stamp_card_definitions')->insertGetId([
                'store_id' => $store->id,
                'name' => 'BEGINNER',
                'display_name' => 'BEGINNER',
                'required_stamps' => 3,
                'priority' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'theme_bg' => '#0b0b0f',
                'theme_accent' => '#ffffff',
                'theme_logo_opacity' => 0.10,
            ]);

            $goldId = DB::table('stamp_card_definitions')->insertGetId([
                'store_id' => $store->id,
                'name' => 'GOLD',
                'display_name' => 'GOLD',
                'required_stamps' => 5,
                'priority' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'theme_bg' => '#07070a',
                'theme_accent' => '#ffd54a',
                'theme_logo_opacity' => 0.12,
            ]);

            $blackId = DB::table('stamp_card_definitions')->insertGetId([
                'store_id' => $store->id,
                'name' => 'BLACK',
                'display_name' => 'BLACK',
                'required_stamps' => 10,
                'priority' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'theme_bg' => '#050506',
                'theme_accent' => '#bdbdbd',
                'theme_logo_opacity' => 0.08,
            ]);

            // ② ランクアップクーポン（GOLD）
            DB::table('coupon_templates')->insert([
                'store_id' => $store->id,
                'type' => 'rank_up',
                'title' => 'GOLDランクアップクーポン',
                'note' => 'GOLDランク昇格おめでとうございます。次回のお会計でご利用いただけます。',
                'image_url' => 'https://placehold.co/900x300/png?text=GOLD+RANK+UP',
                'rank_card_id' => $goldId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ③ ランクアップクーポン（BLACK）
            DB::table('coupon_templates')->insert([
                'store_id' => $store->id,
                'type' => 'rank_up',
                'title' => 'BLACKランクアップクーポン',
                'note' => 'BLACKランク昇格おめでとうございます。特別な特典をご利用ください。',
                'image_url' => 'https://placehold.co/900x300/png?text=BLACK+RANK+UP',
                'rank_card_id' => $blackId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ④ 誕生日クーポン
            DB::table('coupon_templates')->insert([
                'store_id' => $store->id,
                'type' => 'birthday',
                'title' => 'お誕生日クーポン',
                'note' => 'お誕生日おめでとうございます。特別な割引をご用意しました。',
                'image_url' => 'https://placehold.co/900x300/png?text=HAPPY+BIRTHDAY',
                'birthday_offset_days' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ⑤ 離脱防止クーポン（30日後 10:00）
            DB::table('coupon_templates')->insert([
                'store_id' => $store->id,
                'type' => 'inactive',
                'title' => 'おかえりなさいクーポン',
                'note' => 'しばらくご来店がないお客様へ。次回ぜひご利用ください。',
                'image_url' => 'https://placehold.co/900x300/png?text=COME+BACK',
                'inactive_days' => 30,
                'inactive_hour' => 10,
                'inactive_minute' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}