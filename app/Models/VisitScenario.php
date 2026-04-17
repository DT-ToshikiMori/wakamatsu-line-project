<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitScenario extends Model
{
    protected $table = 'visit_scenarios';

    protected $fillable = [
        'visit_number',
        'coupon_template_id',
        'delay_hours',
        'expires_days',
        'is_active',
    ];

    public function couponTemplate()
    {
        return $this->belongsTo(CouponTemplate::class);
    }
}
