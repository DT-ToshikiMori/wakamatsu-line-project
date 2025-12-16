<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineUser extends Model
{
    protected $table = 'users'; // ← 来店用の users テーブル
    protected $guarded = [];
    protected $casts = [
        'last_visit_at' => 'datetime',
        'first_visit_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function visits()
    {
        return $this->hasMany(Visit::class, 'user_id');
    }

}