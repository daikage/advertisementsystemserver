<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
        'description',
        'submitter_contact',
        'submitter_email',
        'category',
        'media_type',
        'image_path',
        'image_width',
        'image_height',
        'video_path',
        'click_through_url',
        'display_duration',
        'starts_at',
        'expires_at',
        'status',
        'decline_reason',
        'approved_by',
        'declined_by',
        'payment_status',
        'payment_reference',
        'payment_amount',
        'payment_currency',
        'paid_at',
        'duration_days',
        'paystack_access_code',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'display_duration' => 'float',
        'payment_amount' => 'decimal:2',
        'duration_days' => 'integer',
        'image_width' => 'integer',
        'image_height' => 'integer',
        'approved_by' => 'integer',
        'declined_by' => 'integer',
    ];

    /**
     * User who approved this ad.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who declined this ad.
     */
    public function declinedBy()
    {
        return $this->belongsTo(User::class, 'declined_by');
    }

    /**
     * Relationship to tracked analytics events.
     */
    public function events()
    {
        return $this->hasMany(AdEvent::class, 'ad_submission_id');
    }

    /**
     * Scope query to only approved and active ads within their scheduled date range.
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        $now = now();
        return $query->where('status', 'approved')
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', $now);
            });
    }

    /**
     * Scope to ads that have been successfully paid for.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope to ads that have not been paid for.
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', '!=', 'paid');
    }

    /**
     * Scope to ads whose payment has been initialized but not yet confirmed.
     */
    public function scopeAwaitingPayment(Builder $query): Builder
    {
        return $query->where('payment_status', 'pending');
    }

    /**
    /**
     * Relationship to payment transactions.
     */
    public function transactions()
    {
        return $this->hasMany(AdTransaction::class, 'ad_submission_id');
    }

    /**
     * Whether this ad has been paid for.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Mark this ad as paid.
     *
     * IMPORTANT: The campaign validity clock (starts_at & expires_at) is NOT started
     * while the ad is pending moderator review. The full purchased duration will be
     * applied when the moderator approves the ad (or on the scheduled start date).
     */
    public function markAsPaid(?AdTransaction $transaction = null): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction) {
            $updates = [
                'payment_status' => 'paid',
                'paid_at'        => $this->paid_at ?? now(),
            ];

            if ($transaction) {
                if (!empty($transaction->reference)) {
                    $updates['payment_reference'] = $transaction->reference;
                }
                if ($transaction->amount > 0) {
                    $updates['payment_amount'] = $transaction->amount;
                }
                if ($transaction->duration_days > 0) {
                    $updates['duration_days'] = $transaction->duration_days;
                }
            }

            // If the ad is already approved (e.g. renewal flow), extend the validity window.
            if ($this->status === 'approved') {
                $days = $transaction ? (int) $transaction->duration_days : (int) $this->duration_days;
                if ($days > 0) {
                    $base = ($this->expires_at && $this->expires_at->isFuture()) ? $this->expires_at : now();
                    $updates['expires_at'] = (clone $base)->addDays($days);
                }
            }

            $this->forceFill($updates)->save();
        });
    }

    /**
     * Applies campaign validity dates upon moderator approval so the advertiser
     * gets their full purchased duration starting from the approval timestamp.
     */
    public function scheduleCampaignOnApproval(?\Carbon\Carbon $customStartsAt = null, $durationOverride = null): void
    {
        $startsAt = $customStartsAt ?? ($this->starts_at ?? now());
        $expiresAt = null;

        if ($durationOverride === 'unlimited') {
            $expiresAt = null;
        } elseif (is_numeric($durationOverride) && (int) $durationOverride > 0) {
            $expiresAt = (clone $startsAt)->addDays((int) $durationOverride);
        } elseif ((int) $this->duration_days > 0) {
            // Default to purchased duration tier
            $expiresAt = (clone $startsAt)->addDays((int) $this->duration_days);
        }

        $this->update([
            'starts_at'  => $startsAt,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Check if this ad has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Get the publicly accessible URL for the ad's image.
     */
    public function getImageUrlAttribute(): string
    {
        if (empty($this->image_path)) return '';
        return asset('storage/' . $this->image_path);
    }

    /**
     * Get the publicly accessible URL for the ad's video.
     */
    public function getVideoUrlAttribute(): string
    {
        if (empty($this->video_path)) return '';
        return asset('storage/' . $this->video_path);
    }
}
