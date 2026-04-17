<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitScenario extends Model
{
    protected $table = 'visit_scenarios';

    protected $fillable = [
        'stamp_card_definition_id',
        'stamp_number',
        'from_visit_count',
        'segment_filter',
        'delay_hours',
        'expires_days',
        'is_active',
    ];

    public function stampCardDefinition()
    {
        return $this->belongsTo(StampCardDefinition::class);
    }
}
