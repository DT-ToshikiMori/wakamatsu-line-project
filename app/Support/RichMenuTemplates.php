<?php

namespace App\Support;

/**
 * リッチメニューテンプレート定義
 *
 * 座標系: LINE API 準拠 (原点左上)
 *   full  = 2500 × 1686
 *   half  = 2500 × 843
 */
class RichMenuTemplates
{
    /**
     * 全テンプレートの定義を返す
     */
    public static function all(): array
    {
        return [
            'large_6' => [
                'label'     => '大 6分割',
                'size_type' => 'full',
                'areas'     => self::large6Areas(),
                'svg'       => self::svg_large_6(),
            ],
            'large_4' => [
                'label'     => '大 4分割',
                'size_type' => 'full',
                'areas'     => self::large4Areas(),
                'svg'       => self::svg_large_4(),
            ],
            'large_4_top' => [
                'label'     => '大 4分割（上大）',
                'size_type' => 'full',
                'areas'     => self::large4TopAreas(),
                'svg'       => self::svg_large_4_top(),
            ],
            'small_3' => [
                'label'     => '小 3分割',
                'size_type' => 'half',
                'areas'     => self::small3Areas(),
                'svg'       => self::svg_small_3(),
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /** テンプレートからエリアのデフォルトデータを返す */
    public static function buildAreaRows(string $templateKey): array
    {
        $tpl = static::get($templateKey);
        if (!$tpl) {
            return [];
        }

        return collect($tpl['areas'])->map(fn ($a, $i) => [
            'label'       => 'ボタン' . ($i + 1),
            'x'           => $a['x'],
            'y'           => $a['y'],
            'width'       => $a['w'],
            'height'      => $a['h'],
            'action_type' => 'postback',
            'action_data' => '',
            'position'    => $i,
        ])->values()->all();
    }

    // ─── エリア座標定義 ───────────────────────────────────────

    private static function large6Areas(): array
    {
        // 2 × 3 グリッド (full 2500×1686)
        // 3列: 833 + 834 + 833 = 2500 / 2行: 843 + 843 = 1686
        return [
            ['x' =>    0, 'y' =>   0, 'w' => 833, 'h' => 843],
            ['x' =>  833, 'y' =>   0, 'w' => 834, 'h' => 843],
            ['x' => 1667, 'y' =>   0, 'w' => 833, 'h' => 843],
            ['x' =>    0, 'y' => 843, 'w' => 833, 'h' => 843],
            ['x' =>  833, 'y' => 843, 'w' => 834, 'h' => 843],
            ['x' => 1667, 'y' => 843, 'w' => 833, 'h' => 843],
        ];
    }

    private static function large4Areas(): array
    {
        // 2 × 2 グリッド (full 2500×1686)
        // 2列: 1250 + 1250 = 2500 / 2行: 843 + 843 = 1686
        return [
            ['x' =>    0, 'y' =>   0, 'w' => 1250, 'h' => 843],
            ['x' => 1250, 'y' =>   0, 'w' => 1250, 'h' => 843],
            ['x' =>    0, 'y' => 843, 'w' => 1250, 'h' => 843],
            ['x' => 1250, 'y' => 843, 'w' => 1250, 'h' => 843],
        ];
    }

    private static function large4TopAreas(): array
    {
        // 上1大 + 下3 (full 2500×1686)
        // 上: 2500 / 下3列: 833 + 834 + 833 = 2500
        return [
            ['x' =>    0, 'y' =>   0, 'w' => 2500, 'h' => 843],
            ['x' =>    0, 'y' => 843, 'w' =>  833, 'h' => 843],
            ['x' =>  833, 'y' => 843, 'w' =>  834, 'h' => 843],
            ['x' => 1667, 'y' => 843, 'w' =>  833, 'h' => 843],
        ];
    }

    private static function small3Areas(): array
    {
        // 1 × 3 グリッド (half 2500×843)
        // 3列: 833 + 834 + 833 = 2500
        return [
            ['x' =>    0, 'y' => 0, 'w' => 833, 'h' => 843],
            ['x' =>  833, 'y' => 0, 'w' => 834, 'h' => 843],
            ['x' => 1667, 'y' => 0, 'w' => 833, 'h' => 843],
        ];
    }

    // ─── SVG サムネイル ──────────────────────────────────────

    private static function svg_large_6(): string
    {
        return <<<SVG
<svg width="96" height="64" viewBox="0 0 96 64" xmlns="http://www.w3.org/2000/svg">
  <rect x="1"  y="1"  width="29" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
  <rect x="34" y="1"  width="29" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
  <rect x="67" y="1"  width="28" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
  <rect x="1"  y="35" width="29" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
  <rect x="34" y="35" width="29" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
  <rect x="67" y="35" width="28" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
</svg>
SVG;
    }

    private static function svg_large_4(): string
    {
        return <<<SVG
<svg width="96" height="64" viewBox="0 0 96 64" xmlns="http://www.w3.org/2000/svg">
  <rect x="1"  y="1"  width="45" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
  <rect x="50" y="1"  width="45" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
  <rect x="1"  y="35" width="45" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
  <rect x="50" y="35" width="45" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
</svg>
SVG;
    }

    private static function svg_large_4_top(): string
    {
        return <<<SVG
<svg width="96" height="64" viewBox="0 0 96 64" xmlns="http://www.w3.org/2000/svg">
  <rect x="1"  y="1"  width="94" height="28" rx="3" fill="#bfdbfe" stroke="#3b82f6" stroke-width="1.5"/>
  <rect x="1"  y="35" width="29" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
  <rect x="34" y="35" width="29" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
  <rect x="67" y="35" width="28" height="28" rx="3" fill="#dbeafe" stroke="#60a5fa" stroke-width="1.5"/>
</svg>
SVG;
    }

    private static function svg_small_3(): string
    {
        return <<<SVG
<svg width="96" height="36" viewBox="0 0 96 36" xmlns="http://www.w3.org/2000/svg">
  <rect x="1"  y="1" width="29" height="34" rx="3" fill="#e0f2fe" stroke="#38bdf8" stroke-width="1.5"/>
  <rect x="34" y="1" width="29" height="34" rx="3" fill="#e0f2fe" stroke="#38bdf8" stroke-width="1.5"/>
  <rect x="67" y="1" width="28" height="34" rx="3" fill="#e0f2fe" stroke="#38bdf8" stroke-width="1.5"/>
</svg>
SVG;
    }
}
