<div
    x-data="{
        bubbles: $wire.data.bubbles || [],
    }"
    x-effect="bubbles = $wire.data.bubbles || []"
    class="rounded-xl border border-gray-200 dark:border-gray-700 p-4"
    style="background: #7494c0; min-height: 120px;"
>
    <div class="text-xs font-bold text-white/70 mb-3">LINE プレビュー</div>

    <div class="flex flex-col gap-2 items-end">
        <template x-for="(bubble, index) in Object.values(bubbles)" :key="index">
            <div class="max-w-[80%]">
                {{-- テキストバブル --}}
                <template x-if="bubble.bubble_type === 'text' && bubble.text_content">
                    <div class="rounded-2xl rounded-tr-sm px-4 py-2 shadow-sm text-sm whitespace-pre-wrap" style="background: #ffffff; color: #1f2937;" x-text="bubble.text_content"></div>
                </template>

                {{-- クーポンバブル --}}
                <template x-if="bubble.bubble_type === 'coupon'">
                    <div class="rounded-2xl rounded-tr-sm px-4 py-3 shadow-sm text-sm" style="background: #ffffff; color: #1f2937;">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" style="color: #f97316;" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z"/>
                            </svg>
                            <span class="font-bold" style="color: #1f2937;">クーポン</span>
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
        <div class="text-white/50 text-xs text-center py-6">バブルを追加してください</div>
    </template>
</div>
