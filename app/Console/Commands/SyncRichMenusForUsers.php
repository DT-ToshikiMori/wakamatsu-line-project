<?php

namespace App\Console\Commands;

use App\Services\RichMenuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncRichMenusForUsers extends Command
{
    protected $signature = 'rich-menu:sync-users {--rank= : Only sync users with current_card_id} {--dry-run : Show target without calling LINE}';

    protected $description = 'ユーザーのランクに応じて個別リッチメニューを同期';

    public function handle(RichMenuService $richMenuService): int
    {
        $rank = $this->option('rank');
        $dryRun = (bool) $this->option('dry-run');

        $query = DB::table('users')
            ->whereNotNull('line_user_id')
            ->where('line_user_id', '!=', '')
            ->orderBy('id');

        if ($rank !== null && $rank !== '') {
            if (!ctype_digit((string) $rank)) {
                $this->error('--rank must be a stamp_card_definitions id.');
                return self::FAILURE;
            }

            $query->where('current_card_id', (int) $rank);
        }

        $successCount = 0;
        $failedCount = 0;
        $seenCount = 0;

        $query->chunkById(200, function ($users) use ($richMenuService, $dryRun, &$successCount, &$failedCount, &$seenCount) {
            foreach ($users as $user) {
                $seenCount++;
                $target = $richMenuService->findTargetForUser($user);

                if ($dryRun) {
                    $this->line(sprintf(
                        'user_id=%s line_user_id=%s current_card_id=%s target=%s',
                        $user->id,
                        $user->line_user_id,
                        $user->current_card_id ?? 'null',
                        $target
                            ? "{$target->name} (#{$target->id}, LINE {$target->line_rich_menu_id})"
                            : 'default/unlink'
                    ));
                    continue;
                }

                if ($richMenuService->syncForUser($user)) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $this->warn("Failed: user_id={$user->id} line_user_id={$user->line_user_id}");
                }
            }
        });

        if ($dryRun) {
            $this->info("Dry run complete: {$seenCount} users.");
            return self::SUCCESS;
        }

        $this->info("Rich menu sync complete: {$successCount} succeeded, {$failedCount} failed.");

        return $failedCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
