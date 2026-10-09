<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostalCodeGeocode extends Model
{
    protected $guarded = [];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'geocoded_at' => 'datetime',
    ];
}
