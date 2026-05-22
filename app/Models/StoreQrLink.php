<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreQrLink extends Model
{
    protected $table = 'store_qr_links';
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (StoreQrLink $storeQrLink): void {
            if (! blank($storeQrLink->slug)) {
                return;
            }

            do {
                $slug = 'qr-' . Str::random(12);
            } while (static::query()->where('slug', $slug)->exists());

            $storeQrLink->slug = $slug;
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
