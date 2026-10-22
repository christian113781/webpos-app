<div>
    <x-ui.page-header title="Suppliers" :description="$this->stats['active'] . ' active · ' . $this->stats['all'] . ' total'">
        <x-ui.button variant="secondary" icon="download" wire:click="exportCsv" target="exportCsv"
                     :disabled="$suppliers->total() === 0">Export CSV</x-ui.button>
        <x-ui.button icon="plus" wire:click="create" target="create">Add supplier</x-ui.button>
    </x-ui.page-header>

    <section aria-label="Supplier summary" class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
        <x-ui.stat-card compact label="Total" :value="$this->stats['all']" icon="truck" color="green" />
        <x-ui.stat-card compact label="Active" :value="$this->stats['active']" icon="check" color="teal" />
        <x-ui.stat-card compact label="Inactive" :value="$this->stats['inactive']" icon="x" color="amber"
                        class="col-span-2 sm:col-span-1" />
    </section>

    <x-ui.card flush aria-labelledby="table-heading" class="supplier-list-container">
        <div class="flex flex-col xl:flex-row xl:items-center gap-3 p-4 sm:p-5 border-b border-gray-100">
            <h2 id="table-heading" class="sr-only">Supplier list</h2>
            <x-ui.input class="xl:w-80 xl:flex-shrink-0" icon="search" type="search"
                        wire:model.live.debounce.200ms="search"
                        placeholder="Search name, contact or products…" aria-label="Search suppliers" />

            <x-ui.select class="xl:!w-auto xl:flex-shrink-0" wire:model.live="status" aria-label="Filter suppliers by status">
                <option value="all">All suppliers ({{ $this->stats['all'] }})</option>
                <option value="active">Active ({{ $this->stats['active'] }})</option>
                <option value="inactive">Inactive ({{ $this->stats['inactive'] }})</option>
            </x-ui.select>

            <x-ui.select class="xl:!w-auto xl:flex-shrink-0 xl:ms-auto" wire:model.live="sortBy" aria-label="Sort suppliers">
                <option value="name">Sort: Name (A–Z)</option>
                <option value="recent">Sort: Recently added</option>
                <option value="terms">Sort: Payment terms</option>
            </x-ui.select>
        </div>

        <div wire:loading.class="opacity-60" wire:target="search,status,sortBy,gotoPage,nextPage,previousPage"
             class="transition-opacity">
            @if ($suppliers->total() > 0)
                <div class="hidden xl:block" role="region" aria-labelledby="table-heading" tabindex="0">
                    <x-ui.table caption="Suppliers" min-width="48rem">
                        <x-slot:head>
                            <x-ui.th>Supplier</x-ui.th>
                            <x-ui.th>Contact</x-ui.th>
                            <x-ui.th>Payment terms</x-ui.th>
                            <x-ui.th>Status</x-ui.th>
                            <x-ui.th class="text-end">Actions</x-ui.th>
                        </x-slot:head>

                        @foreach ($suppliers as $s)
                            <tr wire:key="supplier-row-{{ $s->id }}" class="hover:bg-gray-50/60 transition-colors">
                                <td class="px-5 py-3 max-w-xs">
                                    <p class="font-medium text-gray-800">{{ $s->name }}</p>
                                    <p class="text-[11px] text-gray-400 truncate">{{ $s->supplies ?: 'No products listed' }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <p class="text-gray-700">{{ $s->contact ?: '—' }}</p>
                                    <p class="text-[11px] text-gray-400">
                                        {{ $s->phone }}@if ($s->phone && $s->email) · @endif{{ $s->email }}
                                    </p>
                                </td>
                                <td class="px-3 py-3 text-gray-600">{{ $s->terms }}</td>
                                <td class="px-3 py-3">
                                    <x-ui.badge :color="$s->status === 'active' ? 'green' : 'gray'" dot>{{ ucfirst($s->status) }}</x-ui.badge>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" wire:click="view({{ $s->id }})" aria-label="View {{ $s->name }}" title="View"
                                                class="table-action rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                                            <x-ui.icon name="eye" class="w-4 h-4" />
                                        </button>
                                        <button type="button" wire:click="edit({{ $s->id }})" aria-label="Edit {{ $s->name }}" title="Edit"
                                                class="table-action rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                                            <x-ui.icon name="pencil" class="w-4 h-4" />
                                        </button>
                                        <button type="button" wire:click="confirmDelete({{ $s->id }})" aria-label="Delete {{ $s->name }}" title="Delete"
                                                class="table-action rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                                            <x-ui.icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </div>

                <ul class="supplier-card-list xl:hidden grid grid-cols-1" role="list" aria-label="Supplier list">
                    @foreach ($suppliers as $s)
                    <li wire:key="supplier-card-{{ $s->id }}" class="p-4 border-b border-gray-100">
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 text-sm leading-snug truncate">{{ $s->name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $s->supplies ?: 'No products listed' }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ collect([$s->contact, $s->terms])->filter()->implode(' · ') }}</p>
                            </div>

                            <div class="-me-2 shrink-0">
                                <x-ui.dropdown panel-class="absolute end-2 top-full mt-0.5 w-44">
                                    <x-slot:trigger>
                                        <button type="button" aria-haspopup="menu" aria-label="Actions for {{ $s->name }}"
                                                class="w-11 h-11 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                                            <span class="text-xl leading-none" aria-hidden="true">⋮</span>
                                        </button>
                                    </x-slot:trigger>
                                    <div class="py-1">
                                        <x-ui.menu-item icon="eye" wire:click="view({{ $s->id }})">View details</x-ui.menu-item>
                                        <x-ui.menu-item icon="pencil" wire:click="edit({{ $s->id }})">Edit</x-ui.menu-item>
                                        <div class="my-1 border-t border-gray-100" role="separator"></div>
                                        <x-ui.menu-item icon="trash" danger wire:click="confirmDelete({{ $s->id }})">Delete</x-ui.menu-item>
                                    </div>
                                </x-ui.dropdown>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mt-3 pt-3 border-t border-gray-50 text-xs">
                            @if ($s->phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $s->phone) }}"
                                   class="inline-flex items-center gap-1.5 font-medium text-brand-600 hover:text-brand-700 min-h-6">
                                    <x-ui.icon name="phone" class="w-3.5 h-3.5" />{{ $s->phone }}
                                </a>
                            @else
                                <span class="text-gray-400">No phone</span>
                            @endif
                            <x-ui.badge :color="$s->status === 'active' ? 'green' : 'gray'" dot class="ms-auto">
                                {{ ucfirst($s->status) }}
                            </x-ui.badge>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @else
                @if ($this->stats['all'] === 0)
                    <div class="p-10 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-brand-50 flex items-center justify-center mx-auto mb-3" aria-hidden="true">
                            <x-ui.icon name="inbox" class="w-6 h-6 text-brand-600" />
                        </div>
                        <h3 class="text-base font-bold text-gray-900">No suppliers yet</h3>
                        <p class="text-sm text-gray-500 mt-1">Add the companies you buy stock from so they show up when you add products.</p>
                        <x-ui.button class="mt-4" wire:click="create">Add your first supplier</x-ui.button>
                    </div>
                @else
                    <p class="p-8 text-center text-sm text-gray-400">No suppliers match your filters.</p>
                @endif
            @endif
        </div>

        @if ($suppliers->total() > 10)
            {{ $suppliers->links('pagination.brand') }}
        @endif
    </x-ui.card>

    <x-ui.modal name="supplier-form" :title="$form->id ? 'Edit supplier' : 'Add supplier'" mobile-full-width
                subtitle="Fields marked * are required">
        @php
            $paymentTerms = ['Cash on delivery', 'Net 7', 'Net 15', 'Net 30', 'Net 60'];
            if ($form->terms !== '' && ! in_array($form->terms, $paymentTerms, true)) {
                array_unshift($paymentTerms, $form->terms);
            }
        @endphp
        <form id="supplier-form" wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.input class="sm:col-span-2" label="Supplier name" wire:model="form.name"
                            placeholder="e.g. Metro Grocery Wholesale" required autocomplete="off" />
                <x-ui.input label="Contact person" wire:model="form.contact" placeholder="e.g. Rico Mercado" autocomplete="off" />
                <x-ui.input label="Phone" type="tel" wire:model="form.phone" placeholder="e.g. 0917 123 4567" required autocomplete="off" />
                <x-ui.input class="sm:col-span-2" label="Email" type="email" wire:model="form.email"
                            placeholder="orders@supplier.com" autocomplete="off" />
                <x-ui.input class="sm:col-span-2" label="Address" wire:model="form.address"
                            placeholder="Street, city" autocomplete="off" />
                <x-ui.input class="sm:col-span-2" label="Products supplied" wire:model="form.supplies"
                            placeholder="e.g. Canned goods, beverages, snacks" autocomplete="off" />
                <x-ui.select label="Payment terms" wire:model="form.terms" required>
                    @foreach ($paymentTerms as $term)
                        <option value="{{ $term }}">{{ $term }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select label="Status" wire:model="form.status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </x-ui.select>
                <x-ui.textarea class="sm:col-span-2" label="Notes" wire:model="form.notes" rows="3"
                               placeholder="Delivery days, minimum order, account number…" />
            </div>
            @if ($form->status === 'inactive')
                <p class="text-xs text-gray-400">Inactive suppliers are hidden from the supplier list when adding new products.</p>
            @endif
        </form>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="$dispatch('close-modal', { name: 'supplier-form' })">Cancel</x-ui.button>
            <x-ui.button type="submit" form="supplier-form" target="save">
                {{ $form->id ? 'Save changes' : 'Add supplier' }}
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="supplier-view" :title="$this->viewing?->name ?? 'Supplier details'" max-width="md" mobile-full-width>
        @if ($this->viewing)
            <div class="mb-2">
                <x-ui.badge :color="$this->viewing->status === 'active' ? 'green' : 'gray'" dot>{{ ucfirst($this->viewing->status) }}</x-ui.badge>
            </div>
            <dl class="divide-y divide-gray-50">
                <div class="grid grid-cols-3 gap-3 py-2.5">
                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Contact</dt>
                    <dd class="col-span-2 text-sm text-gray-800">{{ $this->viewing->contact ?: '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-3 py-2.5">
                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Phone</dt>
                    <dd class="col-span-2 text-sm text-gray-800">
                        @if ($this->viewing->phone)
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $this->viewing->phone) }}" class="text-brand-600 hover:text-brand-700">{{ $this->viewing->phone }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="grid grid-cols-3 gap-3 py-2.5">
                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Email</dt>
                    <dd class="col-span-2 text-sm text-gray-800 break-words">
                        @if ($this->viewing->email)
                            <a href="mailto:{{ $this->viewing->email }}" class="text-brand-600 hover:text-brand-700">{{ $this->viewing->email }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="grid grid-cols-3 gap-3 py-2.5">
                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Address</dt>
                    <dd class="col-span-2 text-sm text-gray-800 break-words">{{ $this->viewing->address ?: '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-3 py-2.5">
                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Products</dt>
                    <dd class="col-span-2 text-sm text-gray-800 break-words">{{ $this->viewing->supplies ?: '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-3 py-2.5">
                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Payment terms</dt>
                    <dd class="col-span-2 text-sm text-gray-800">{{ $this->viewing->terms }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-3 py-2.5">
                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Notes</dt>
                    <dd class="col-span-2 text-sm text-gray-800 whitespace-pre-line break-words">{{ $this->viewing->notes ?: '—' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-3 py-2.5">
                    <dt class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Added</dt>
                    <dd class="col-span-2 text-sm text-gray-800">{{ $this->viewing->created_at->format('M d, Y') }}</dd>
                </div>
            </dl>
        @endif
        <x-slot:footer>
            @if ($this->viewing)
                @if ($this->viewing->phone)
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $this->viewing->phone) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 border border-gray-200 hover:bg-gray-50 min-h-11">
                        <x-ui.icon name="phone" class="w-4 h-4" />Call
                    </a>
                @endif
                @if ($this->viewing->email)
                    <a href="mailto:{{ $this->viewing->email }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 border border-gray-200 hover:bg-gray-50 min-h-11">
                        <x-ui.icon name="mail" class="w-4 h-4" />Email
                    </a>
                @endif
                <x-ui.button variant="secondary" wire:click="edit({{ $this->viewing->id }})" icon="pencil">Edit</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm-modal name="delete-supplier" title="Delete supplier?" confirm="Delete" wire:click="delete"
                        mobile-full-width>
        <strong class="text-gray-800">{{ $this->deleting?->name }}</strong> will be removed from your supplier list.
        Products already in inventory aren’t changed. If you may order from them again, set them to Inactive instead.
    </x-ui.confirm-modal>
</div>
