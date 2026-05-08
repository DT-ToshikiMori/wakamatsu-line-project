<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $table = "app_settings";
    protected $fillable = ["key", "value", "group"];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("app_setting_{$key}", 300, function () use ($key, $default) {
            $setting = static::where("key", $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value, string $group = "general"): void
    {
        static::updateOrCreate(["key" => $key], ["value" => $value, "group" => $group]);
        Cache::forget("app_setting_{$key}");
    }
}
