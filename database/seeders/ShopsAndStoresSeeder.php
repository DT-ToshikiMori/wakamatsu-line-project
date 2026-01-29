<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopsAndStoresSeeder extends Seeder
{
    public function run(): void
    {
        // すでにあれば何もしない（開発用）
        if (DB::table('shops')->count() > 0 && DB::table('stores')->count() > 0) {
            return;
        }

        $shopId = DB::table('shops')->insertGetId([
            'name' => 'WAKAMATSU',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('stores')->insert([
            'shop_id' => $shopId,
            'name' => 'DT｜テスト(1)',
            'address' => '',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}