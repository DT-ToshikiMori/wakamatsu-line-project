<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreCycleRow extends Model
{
    protected $table = 't';        // fromSub の alias と合わせる
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}