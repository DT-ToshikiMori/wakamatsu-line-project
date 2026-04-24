<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageCampaign extends Model
{
    protected $fillable = [
        'type',
        'name',
        'coupon_template_id',
        'coupon_expires_days',
        'text_content',
        'is_active',
        'offset_days',
        'send_hour',
        'send_minute',
        'seg_rank_id',
        'seg_stamp_min',
        'seg_stamp_max',
        'seg_visit_min',
        'send_at',
        'is_full_broadcast',
        'seg_gender',
        'seg_last_visit_within_days',
        'seg_last_visit_over_days',
        'birthday_send_day',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_full_broadcast' => 'boolean',
            'send_at' => 'datetime',
        ];
    }

    public function couponTemplate(): BelongsTo
    {
        return $this->belongsTo(CouponTemplate::class);
    }

    public function segRank(): BelongsTo
    {
        return $this->belongsTo(StampCardDefinition::class, 'seg_rank_id');
    }

    public function sends(): HasMany
    {
        return $this->hasMany(MessageSend::class, 'campaign_id');
    }
}
