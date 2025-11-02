<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    // Fillable field
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'is_draft',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_draft', 0)
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function isDraft(): bool
    {
        return $this->is_draft === 1;
    }

    public function isScheduled(): bool
    {
        return $this->is_draft === 0 && $this->published_at && Carbon::parse($this->published_at)->gte(now());
    }

    public function isPublished(): bool
    {
        return $this->is_draft === 0 &&
               (! empty($this->published_at) && Carbon::parse($this->published_at)->lte(now()));
    }
}
