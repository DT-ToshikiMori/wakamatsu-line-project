<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotteryResult extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'coupon_template_id',
        'lottery_prize_id',
        'user_coupon_id',
        'trigger_type',
        'is_win',
        'drawn_at',
    ];

    protected $casts = [
        'is_win' => 'boolean',
        'drawn_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function couponTemplate()
    {
        return $this->belongsTo(CouponTemplate::class);
    }

    public function lotteryPrize()
    {
        return $this->belongsTo(LotteryPrize::class);
    }
}
