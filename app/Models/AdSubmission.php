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
        'category',
        'media_type',
        'image_path',
        'video_path',
        'click_through_url',
        'display_duration',
        'starts_at',
        'expires_at',
        'status',
        'decline_reason',
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
    ];

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
     * Whether this ad has been paid for.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Mark this ad as paid and schedule its validity window based on the
     * purchased duration. Idempotent — safe to call from both the webhook and
     * the client verification endpoint.
     */
    public function markAsPaid(): void
    {
        if ($this->isPaid()) {
            return;
        }

        $startsAt = $this->starts_at ?? now();
        $expiresAt = $this->expires_at;

        // Derive expiry from the purchased duration if not already scheduled.
        if ($expiresAt === null && (int) $this->duration_days > 0) {
            $expiresAt = (clone $startsAt)->addDays((int) $this->duration_days);
        }

        $this->forceFill([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
        ])->save();
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
