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
            <div style="max-width: 75%;">
                {{-- テキストバブル --}}
                <template x-if="bubble.bubble_type === 'text' && bubble.text_content">
                    <div style="background: #ffffff; color: #111111; white-space: pre-wrap; font-size: 13px; line-height: 1.5; padding: 10px 14px; border-radius: 20px 4px 20px 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);" x-text="bubble.text_content"></div>
                </template>

                {{-- クーポンバブル (Flex Message風) --}}
                <template x-if="bubble.bubble_type === 'coupon'">
                    <div style="background: #ffffff; border-radius: 20px 4px 20px 20px; overflow: hidden; width: 220px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                        {{-- Hero Image --}}
                        <div style="height: 72px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <template x-if="bubble.coupon_template_image_url">
                                <img :src="bubble.coupon_template_image_url" style="width: 100%; height: 72px; object-fit: cover;" />
                            </template>
                            <template x-if="!bubble.coupon_template_image_url">
                                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 28px; height: 28px; color: rgba(255,255,255,0.8);" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z"/>
                                    </svg>
                                </div>
                            </template>
                        </div>
                        {{-- Body --}}
                        <div style="padding: 10px 12px 6px;">
                            <div style="font-weight: bold; font-size: 13px; color: #111111; margin-bottom: 8px;" x-text="bubble.coupon_template_title || 'クーポン'"></div>
                            <div style="display: flex; gap: 6px; font-size: 10px; margin-bottom: 3px; line-height: 1.4;">
                                <span style="color: #aaaaaa; flex-shrink: 0;">有効期限</span>
                                <span style="color: #666666;" x-text="bubble.coupon_expires_text || '未設定'"></span>
                            </div>
                            <template x-if="bubble.coupon_template_note">
                                <div style="display: flex; gap: 6px; font-size: 10px; line-height: 1.4;">
                                    <span style="color: #aaaaaa; flex-shrink: 0;">備考</span>
                                    <span style="color: #666666;" x-text="bubble.coupon_template_note"></span>
                                </div>
                            </template>
                        </div>
                        {{-- Footer --}}
                        <div style="padding: 6px 12px 10px; text-align: center; border-top: 1px solid #f0f0f0; margin-top: 6px;">
                            <span style="color: #06c755; font-size: 12px; font-weight: bold;">クーポンを取得する</span>
                        </div>
                    </div>
                </template>

                {{-- 未設定 --}}
                <template x-if="!bubble.bubble_type">
                    <div style="background: rgba(255,255,255,0.5); color: #9ca3af; font-size: 11px; font-style: italic; padding: 8px 14px; border-radius: 20px 4px 20px 20px;">
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
