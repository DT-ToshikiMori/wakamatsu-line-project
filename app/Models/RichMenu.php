<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RichMenu extends Model
{
    protected $fillable = [
        'name',
        'line_rich_menu_id',
        'chat_bar_text',
        'size_type',
        'template_key',
        'selected',
        'image_path',
        'is_default',
        'status',
        'synced_at',
    ];

    protected $casts = [
        'selected' => 'boolean',
        'is_default' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function areas()
    {
        return $this->hasMany(RichMenuArea::class)->orderBy('position');
    }

    public function clicks()
    {
        return $this->hasManyThrough(RichMenuClick::class, RichMenuArea::class);
    }

    public function toLineApiPayload(): array
    {
        $width = 2500;
        $height = $this->size_type === 'full' ? 1686 : 843;

        $areas = $this->areas->map(function (RichMenuArea $area) {
            $action = match ($area->action_type) {
                'postback' => array_filter([
                    'type'        => 'postback',
                    'data'        => $area->action_data ?? "action=richmenu_click&area_id={$area->id}",
                    // displayText: postback時にトーク画面に表示するテキスト（任意）
                    'displayText' => $area->label ?: null,
                ], fn ($v) => $v !== null),
                'uri' => [
                    'type'  => 'uri',
                    'label' => mb_substr($area->label, 0, 20), // LINE仕様: 最大20文字
                    // クリック計測のためリダイレクト経由。直接URLも有効なフォールバック
                    'uri'   => url("/rm/click/{$area->id}"),
                ],
                'message' => [
                    'type' => 'message',
                    'label' => mb_substr($area->label, 0, 20),
                    'text'  => $area->action_data ?? $area->label,
                ],
            };

            return [
                'bounds' => [
                    'x' => $area->x,
                    'y' => $area->y,
                    'width' => $area->width,
                    'height' => $area->height,
                ],
                'action' => $action,
            ];
        })->toArray();

        return [
            'size' => [
                'width' => $width,
                'height' => $height,
            ],
            'selected' => $this->selected,
            'name' => $this->name,
            'chatBarText' => $this->chat_bar_text,
            'areas' => $areas,
        ];
    }
}
