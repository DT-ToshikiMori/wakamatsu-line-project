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
        'birth_year' => 'integer',
        'birth_month' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * line_user_id でグローバルに一意なユーザーを検索
     */
    public static function findByLineUserId(string $lineUserId): ?self
    {
        return static::where('line_user_id', $lineUserId)->first();
    }

    public function visits()
    {
        return $this->hasMany(Visit::class, 'user_id');
    }

}