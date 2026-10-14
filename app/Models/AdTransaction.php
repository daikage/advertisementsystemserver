<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_submission_id',
        'reference',
        'type',
        'amount',
        'currency',
        'duration_days',
        'status',
        'channel',
        'paid_at',
        'paystack_access_code',
        'metadata',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'duration_days' => 'integer',
        'paid_at'       => 'datetime',
        'metadata'      => 'array',
    ];

    /**
     * The ad submission this transaction belongs to.
     */
    public function submission()
    {
        return $this->belongsTo(AdSubmission::class, 'ad_submission_id');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Mark this transaction as paid.
     */
    public function markAsPaid(): void
    {
        if ($this->isPaid()) {
            return;
        }

        $this->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);
    }
}
