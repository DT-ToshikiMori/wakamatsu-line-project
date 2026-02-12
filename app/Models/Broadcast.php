<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'filter_type',
        'filter_rank_card_id',
        'filter_days_since_visit',
        'filter_min_visits',
        'status',
        'scheduled_at',
        'sent_at',
        'sent_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function filterRankCard()
    {
        return $this->belongsTo(StampCardDefinition::class, 'filter_rank_card_id');
    }

    public function bubbles()
    {
        return $this->morphMany(MessageBubble::class, 'parent', 'parent_type', 'parent_id')
            ->orderBy('position');
    }

    public function logs()
    {
        return $this->hasMany(BroadcastLog::class);
    }
}
