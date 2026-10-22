{{--
  Lightbox. Include ONCE per page, then open it from any thumbnail — no server round-trip:

    <x-ui.image-viewer />
    <button x-on:click="$dispatch('view-image', { src: '/images/product.svg', title: 'Coca-Cola', subtitle: 'Soft drink · Bottle', left: 'BEV-0002', right: '₱75' })">…</button>

  Esc / backdrop click / ✕ closes it; focus returns to the thumbnail.
--}}
<div x-data="{ open: false, item: {}, initials() { return String(this.item.title || '').split(/[\s-]+/).slice(0, 2).map(w => w[0]).join('').toUpperCase(); } }"
     x-on:view-image.window="item = $event.detail; open = true"
     x-on:keydown.escape.window="open = false"
     x-on:click.self="open = false"
     x-show="open" x-cloak style="display:none"
     :class="{ 'is-open': open }"
     class="ui-modal-backdrop ui-modal-backdrop--viewer"
     role="dialog" aria-modal="true" aria-labelledby="viewer-title">
    <div class="ui-modal-panel viewer-card relative" x-show="open" x-trap.noscroll="open"
         :class="{ 'is-open': open }" x-transition.opacity>
        <button type="button" @click="open = false" class="viewer-close" aria-label="Close image">
            <x-ui.icon name="x" class="w-5 h-5" />
        </button>
        <div class="viewer-img relative bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-6xl">
            <span x-text="initials()"></span>
            <template x-if="open && item.src">
                <img :src="item.src" :alt="item.title" class="absolute inset-0 w-full h-full object-contain" x-on:error="$el.remove()">
            </template>
        </div>
        <div class="px-5 py-4">
            <h2 id="viewer-title" class="text-base font-bold text-gray-900" x-text="item.title"></h2>
            <p class="text-sm text-gray-500 mt-0.5" x-text="item.subtitle" x-show="item.subtitle"></p>
            <div class="flex items-center justify-between mt-3 text-sm">
                <span class="text-gray-400" x-text="item.left"></span>
                <span class="font-bold text-gray-900" x-text="item.right"></span>
            </div>
        </div>
    </div>
</div>
