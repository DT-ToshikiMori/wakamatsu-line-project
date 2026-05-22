@php
    use App\Support\RichMenuTemplates;
    $templates = RichMenuTemplates::all();
@endphp

<div
    x-data="{
        selected: '',
        init() {
            $nextTick(() => {
                $wire.get('data.template_key').then(v => {
                    if (v) this.selected = v;
                });
            });
        },
        pick(key) {
            this.selected = key;
            $wire.set('data.template_key', key, true);
        }
    }"
    class="space-y-2"
>
    <label class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
        テンプレート
        <span class="ml-1 text-xs font-normal text-gray-500">（選択するとエリアが自動設定されます）</span>
    </label>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($templates as $key => $tpl)
            <div
                @click="pick('{{ $key }}')"
                :class="selected === '{{ $key }}'
                    ? 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/30 shadow-sm'
                    : 'ring-1 ring-gray-200 dark:ring-gray-600 hover:ring-primary-300 hover:bg-gray-50 dark:hover:bg-gray-800'"
                class="relative cursor-pointer rounded-xl p-3 text-center transition-all duration-150 select-none"
            >
                {{-- 選択チェックマーク --}}
                <div
                    x-show="selected === '{{ $key }}'"
                    class="absolute top-1.5 right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-primary-500"
                >
                    <svg class="h-3 w-3 text-white" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 6l3 3 5-5"/>
                    </svg>
                </div>

                {{-- SVG プレビュー --}}
                <div class="flex justify-center mb-2">
                    {!! $tpl['svg'] !!}
                </div>

                {{-- ラベル --}}
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $tpl['label'] }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $tpl['size_type'] === 'full' ? 'フルサイズ' : 'ハーフサイズ' }}</p>
            </div>
        @endforeach
    </div>

    {{-- 未選択時の注意 --}}
    <p
        x-show="!selected"
        class="text-xs text-amber-600 dark:text-amber-400"
    >
        ⚠ テンプレートを選択してください
    </p>
</div>
