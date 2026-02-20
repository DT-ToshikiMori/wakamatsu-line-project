<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireCoupons extends Command
{
    protected $signature = 'coupons:expire';
    protected $description = '有効期限切れクーポンを expired に更新';

    public function handle(): int
    {
        $count = DB::table('user_coupons')
            ->where('status', 'issued')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
                'updated_at' => now(),
            ]);

        if ($count > 0) {
            $this->info("{$count} 件のクーポンを期限切れに更新しました");
        }

        return self::SUCCESS;
    }
}
