<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StampCardDefinition extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'required_stamps',
        'priority',
        'theme_bg',
        'theme_accent',
        'theme_logo_opacity',
        'show_stamp_marks',
        'is_active',
        'rankup_coupon_id',
        'rankup_coupon_expires_days',
        'checkin_coupon_id',
        'checkin_coupon_expires_days',
    ];

    protected $casts = [
        'show_stamp_marks' => 'boolean',
        'is_active' => 'boolean',
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