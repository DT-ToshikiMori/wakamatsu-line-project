<div
    x-data="{
        bubbles: $wire.data.bubbles || [],
    }"
    x-effect="bubbles = $wire.data.bubbles || []"
    class="rounded-xl border border-gray-200 dark:border-gray-700 p-4"
    style="background: #7494c0; min-height: 120px;"
>
    <div class="text-xs font-bold mb-3" style="color: rgba(255,255,255,0.7);">LINE プレビュー</div>

    <div class="flex flex-col gap-2 items-end">
        <template x-for="(bubble, index) in Object.values(bubbles)" :key="index">
            <div class="max-w-[80%]">
                {{-- テキストバブル --}}
                <template x-if="bubble.bubble_type === 'text' && bubble.text_content">
                    <div class="rounded-2xl rounded-tr-sm px-4 py-2 shadow-sm text-sm" style="background: #ffffff; color: #1f2937; white-space: pre-wrap;" x-text="bubble.text_content"></div>
                </template>

                {{-- クーポンバブル (Flex Message風) --}}
                <template x-if="bubble.bubble_type === 'coupon'">
                    <div class="rounded-2xl rounded-tr-sm shadow-sm overflow-hidden" style="background: #ffffff; width: 240px;">
                        {{-- Hero Image --}}
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 80px; display: flex; align-items: center; justify-content: center;">
                            <template x-if="bubble.coupon_template_image_url">
                                <img :src="bubble.coupon_template_image_url" style="width: 100%; height: 80px; object-fit: cover;" />
                            </template>
                            <template x-if="!bubble.coupon_template_image_url">
                                <svg xmlns="http://www.w3.org/2000/svg" style="width: 32px; height: 32px; color: rgba(255,255,255,0.8);" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z"/>
                                </svg>
                            </template>
                        </div>
                        {{-- Body --}}
                        <div style="padding: 12px 14px;">
                            <div style="font-weight: bold; font-size: 14px; color: #1f2937; margin-bottom: 8px;" x-text="bubble.coupon_template_title || 'クーポン'"></div>
                            <div style="display: flex; gap: 4px; font-size: 11px; margin-bottom: 4px;">
                                <span style="color: #aaaaaa; flex-shrink: 0;">有効期限</span>
                                <span style="color: #666666;">発行から一定期間</span>
                            </div>
                            <template x-if="bubble.coupon_template_note">
                                <div style="display: flex; gap: 4px; font-size: 11px;">
                                    <span style="color: #aaaaaa; flex-shrink: 0;">備考</span>
                                    <span style="color: #666666;" x-text="bubble.coupon_template_note"></span>
                                </div>
                            </template>
                        </div>
                        {{-- Footer --}}
                        <div style="padding: 8px 14px 12px; text-align: center;">
                            <span style="color: #06c755; font-size: 13px; font-weight: bold;">クーポンを取得する</span>
                        </div>
                    </div>
                </template>

                {{-- 未設定 --}}
                <template x-if="!bubble.bubble_type">
                    <div class="rounded-2xl rounded-tr-sm px-4 py-2 shadow-sm text-xs italic" style="background: rgba(255,255,255,0.5); color: #9ca3af;">
                        未設定
                    </div>
                </template>
            </div>
        </template>
    </div>

    <template x-if="Object.values(bubbles).length === 0">
        <div class="text-xs text-center py-6" style="color: rgba(255,255,255,0.5);">バブルを追加してください</div>
    </template>
</div>
