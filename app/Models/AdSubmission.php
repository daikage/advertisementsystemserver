<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_name',
        'description',
        'submitter_contact',
        'image_path',
        'status',
        'decline_reason',
    ];

    /**
     * Get the publicly accessible URL for the ad's image.
     */
    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image_path);
    }
}
