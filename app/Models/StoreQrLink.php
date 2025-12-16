<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreQrLink extends Model
{
    protected $table = 'store_qr_links';
    protected $guarded = [];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}