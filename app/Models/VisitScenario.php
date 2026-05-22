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
        'trigger_days',
        'send_hour',
        'reminder_enabled',
        'reminder_before_days',
        'reminder_hour',
    ];

    public function stampCardDefinition()
    {
        return $this->belongsTo(StampCardDefinition::class);
    }

    public function couponTemplate()
    {
        return $this->belongsTo(\App\Models\CouponTemplate::class);
    }

    public function bubbles()
    {
        return $this->morphMany(MessageBubble::class, 'parent', 'parent_type', 'parent_id')
            ->orderBy('position');
    }

    public function reminders()
    {
        return $this->hasMany(VisitScenarioReminder::class, 'visit_scenario_id')
            ->orderBy('before_days');
    }

    /** バブルにクーポンが含まれているか */
    public function hasCouponBubble(): bool
    {
        return $this->bubbles()->where('bubble_type', 'coupon')->exists();
    }
}
