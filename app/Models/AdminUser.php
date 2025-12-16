<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    protected $table = 'admin_users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password'];
}