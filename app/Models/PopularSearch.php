<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopularSearch extends Model
{
    protected $fillable = [
        'label',
        'search_query',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
