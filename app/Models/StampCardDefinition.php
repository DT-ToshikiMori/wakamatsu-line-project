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
    ];

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}