<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitScenarioSend extends Model
{
    protected $table = 'visit_scenario_sends';

    protected $fillable = [
        'user_id',
        'scenario_id',
        'scheduled_at',
        'sent_at',
        'coupon_issued_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'coupon_issued_at' => 'datetime',
    ];

    public function scenario()
    {
        return $this->belongsTo(VisitScenario::class, 'scenario_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
