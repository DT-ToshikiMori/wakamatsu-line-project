<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitScenario extends Model
{
    protected $table = 'visit_scenarios';

    protected $fillable = [
        'name',
        'stamp_card_definition_id',
        'stamp_number',
        'from_visit_count',
        'visit_count_min',
        'visit_count_max',
        'repeat',
        'segment_filter',
        'coupon_template_id',
        'delay_hours',
        'expires_days',
        'is_active',
        'trigger_type',
    ];

    public function stampCardDefinition()
    {
        return $this->belongsTo(StampCardDefinition::class);
    }

    public function couponTemplate()
    {
        return $this->belongsTo(\App\Models\CouponTemplate::class);
    }
}
