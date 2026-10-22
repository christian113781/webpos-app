<main id="main" tabindex="-1" class="flex-1 px-4 sm:px-6 py-6 space-y-6 max-w-[1600px] mx-auto w-full">
    <x-ui.page-header title="Product Categories" description="Organize your products into categories.">
        <x-ui.button wire:click="createCategory" icon="plus">Add category</x-ui.button>
    </x-ui.page-header>

    <x-ui.card flush aria-labelledby="category-list-heading">
        <div class="p-4 sm:p-5 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 xl:flex xl:items-center gap-3">
            <h2 id="category-list-heading" class="sr-only">Category list</h2>

            <x-ui.input
                class="sm:col-span-2 xl:col-span-1 xl:flex-1 xl:max-w-sm"
                icon="search"
                type="search"
                placeholder="Search categories…"
                aria-label="Search categories"
                wire:model.live.debounce.300ms="search"
            />

            <x-ui.select class="xl:w-56" aria-label="Filter by status" wire:model.live="statusFilter">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </x-ui.select>
        </div>

        @if ($categories->isEmpty())
            <x-ui.empty-state
                icon="box"
                title="{{ $search || $statusFilter ? 'No categories found' : 'No categories yet' }}"
                description="{{ $search || $statusFilter ? 'Try changing your search or status filter.' : 'Create categories to organize the products in your inventory.' }}"
                class="py-10 sm:py-12"
            >
                @unless ($search || $statusFilter)
                    <x-ui.button wire:click="createCategory" icon="plus">Add category</x-ui.button>
                @endunless
            </x-ui.empty-state>
        @else
            <div class="hidden xl:block overflow-x-auto" role="region" aria-labelledby="category-list-heading" tabindex="0">
                <table class="w-full text-start text-sm" style="min-inline-size: 34rem">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-3 py-3 text-start font-semibold text-gray-500 text-xs uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-3 py-3 text-end font-semibold text-gray-500 text-xs uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($categories as $category)
                            <tr wire:key="category-row-{{ $category->id }}" class="hover:bg-gray-50/60 transition-colors">
                                <th scope="row" class="px-3 py-3 text-start font-medium text-gray-800">{{ $category->name }}</th>
                                <td class="px-3 py-3 text-start">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $category->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($category->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            wire:click="editCategory({{ $category->id }})"
                                            aria-label="Edit {{ $category->name }}"
                                            title="Edit"
                                            class="table-action rounded-lg text-gray-400 hover:text-brand-600 hover:bg-brand-50 transition-colors"
                                        >
                                            <x-ui.icon name="pencil" class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $category->id }})"
                                            aria-label="Delete {{ $category->name }}"
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

            <ul class="xl:hidden grid grid-cols-1 md:grid-cols-2 gap-3 p-3 sm:p-4" role="list" aria-label="Category list">
                @foreach ($categories as $category)
                    <li wire:key="category-card-{{ $category->id }}" class="min-w-0 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-3">
                            <p class="min-w-0 flex-1 font-semibold text-gray-900 text-base leading-snug break-words">{{ $category->name }}</p>
                            <div class="-me-2 -my-2 flex shrink-0 items-center gap-1">
                                <button
                                    type="button"
                                    wire:click="editCategory({{ $category->id }})"
                                    aria-label="Edit {{ $category->name }}"
                                    title="Edit"
                                    class="w-11 h-11 flex items-center justify-center rounded-xl text-gray-500 hover:text-brand-600 hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600"
                                >
                                    <x-ui.icon name="pencil" class="w-5 h-5" />
                                </button>
                                <button
                                    type="button"
                                    wire:click="confirmDelete({{ $category->id }})"
                                    aria-label="Delete {{ $category->name }}"
                                    title="Delete"
                                    class="w-11 h-11 flex items-center justify-center rounded-xl text-gray-500 hover:text-red-600 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600"
                                >
                                    <x-ui.icon name="trash" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                        <div class="mt-3 border-t border-gray-100 pt-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $category->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($category->status) }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($categories->hasPages())
            {{ $categories->links('pagination.brand') }}
        @endif
    </x-ui.card>

    <x-ui.modal
        name="category-form"
        mobile-full-width
        :aria-label="$categoryId ? 'Edit category' : 'Add category'"
    >
        <div class="space-y-4">
            <div>
                <h2 class="text-base font-bold text-gray-900">{{ $categoryId ? 'Edit category' : 'Add category' }}</h2>
                <p class="text-sm text-gray-500 mt-1">Enter the category details.</p>
            </div>
            <x-ui.input label="Category name" wire:model="name" placeholder="e.g. Beverages" required />
            <div class="flex justify-end gap-3 pt-2">
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', { name: 'category-form' })">Cancel</x-ui.button>
                <x-ui.button wire:click="save" target="save">{{ $categoryId ? 'Save changes' : 'Save' }}</x-ui.button>
            </div>
        </div>
    </x-ui.modal>

    <x-ui.confirm-modal
        name="delete-category"
        title="Delete category?"
        confirm="Delete"
        wire:click="delete"
        target="delete"
    >
        @if ($deletingCategory)
            <strong class="text-gray-800">{{ $deletingCategory->name }}</strong> will be removed from your categories.
        @endif
    </x-ui.confirm-modal>
</main>
