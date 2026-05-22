<div
    x-data="{
        bubbles: $wire.data?.bubbles ?? [],
        reminders: $wire.data?.reminders ?? [],
        get couponBubble() {
            const list = Object.values(this.bubbles ?? {});
            return list.find(b => b.bubble_type === 'coupon') ?? null;
        },
        get couponTitle() {
            return this.couponBubble?.coupon_template_title || 'クーポン';
        },
        get couponImage() {
            return this.couponBubble?.coupon_template_image_url || null;
        },
        get reminderList() {
            return Object.values(this.reminders ?? {}).filter(r => r.before_days);
        },
    }"
    x-effect="
        bubbles = $wire.data?.bubbles ?? [];
        reminders = $wire.data?.reminders ?? [];
    "
    class="rounded-xl border border-gray-200 dark:border-gray-700 p-4"
    style="background: #7494c0; min-height: 120px;"
>
    <div class="text-xs font-bold mb-3" style="color: rgba(255,255,255,0.7);">リマインド プレビュー</div>

    {{-- クーポンが未設定の場合 --}}
    <template x-if="!couponBubble">
        <div class="text-xs text-center py-6" style="color: rgba(255,255,255,0.6);">
            ③ メッセージ内容にクーポンを追加するとプレビューが表示されます
        </div>
    </template>

    {{-- リマインドが未設定の場合 --}}
    <template x-if="couponBubble && reminderList.length === 0">
        <div class="text-xs text-center py-6" style="color: rgba(255,255,255,0.6);">
            リマインドを追加してください
        </div>
    </template>

    {{-- 各リマインドのプレビュー --}}
    <template x-if="couponBubble && reminderList.length > 0">
        <div class="flex flex-col gap-4">
            <template x-for="(reminder, idx) in reminderList" :key="idx">
                <div>
                    {{-- ラベル --}}
                    <div class="text-xs mb-1 font-semibold" style="color: rgba(255,255,255,0.8);">
                        <span x-text="'期限 ' + reminder.before_days + ' 日前 ' + (reminder.send_hour ?? 10) + ' 時送信'"></span>
                    </div>

                    {{-- Flex メッセージ（右寄せ） --}}
                    <div class="flex justify-end">
                        <div style="width: 220px; background: #ffffff; border-radius: 20px 4px 20px 20px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.15);">

                            {{-- Hero Image --}}
                            <div style="overflow: hidden;">
                                <template x-if="couponImage">
                                    <img :src="couponImage" style="width: 100%; aspect-ratio: 1/1; object-fit: cover; display: block;" />
                                </template>
                                <template x-if="!couponImage">
                                    <div style="width: 100%; aspect-ratio: 1/1; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); display: flex; align-items: center; justify-content: center;">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 28px; height: 28px; color: rgba(255,255,255,0.8);" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>

                            {{-- Body --}}
                            <div style="padding: 10px 12px 6px;">
                                <div style="font-size: 11px; color: #e74c3c; font-weight: bold; margin-bottom: 4px;">
                                    ⏰ クーポンの期限が近づいています
                                </div>
                                <div style="font-weight: bold; font-size: 13px; color: #111111; margin-bottom: 8px;" x-text="couponTitle"></div>
                                <div style="display: flex; gap: 6px; font-size: 10px; line-height: 1.4;">
                                    <span style="color: #aaaaaa; flex-shrink: 0;">有効期限</span>
                                    <span style="color: #e74c3c;" x-text="'あと ' + reminder.before_days + ' 日'"></span>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div style="padding: 6px 12px 10px; text-align: center; border-top: 1px solid #f0f0f0; margin-top: 6px;">
                                <span style="color: #e74c3c; font-size: 12px; font-weight: bold;">クーポンを確認する</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
