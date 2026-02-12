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
