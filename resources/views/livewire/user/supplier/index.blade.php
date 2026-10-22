<main id="main" tabindex="-1" class="flex-1 px-4 sm:px-6 py-6 space-y-6 max-w-[1600px] mx-auto w-full">
    <x-ui.page-header title="Suppliers" :description="$activeSuppliers . ' active · ' . $totalSuppliers . ' total'">
        <x-ui.button variant="secondary" icon="download" disabled>Export CSV</x-ui.button>
        <x-ui.button :href="route('user.suppliers.create')" wire:navigate icon="plus">Add supplier</x-ui.button>
    </x-ui.page-header>

    <section aria-label="Supplier summary" class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <x-ui.stat-card compact label="Total" :value="$totalSuppliers" icon="truck" color="green" />
        <x-ui.stat-card compact label="Active" :value="$activeSuppliers" icon="check" color="teal" />
        <x-ui.stat-card compact label="Inactive" :value="$inactiveSuppliers" icon="x" color="amber" class="col-span-2 sm:col-span-1" />
    </section>

    <x-ui.card flush aria-labelledby="supplier-list-heading">
        <div class="p-4 sm:p-5 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 xl:flex xl:items-center gap-3">
            <h2 id="supplier-list-heading" class="sr-only">Supplier list</h2>

            <x-ui.input
                class="sm:col-span-2 xl:col-span-1 xl:flex-1 xl:max-w-sm"
                icon="search"
                type="search"
                placeholder="Search name, contact or products…"
                aria-label="Search suppliers"
            />

            <x-ui.select class="xl:w-56" aria-label="Filter by status">
                <option value="">All suppliers</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </x-ui.select>

            <x-ui.select class="xl:w-56 xl:ms-auto" aria-label="Sort suppliers">
                <option value="name">Sort: Name (A–Z)</option>
                <option value="recent">Sort: Recently added</option>
                <option value="terms">Sort: Payment terms</option>
            </x-ui.select>
        </div>

        @if ($suppliers->isEmpty())
            <x-ui.empty-state
                icon="truck"
                title="No suppliers yet"
                description="Add the companies you buy stock from so they show up when you add products."
                class="py-10 sm:py-12"
            >
                <x-ui.button :href="route('user.suppliers.create')" wire:navigate icon="plus">Add supplier</x-ui.button>
            </x-ui.empty-state>
        @else
            <div class="hidden xl:block overflow-x-auto" role="region" aria-labelledby="supplier-list-heading" tabindex="0">
                <table class="w-full text-start text-sm" style="min-inline-size: 48rem">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Supplier</th>
                            <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Contact person</th>
                            <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Phone</th>
                            <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-3 py-3 text-end font-semibold text-gray-500 text-xs uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($suppliers as $supplier)
                            <tr wire:key="supplier-row-{{ $supplier->id }}" class="hover:bg-gray-50/60 transition-colors">
                                <th scope="row" class="px-3 py-3 text-start font-medium text-gray-800">{{ $supplier->name }}</th>
                                <td class="px-3 py-3 text-start text-gray-600">{{ $supplier->contact_person ?: '—' }}</td>
                                <td class="px-3 py-3 text-start text-gray-600">{{ $supplier->phone ?: '—' }}</td>
                                <td class="px-3 py-3 text-start text-gray-600">{{ $supplier->email ?: '—' }}</td>
                                <td class="px-3 py-3 text-start">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $supplier->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($supplier->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <a
                                            href="{{ route('user.suppliers.edit', $supplier) }}"
                                            wire:navigate
                                            aria-label="Edit {{ $supplier->name }}"
                                            title="Edit"
                                            class="table-action rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                        >
                                            <x-ui.icon name="pencil" class="w-4 h-4" />
                                        </a>
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $supplier->id }})"
                                            aria-label="Delete {{ $supplier->name }}"
                                            title="Delete"
                                            class="table-action rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                        >
                                            <x-ui.icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <ul class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-3 p-3 sm:p-4" role="list" aria-label="Supplier list">
                @foreach ($suppliers as $supplier)
                    <li wire:key="supplier-card-{{ $supplier->id }}" class="min-w-0 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-900 text-base leading-snug break-words">{{ $supplier->name }}</p>
                                <p class="text-sm text-gray-500 mt-1 break-words">{{ $supplier->contact_person ?: 'No contact person' }}</p>
                            </div>
                            <div class="-me-2 -mt-2 flex shrink-0 items-center gap-1">
                                <a
                                    href="{{ route('user.suppliers.edit', $supplier) }}"
                                    wire:navigate
                                    aria-label="Edit {{ $supplier->name }}"
                                    title="Edit"
                                    class="w-11 h-11 flex items-center justify-center rounded-xl text-gray-500 hover:text-brand-600 hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                                >
                                    <x-ui.icon name="pencil" class="w-5 h-5" />
                                </a>
                                <button
                                    type="button"
                                    wire:click="confirmDelete({{ $supplier->id }})"
                                    aria-label="Delete {{ $supplier->name }}"
                                    title="Delete"
                                    class="w-11 h-11 flex items-center justify-center rounded-xl text-gray-500 hover:text-red-600 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600"
                                >
                                    <x-ui.icon name="trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2 border-t border-gray-100 pt-3 text-sm">
                            @if ($supplier->phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $supplier->phone) }}" class="flex min-h-6 min-w-0 items-center gap-2 text-brand-700 hover:text-brand-800">
                                    <x-ui.icon name="phone" class="w-4 h-4 shrink-0" />
                                    <span class="break-words">{{ $supplier->phone }}</span>
                                </a>
                            @endif
                            @if ($supplier->email)
                                <a href="mailto:{{ $supplier->email }}" class="flex min-h-6 min-w-0 items-center gap-2 text-gray-600 hover:text-gray-900">
                                    <x-ui.icon name="mail" class="w-4 h-4 shrink-0" />
                                    <span class="break-all">{{ $supplier->email }}</span>
                                </a>
                            @endif
                            @if (! $supplier->phone && ! $supplier->email)
                                <p class="text-sm text-gray-400">No contact details available</p>
                            @endif
                        </div>

                        <div class="mt-3 border-t border-gray-100 pt-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $supplier->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($supplier->status) }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($suppliers->hasPages())
            {{ $suppliers->links('pagination.brand') }}
        @endif
    </x-ui.card>

    <x-ui.confirm-modal
        name="delete-supplier"
        title="Delete supplier?"
        confirm="Delete"
        wire:click="delete"
        target="delete"
    >
        @if ($deletingSupplier)
            <strong class="text-gray-800">{{ $deletingSupplier->name }}</strong> will be removed from your supplier list.
        @endif
    </x-ui.confirm-modal>
</main>
