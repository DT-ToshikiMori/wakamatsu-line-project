<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitScenarioReminder extends Model
{
    protected $table = 'visit_scenario_reminders';

    protected $fillable = [
        'visit_scenario_id',
        'before_days',
        'send_hour',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scenario()
    {
        return $this->belongsTo(VisitScenario::class, 'visit_scenario_id');
    }
}
