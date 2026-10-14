<?php

namespace App\Console\Commands;

use App\Models\AdSubmission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneUnpaidSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ads:prune-unpaid
                            {--hours=48 : Delete unpaid submissions older than this many hours}
                            {--force : Force deletion without prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Prune abandoned or failed unpaid ad submissions and clean up orphan media files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        if ($hours <= 0) {
            $hours = 48;
        }

        $cutoff = now()->subHours($hours);

        $unpaidSubmissions = AdSubmission::where('status', 'pending')
            ->whereIn('payment_status', ['pending', 'failed', 'unpaid'])
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($unpaidSubmissions->isEmpty()) {
            $this->info("✅ No abandoned unpaid submissions older than {$hours} hours found.");
            return Command::SUCCESS;
        }

        $count = $unpaidSubmissions->count();
        $this->warn("⚠️  Found {$count} unpaid submission(s) created before {$cutoff->format('Y-m-d H:i:s')}.");

        $pruned = 0;
        foreach ($unpaidSubmissions as $ad) {
            // Delete media from disk
            if ($ad->image_path && Storage::disk('public')->exists($ad->image_path)) {
                Storage::disk('public')->delete($ad->image_path);
            }

            if ($ad->video_path && Storage::disk('public')->exists($ad->video_path)) {
                Storage::disk('public')->delete($ad->video_path);
            }

            $ad->delete();
            $pruned++;
        }

        $this->info("🗑️  Successfully pruned {$pruned} abandoned unpaid submission(s) and associated media.");

        return Command::SUCCESS;
    }
}
