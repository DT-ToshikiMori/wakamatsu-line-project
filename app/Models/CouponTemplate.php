<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CouponTemplate extends Model
{
    protected $table = 'coupon_templates';

    protected $fillable = [
        'store_id','type','mode','title','note','image_url','is_active',
        'birthday_offset_days',
        'inactive_days','inactive_hour','inactive_minute',
        'required_stamps',
        'rank_card_id',
    ];

    /**
     * image_url(パス or フルURL)を公開URLに変換
     */
    public static function resolveImageUrl(?string $value): ?string
    {
        if (!$value) return null;
        if (str_starts_with($value, 'http')) return $value;
        return url('storage/' . $value);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function rankCard()
    {
        return $this->belongsTo(\App\Models\StampCardDefinition::class, 'rank_card_id');
    }

    public function lotteryPrizes()
    {
        return $this->hasMany(LotteryPrize::class)->orderBy('rank');
    }
}