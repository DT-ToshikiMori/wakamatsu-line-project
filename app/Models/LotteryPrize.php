<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryPrize extends Model
{
    protected $fillable = [
        'coupon_template_id',
        'rank',
        'title',
        'image_url',
        'probability',
        'is_miss',
    ];

    protected $casts = [
        'is_miss' => 'boolean',
    ];

    public function couponTemplate()
    {
        return $this->belongsTo(CouponTemplate::class);
    }
}
