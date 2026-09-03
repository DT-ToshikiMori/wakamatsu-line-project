<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RichMenuArea extends Model
{
    protected $fillable = [
        'rich_menu_id',
        'position',
        'label',
        'x',
        'y',
        'width',
        'height',
        'action_type',
        'action_data',
        'open_external_browser',
    ];

    protected $casts = [
        'open_external_browser' => 'boolean',
    ];

    public function richMenu()
    {
        return $this->belongsTo(RichMenu::class);
    }

    public function clicks()
    {
        return $this->hasMany(RichMenuClick::class);
    }
}
