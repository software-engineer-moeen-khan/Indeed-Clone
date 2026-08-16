<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'placement',
        'type',
        'image_path',
        'target_url',
        'alt_text',
        'custom_code',
        'open_in_new_tab',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public static function placements(): array
    {
        return [
            'global_after_header' => 'Global - Below Header',
            'home_below_search' => 'Homepage - Below Search',
            'home_after_latest' => 'Homepage - After Latest Jobs',
            'jobs_above_results' => 'Jobs Page - Above Results',
            'jobs_after_results' => 'Jobs Page - Below Results',
            'job_details_top' => 'Job Details - Above Job Content',
            'on_apply_now' => 'On Apply Now',
            'on_find_jobs' => 'On Find Jobs',
            'global_before_footer' => 'Global - Before Footer',
        ];
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    public static function forPlacement(string $placement): Collection
    {
        return static::query()
            ->currentlyActive()
            ->where('placement', $placement)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(3)
            ->get();
    }
}
