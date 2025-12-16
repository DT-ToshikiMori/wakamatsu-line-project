<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable implements FilamentUser
{
    protected $table = 'admin_users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password'];

    public function canAccessPanel(Panel $panel): bool
    {
        // デモでは全員OK。本番はここで権限分岐できる
        return $panel->getId() === 'admin';
    }
}