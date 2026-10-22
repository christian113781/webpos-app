<main id="main" tabindex="-1" class="flex-1 px-4 sm:px-6 py-6 max-w-[1600px] mx-auto w-full">
    <x-ui.page-header title="Edit supplier" description="Update the supplier’s contact information.">
    </x-ui.page-header>
    <x-ui.card title="Supplier information" class="w-full">
        <form wire:submit="save" novalidate class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.input label="Supplier name" wire:model="name" placeholder="e.g. Metro Grocery Wholesale" required />
                <x-ui.input label="Contact person" wire:model="contact_person" placeholder="e.g. Alex Santos" />
                <x-ui.input label="Phone number" type="tel" wire:model="phone" placeholder="e.g. 0917 555 0100" />
                <x-ui.input label="Email address" type="email" wire:model="email" placeholder="e.g. orders@example.com" />
                <x-ui.textarea label="Address" wire:model="address" rows="3" class="sm:col-span-2" placeholder="Supplier business address" />
                <x-ui.textarea label="Notes" wire:model="notes" rows="3" class="sm:col-span-2" placeholder="Additional notes about this supplier" />
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 border-t border-gray-100 pt-4">
                <x-ui.button variant="secondary" :href="route('user.suppliers')" wire:navigate>
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" target="save">
                    Save changes
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</main>
