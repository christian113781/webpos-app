<main id="main" tabindex="-1" class="flex-1 px-4 sm:px-6 py-6 space-y-6 max-w-[1600px] mx-auto w-full">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">User dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5">Welcome, {{ auth()->user()->name }} · {{ now()->format('l, F j, Y') }}</p>
        </div>
    </div>

    <section class="bg-white rounded-2xl shadow-card p-5" aria-labelledby="dashboard-welcome">
        <h2 id="dashboard-welcome" class="text-base font-bold text-gray-900">Your dashboard is ready</h2>
        <p class="text-sm text-gray-500 mt-1">Use the navigation menu to access your workspace.</p>
    </section>
</main>
