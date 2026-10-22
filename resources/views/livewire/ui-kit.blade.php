<div class="space-y-6">
    <x-ui.page-header title="UI Kit" description="Every reusable component in one place — copy from here." />

    {{-- BUTTONS --}}
    <x-ui.card title="Buttons">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button>Primary</x-ui.button>
            <x-ui.button variant="secondary">Secondary</x-ui.button>
            <x-ui.button variant="ghost">Ghost</x-ui.button>
            <x-ui.button variant="danger">Danger</x-ui.button>
            <x-ui.button variant="danger-soft">Danger soft</x-ui.button>
            <x-ui.button icon="plus">With icon</x-ui.button>
            <x-ui.button size="sm">Small</x-ui.button>
            <x-ui.button size="lg">Large</x-ui.button>
            <x-ui.button disabled>Disabled</x-ui.button>
            <x-ui.button wire:click="destroy" target="destroy">Loading spinner (click)</x-ui.button>
        </div>
    </x-ui.card>

    {{-- BADGES + STATS --}}
    <x-ui.card title="Badges & stat cards">
        <div class="flex flex-wrap gap-2 mb-5">
            <x-ui.badge dot>Active</x-ui.badge>
            <x-ui.badge color="gray" dot>Inactive</x-ui.badge>
            <x-ui.badge color="red" dot>Expired</x-ui.badge>
            <x-ui.badge color="amber" dot>Low stock</x-ui.badge>
            <x-ui.badge color="blue">Draft</x-ui.badge>
            <x-ui.badge color="teal">Ordered</x-ui.badge>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <x-ui.stat-card label="Sales" value="₱12,450" hint="+8%" icon="cash" />
            <x-ui.stat-card label="Orders" value="38" icon="receipt" color="teal" />
            <x-ui.stat-card label="Low stock" value="6" icon="warning" color="amber" />
            <x-ui.stat-card label="Expired" value="3" icon="clock" color="red" />
        </div>
    </x-ui.card>

    {{-- FORM --}}
    <x-ui.card title="Form inputs (live Livewire validation)">
        <form wire:submit="save" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.input class="sm:col-span-2" label="Supplier name" wire:model="name" placeholder="e.g. Metro Grocery Wholesale" required />
            <x-ui.input label="Email" type="email" wire:model.blur="email" placeholder="orders@supplier.com" hint="Blur the field to validate." required />
            <x-ui.input label="Search style" icon="search" placeholder="With a leading icon" />
            <x-ui.input label="Password" type="password" wire:model="password" placeholder="Has a show/hide toggle" />
            <x-ui.select label="Payment terms" wire:model="terms">
                <option value="cod">Cash on delivery</option>
                <option value="net15">Net 15</option>
                <option value="net30">Net 30</option>
            </x-ui.select>
            <x-ui.textarea class="sm:col-span-2" label="Notes" wire:model="notes" placeholder="Delivery days, minimum order…" />
            <div class="sm:col-span-2 space-y-3">
                <x-ui.switch label="Send low-stock alerts" wire:model.live="alerts" />
                <x-ui.checkbox wire:model="agree">I agree to the terms</x-ui.checkbox>
                <p class="text-xs text-gray-400">alerts = <span class="font-semibold" x-data x-text="$wire.alerts ? 'on' : 'off'"></span></p>
            </div>
            <div class="sm:col-span-2 flex justify-end">
                <x-ui.button type="submit" target="save">Validate & save</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- MODALS + TOAST --}}
    <x-ui.card title="Modals & toasts">
        <div class="flex flex-wrap gap-3">
            <x-ui.button x-on:click="$dispatch('open-modal', { name: 'demo-form' })" icon="plus">Open form modal</x-ui.button>
            <x-ui.button variant="danger-soft" x-on:click="$dispatch('open-modal', { name: 'demo-delete' })" icon="trash">Open confirm modal</x-ui.button>
            <x-ui.button variant="secondary" x-on:click="$dispatch('toast', { message: 'Hello from Alpine!' })">Alpine toast</x-ui.button>
            <x-ui.button variant="secondary" wire:click="$dispatch('toast', { message: 'Hello from Livewire!' })">Livewire toast</x-ui.button>
        </div>
    </x-ui.card>

    {{-- EMPTY STATE --}}
    <x-ui.card title="Empty state">
        <x-ui.empty-state icon="truck" title="No suppliers yet" description="Add your first supplier to start creating purchase orders.">
            <x-ui.button icon="plus" x-on:click="$dispatch('open-modal', { name: 'demo-form' })">Add supplier</x-ui.button>
        </x-ui.empty-state>
    </x-ui.card>

    {{-- modal definitions --}}
    <x-ui.modal name="demo-form" title="Add supplier" subtitle="Fields marked * are required">
        <div class="space-y-4">
            <x-ui.input label="Supplier name" wire:model="name" required />
            <x-ui.input label="Email" type="email" wire:model="email" />
        </div>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="$dispatch('close-modal', { name: 'demo-form' })">Cancel</x-ui.button>
            <x-ui.button wire:click="save" target="save">Add supplier</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-modal name="demo-delete" title="Delete supplier?" confirm="Delete" wire:click="destroy">
        This supplier will be removed from your list. Products already in inventory aren't changed.
    </x-ui.confirm-modal>
</div>
