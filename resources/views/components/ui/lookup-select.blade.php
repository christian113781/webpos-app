@props([
    'label',
    'name',
    'type',
    'items',
    'placeholder',
    'required' => false,
    'optional' => false,
    'disabledIds' => null,
    'labelClass' => '',
])
@php
    $model = $attributes->whereStartsWith('wire:model')->first();
    $xModel = $attributes->get('x-model');
    $idExpression = $attributes->get('x-bind:id');
    $key = $name ?? $model;
    $id = $attributes->get('id') ?? 'f-' . \Illuminate\Support\Str::slug($key ?? \Illuminate\Support\Str::random(6));
    $hasError = $key && $errors->has($key);
    $lookupItems = collect($items)->map(fn ($item) => [
        'id' => (string) data_get($item, 'id'),
        'name' => (string) data_get($item, 'name'),
        'subtitle' => (string) data_get($item, 'subtitle', ''),
    ])->values()->all();
@endphp

<div
    class="relative"
    x-modelable="selectedId"
    @if ($xModel) x-model="{{ $xModel }}" @endif
    x-data="{
        type: @js($type),
        items: @js($lookupItems),
        selectedId: @js($model) ? $wire.entangle(@js($model)) : '',
        disabledIds: [],
        query: '',
        open: false,
        mode: 'list',
        activeIndex: 0,
        saving: false,
        error: '',
        name: '',
        phone: '',
        get filteredItems() {
            const query = this.query.trim().toLowerCase();
            return this.items.filter(item => !query || `${item.name} ${item.subtitle}`.toLowerCase().includes(query));
        },
        get selectedName() {
            return this.items.find(item => item.id === String(this.selectedId || ''))?.name || '';
        },
        openPicker() {
            this.open = true;
            this.mode = 'list';
            this.query = '';
            this.activeIndex = 0;
            this.error = '';
            this.$nextTick(() => this.$refs.search?.focus());
        },
        closePicker(refocus = false) {
            this.open = false;
            this.mode = 'list';
            if (refocus) this.$nextTick(() => this.$refs.trigger.focus());
        },
        select(item) {
            if (this.isDisabled(item)) return;
            this.selectedId = item?.id || '';
            this.closePicker(true);
        },
        isDisabled(item) {
            return this.disabledIds.includes(String(item.id));
        },
        moveActive(direction) {
            if (!this.filteredItems.length) return;
            this.activeIndex = (this.activeIndex + direction + this.filteredItems.length) % this.filteredItems.length;
        },
        startAdd() {
            this.name = this.query.trim();
            this.phone = '';
            this.error = '';
            this.mode = 'add';
            this.$nextTick(() => this.$refs.name?.focus());
        },
        async saveNew() {
            if (this.saving) return;
            this.saving = true;
            this.error = '';
            try {
                const result = this.type === 'category'
                    ? await $wire.createCategoryLookup(this.name)
                    : await $wire.createSupplierLookup(this.name, this.phone);
                if (result.errors) {
                    this.error = result.errors.name?.[0] || result.errors.phone?.[0] || 'Please check the entered details.';
                    return;
                }
                const item = { id: String(result.id), name: result.name, subtitle: result.subtitle || '' };
                if (!this.items.some(existing => existing.id === item.id)) this.items.push(item);
                this.select(item);
            } finally {
                this.saving = false;
            }
        },
    }"
    @if ($disabledIds) x-effect="disabledIds = {{ $disabledIds }}" @endif
    @click.outside="closePicker()"
>
    <label
        @if ($idExpression) :id="{{ $idExpression }} + '-label'" :for="{{ $idExpression }} + '-trigger'"
        @else id="{{ $id }}-label" for="{{ $id }}" @endif
        class="block text-xs font-semibold text-gray-600 mb-1.5 {{ $labelClass }}"
    >
        {{ $label }}
        @if ($required) <span class="text-red-500" aria-hidden="true">*</span> @endif
        @if ($optional) <span class="font-normal text-gray-400">(optional)</span> @endif
    </label>

    <button
        x-ref="trigger"
        @if ($idExpression) :id="{{ $idExpression }} + '-trigger'" @else id="{{ $id }}-trigger" @endif
        type="button"
        role="combobox"
        aria-haspopup="listbox"
        @if ($idExpression) :aria-labelledby="{{ $idExpression }} + '-label'" @else aria-labelledby="{{ $id }}-label" @endif
        @if ($idExpression) :aria-controls="{{ $idExpression }} + '-panel'" @else aria-controls="{{ $id }}-panel" @endif
        :aria-expanded="open.toString()"
        @click="open ? closePicker() : openPicker()"
        class="field flex items-center justify-between gap-2 text-start {{ $hasError ? 'error' : '' }}"
        @if ($required) aria-required="true" @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
    >
        <span class="truncate" :class="selectedName ? 'text-gray-800' : 'text-gray-400'" x-text="selectedName || @js($placeholder)"></span>
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    @if ($key)
        @error($key)
            <p id="{{ $id }}-error" class="mt-1 text-xs text-red-500" role="alert">{{ $message }}</p>
        @enderror
    @endif

    <div
        x-cloak
        x-show="open"
        x-transition.opacity.duration.120ms
        class="fixed inset-0 z-40 bg-gray-900/40 sm:hidden"
        @click="closePicker()"
        aria-hidden="true"
    ></div>

    <div
        @if ($idExpression) :id="{{ $idExpression }} + '-panel'" @else id="{{ $id }}-panel" @endif
        x-cloak
        x-show="open"
        x-transition.opacity.duration.120ms
        class="fixed inset-x-0 bottom-0 z-50 flex max-h-[78dvh] min-h-[36dvh] flex-col overflow-hidden rounded-t-2xl border border-gray-200 bg-white shadow-xl sm:absolute sm:inset-x-0 sm:bottom-auto sm:top-full sm:mt-1 sm:min-h-0 sm:max-h-none sm:rounded-xl"
    >
        <template x-if="mode === 'list'">
            <div class="flex min-h-0 flex-1 flex-col">
                <div class="flex items-center justify-between px-4 pb-2 pt-3 sm:hidden">
                    <h2 class="text-sm font-semibold text-gray-900">Select {{ strtolower($label) }}</h2>
                    <button
                        type="button"
                        @click="closePicker()"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100"
                        aria-label="Close {{ strtolower($label) }} picker"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="border-b border-gray-100 p-3 sm:p-2.5">
                    <input
                        x-ref="search"
                        x-model="query"
                        type="search"
                        class="field"
                        style="font-size: 16px"
                        placeholder="Search {{ strtolower($label) }}..."
                        aria-label="Search {{ strtolower($label) }}"
                        autocomplete="off"
                        @input="activeIndex = 0"
                        @keydown.arrow-down.prevent="moveActive(1)"
                        @keydown.arrow-up.prevent="moveActive(-1)"
                        @keydown.enter.prevent="filteredItems[activeIndex] ? select(filteredItems[activeIndex]) : (type !== 'product' && startAdd())"
                        @keydown.escape.prevent="closePicker(true)"
                    >
                </div>
                <ul role="listbox" @if ($idExpression) :aria-labelledby="{{ $idExpression }} + '-label'" @else aria-labelledby="{{ $id }}-label" @endif class="min-h-0 flex-1 overflow-y-auto overscroll-contain py-1 sm:max-h-56">
                    @if (! $required)
                            <li role="option" :aria-selected="(! selectedId).toString()">
                            <button type="button" @click="select(null)" class="w-full px-3 py-2 text-start text-sm text-gray-500 hover:bg-gray-50">
                                No {{ strtolower($label) }}
                            </button>
                        </li>
                    @endif
                    <template x-for="(item, index) in filteredItems" :key="item.id">
                        <li role="option" :aria-selected="(String(selectedId || '') === item.id).toString()">
                            <button
                                type="button"
                                @click="select(item)"
                                @mouseenter="activeIndex = index"
                                :aria-disabled="isDisabled(item).toString()"
                                :disabled="isDisabled(item)"
                                class="w-full flex items-center gap-3 text-start px-3 py-2"
                                :class="isDisabled(item) ? 'opacity-50 cursor-not-allowed' : (activeIndex === index ? 'bg-brand-50' : '')"
                            >
                                <template x-if="type === 'supplier'">
                                    <span class="w-8 h-8 rounded-full bg-brand-50 flex items-center justify-center text-xs font-bold text-brand-700 flex-shrink-0" x-text="item.name.charAt(0).toUpperCase()"></span>
                                </template>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm text-gray-800 truncate" x-text="item.name"></span>
                                    <span x-show="item.subtitle" class="block text-xs text-gray-400 truncate" x-text="item.subtitle"></span>
                                </span>
                                <svg x-show="String(selectedId || '') === item.id" class="w-4 h-4 text-brand-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </li>
                    </template>
                    <li x-show="!filteredItems.length" class="px-4 py-5 text-center text-sm text-gray-400">No {{ strtolower($label) }} found.</li>
                </ul>
                <div x-show="type !== 'product'" class="border-t border-gray-100 p-1.5">
                    <button type="button" @click="startAdd()" class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-semibold text-brand-700 hover:bg-brand-50 text-start">
                        <span class="w-6 h-6 rounded-full bg-brand-100 flex items-center justify-center text-lg leading-none" aria-hidden="true">+</span>
                        <span x-text="query.trim() ? `Add &quot;${query.trim()}&quot;` : 'Add new {{ strtolower($label) }}'"></span>
                    </button>
                </div>
            </div>
        </template>

        <template x-if="mode === 'add'">
            <form @submit.prevent="saveNew()" class="p-3 space-y-3" novalidate>
                <div class="flex items-center gap-2">
                    <button type="button" @click="mode = 'list'; $nextTick(() => $refs.search.focus())" class="p-1.5 -ms-1.5 rounded-lg text-gray-400 hover:bg-gray-100" aria-label="Back to {{ strtolower($label) }} list">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <p class="text-sm font-bold text-gray-900">New {{ strtolower($label) }}</p>
                </div>
                <div>
                    <label for="{{ $id }}-new-name" class="block text-xs font-semibold text-gray-600 mb-1">{{ $label }} name <span class="text-red-500" aria-hidden="true">*</span></label>
                    <input x-ref="name" x-model="name" id="{{ $id }}-new-name" type="text" maxlength="255" class="field" style="font-size: 16px" placeholder="Enter {{ strtolower($label) }} name" autocomplete="off">
                </div>
                @if ($type === 'supplier')
                    <div>
                        <label for="{{ $id }}-new-phone" class="block text-xs font-semibold text-gray-600 mb-1">Phone <span class="font-normal text-gray-400">(optional)</span></label>
                        <input x-model="phone" id="{{ $id }}-new-phone" type="tel" maxlength="50" class="field" style="font-size: 16px" placeholder="e.g. 0917 555 0100" autocomplete="off">
                    </div>
                @endif
                <p x-show="error" x-text="error" class="text-xs text-red-500" role="alert"></p>
                <div class="flex gap-2 pt-1">
                    <button type="button" @click="mode = 'list'" class="flex-1 px-3 py-2.5 rounded-xl text-sm font-semibold text-gray-500 border border-gray-200 hover:bg-gray-50 min-h-[44px]">Cancel</button>
                    <button type="submit" :disabled="saving" class="flex-1 px-3 py-2.5 rounded-xl text-sm font-semibold bg-brand-600 text-white hover:bg-brand-700 disabled:opacity-60 min-h-[44px]">
                        <span x-text="saving ? 'Adding...' : 'Add & select'"></span>
                    </button>
                </div>
            </form>
        </template>
    </div>
</div>
