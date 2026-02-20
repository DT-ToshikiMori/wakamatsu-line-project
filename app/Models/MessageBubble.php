<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageBubble extends Model
{
    protected $fillable = [
        'parent_type',
        'parent_id',
        'position',
        'bubble_type',
        'text_content',
        'coupon_template_id',
        'coupon_expires_text',
        'coupon_expires_at',
        'coupon_expires_days',
    ];

    protected $casts = [
        'coupon_expires_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->morphTo('parent', 'parent_type', 'parent_id');
    }

    public function couponTemplate()
    {
        return $this->belongsTo(CouponTemplate::class);
    }
}
