{{--
  Included once in the layout. Fire it from anywhere:
    Livewire PHP:  $this->dispatch('toast', message: 'Supplier saved');
                   $this->dispatch('toast', message: 'Something failed', type: 'error');
    Alpine/Blade:  $dispatch('toast', { message: 'Copied!' })
    After redirect: return redirect()->route('x')->with('toast', 'Saved!');
--}}
<div x-data="{
        show: false, message: '', type: 'success', timer: null,
        fire(d) {
            if (typeof d === 'string') d = { message: d };
            this.message = d.message ?? ''; this.type = d.type ?? 'success'; this.show = true;
            clearTimeout(this.timer); this.timer = setTimeout(() => this.show = false, 3500);
        },
     }"
     x-on:toast.window="fire($event.detail)"
     @if (session('toast')) x-init="fire(@js(session('toast')))" @endif
     x-show="show" x-cloak style="display:none"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     id="toast" role="status" aria-live="polite" aria-atomic="true"
     class="fixed bottom-4 inset-x-4 sm:inset-x-auto sm:bottom-6 sm:end-6 z-[9999] flex items-center gap-2 bg-brand-900 text-white text-sm font-medium px-5 py-3 rounded-xl shadow-lg">
    <svg x-show="type === 'success'" class="w-4 h-4 text-brand-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
    <svg x-show="type === 'error'" x-cloak class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
    <span x-text="message"></span>
</div>
