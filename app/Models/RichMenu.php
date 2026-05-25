<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RichMenu extends Model
{
    protected $fillable = [
        'name',
        'line_rich_menu_id',
        'chat_bar_text',
        'size_type',
        'template_key',
        'target_stamp_card_definition_id',
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

    public function targetStampCardDefinition(): BelongsTo
    {
        return $this->belongsTo(StampCardDefinition::class, 'target_stamp_card_definition_id');
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
                    // LIFF URLはリダイレクトを挟むと、LINE内ブラウザ→ミニアプリの二重起動になりやすい。
                    // そのためLIFFは直リンク、それ以外はクリック計測リダイレクト経由にする。
                    'uri'   => $this->shouldBypassClickRedirect($area->action_data)
                        ? $area->action_data
                        : url("/rm/click/{$area->id}"),
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

    private function shouldBypassClickRedirect(?string $uri): bool
    {
        if (!$uri) {
            return false;
        }

        $liffId = (string) config('services.line.liff_id', '');
        if ($liffId !== '' && str_starts_with($uri, "https://liff.line.me/{$liffId}")) {
            return true;
        }

        return str_starts_with($uri, 'line://app/');
    }
}
