<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RichMenuClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'rich_menu_area_id',
        'user_id',
        'line_user_id',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    public function area()
    {
        return $this->belongsTo(RichMenuArea::class, 'rich_menu_area_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
