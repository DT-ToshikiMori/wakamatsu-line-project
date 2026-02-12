<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChurnScenario extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'days_after_last_stamp',
        'send_hour',
        'send_minute',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function bubbles()
    {
        return $this->morphMany(MessageBubble::class, 'parent', 'parent_type', 'parent_id')
            ->orderBy('position');
    }
}
