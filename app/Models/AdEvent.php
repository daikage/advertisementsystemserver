<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdEvent extends Model
{
    use HasFactory;

    public $timestamps = false; // Only using created_at

    protected $fillable = [
        'ad_submission_id',
        'event_type',
        'slot_index',
        'device_id',
        'duration_viewed',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'duration_viewed' => 'float',
        'slot_index' => 'integer',
    ];

    public function submission()
    {
        return $this->belongsTo(AdSubmission::class, 'ad_submission_id');
    }
}
