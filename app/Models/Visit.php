<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $guarded = [];
    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(LineUser::class, 'user_id'); // usersテーブルのPKに紐付く
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}