<?php

namespace App\Console\Commands;

use App\Models\AdSubmission;
use Illuminate\Console\Command;

class CheckExpiredAds extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ads:check-expired
                            {--archive : Mark expired ads as "expired" status instead of just reporting}
                            {--notify : Send email warning notifications to advertisers whose campaigns expire soon}
                            {--days=0 : Show ads expiring within this many days (0 = already expired)}';

    /**
     * The console command description.
     */
    protected $description = 'Check for expired ad campaigns and optionally archive or notify them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $daysAhead = (int) $this->option('days');
        $archive = $this->option('archive');
        $notify = $this->option('notify');

        // Find currently expired ads (approved but past their expiry)
        $expiredAds = AdSubmission::where('status', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->get();

        $this->info("=== Ad Expiry Report ({$now->format('Y-m-d H:i:s')}) ===");
        $this->newLine();

        if ($expiredAds->isEmpty()) {
            $this->info('✅ No expired ads found. All campaigns are within their schedule.');
        } else {
            $this->warn("⚠️  Found {$expiredAds->count()} expired ad(s):");
            $this->newLine();

            $rows = $expiredAds->map(fn ($ad) => [
                $ad->id,
                $ad->business_name,
                $ad->category ?? 'general',
                $ad->expires_at?->format('Y-m-d H:i') ?? 'N/A',
                $ad->expires_at ? $now->diffForHumans($ad->expires_at, true) . ' ago' : '-',
            ])->toArray();

            $this->table(
                ['ID', 'Business Name', 'Category', 'Expired At', 'Expired Since'],
                $rows
            );

            if ($archive) {
                $count = $expiredAds->count();
                foreach ($expiredAds as $ad) {
                    $ad->update(['status' => 'expired']);
                }
                $this->info("📦 Archived {$count} expired ad(s). They will no longer appear in active feeds.");
            }
        }

        // Show upcoming expirations or notify
        $notifyDays = ($notify && $daysAhead <= 0) ? 2 : $daysAhead;
        if ($notifyDays > 0) {
            $upcoming = AdSubmission::where('status', 'approved')
                ->whereNotNull('expires_at')
                ->where('expires_at', '>=', $now)
                ->where('expires_at', '<=', $now->copy()->addDays($notifyDays))
                ->orderBy('expires_at')
                ->get();

            $this->newLine();

            if ($upcoming->isEmpty()) {
                $this->info("ℹ️  No ads expiring within the next {$notifyDays} day(s).");
            } else {
                $this->warn("⏰ {$upcoming->count()} ad(s) expiring within {$notifyDays} day(s):");
                $this->newLine();

                $rows = $upcoming->map(fn ($ad) => [
                    $ad->id,
                    $ad->business_name,
                    $ad->category ?? 'general',
                    $ad->expires_at?->format('Y-m-d H:i') ?? 'N/A',
                    $now->diffForHumans($ad->expires_at, ['parts' => 2, 'join' => true]),
                ])->toArray();

                $this->table(
                    ['ID', 'Business Name', 'Category', 'Expires At', 'Time Remaining'],
                    $rows
                );

                if ($notify) {
                    $sentCount = 0;
                    foreach ($upcoming as $ad) {
                        if (!empty($ad->submitter_email)) {
                            try {
                                \Illuminate\Support\Facades\Mail::to($ad->submitter_email)
                                    ->send(new \App\Mail\AdExpiringSoonMail($ad));
                                $sentCount++;
                            } catch (\Throwable $e) {
                                $this->warn("Could not email {$ad->submitter_email}: " . $e->getMessage());
                            }
                        }
                    }
                    $this->info("✉️  Sent {$sentCount} expiration warning notification email(s).");
                }
            }
        }

        // Summary stats
        $this->newLine();
        $active = AdSubmission::where('status', 'approved')
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->count();
        $total = AdSubmission::count();
        $pending = AdSubmission::where('status', 'pending')->count();

        $this->info("📊 Summary: {$active} active | {$pending} pending | {$total} total ads");

        return Command::SUCCESS;
    }
}
