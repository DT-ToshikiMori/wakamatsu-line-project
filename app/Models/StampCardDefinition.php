<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StampCardDefinition extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'display_name',
        'required_stamps',
        'priority',
        'theme_bg',
        'theme_accent',
        'theme_logo_opacity',
        'is_active',
        'rankup_coupon_id',
        'checkin_coupon_id',
    ];

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function rankupCoupon()
    {
        return $this->belongsTo(CouponTemplate::class, 'rankup_coupon_id');
    }

    public function checkinCoupon()
    {
        return $this->belongsTo(CouponTemplate::class, 'checkin_coupon_id');
    }
}