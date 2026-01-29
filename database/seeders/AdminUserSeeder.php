<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 既にいれば何もしない（再実行安全）
        $exists = DB::table('admin_users')
            ->where('email', 'admin@waka-matsu.jp')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('admin_users')->insert([
            'name' => 'WAKAMATSU Admin',
            'email' => 'admin@waka-matsu.jp',
            'password' => Hash::make('demo1234'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}