<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ config('app.name', 'TalipapaPOS') }} — Dashboard</title>
    <link rel="icon" type="image/png" href="{{ asset('talipapapos/assets/img/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        function userDashboard() {
            let sidebarCollapsed = false;

            try {
                sidebarCollapsed = localStorage.getItem('pp-sidebar-collapsed') === '1';
            } catch (error) {
                // Keep the sidebar expanded when browser storage is unavailable.
            }

            return {
                sidebarCollapsed,
                mobileOpen: false,
                notifOpen: false,
                profileOpen: false,
                notifications: [
                    {
                        id: 1,
                        title: 'Low stock alert',
                        message: 'Prawn Crackers 60g has only 8 packs left.',
                        time: '10 min ago',
                        read: false,
                        icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 1.732 1.732 3z',
                        iconBg: 'bg-amber-50',
                        iconColor: 'text-amber-500',
                    },
                    {
                        id: 2,
                        title: 'Expiring soon',
                        message: 'Fresh Milk 1L expires in 20 days.',
                        time: '1 hour ago',
                        read: false,
                        icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 1.732 1.732 3z',
                        iconBg: 'bg-amber-50',
                        iconColor: 'text-amber-500',
                    },
                    {
                        id: 3,
                        title: 'Delivery received',
                        message: 'Metro Grocery Wholesale delivered PO-0412.',
                        time: '3 hours ago',
                        read: false,
                        icon: 'M5 13l4 4L19 7',
                        iconBg: 'bg-brand-50',
                        iconColor: 'text-brand-600',
                    },
                    {
                        id: 4,
                        title: 'Out of stock',
                        message: 'Sardines in Tomato Sauce 155g is now out of stock.',
                        time: 'Yesterday',
                        read: true,
                        icon: 'M6 18L18 6M6 6l12 12',
                        iconBg: 'bg-red-50',
                        iconColor: 'text-red-500',
                    },
                ],
                navSections: [
                    {
                        label: 'Sales',
                        items: [{
                            name: 'Dashboard',
                            href: @js(route('dashboard')),
                            icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                            active: @js(request()->routeIs('dashboard')),
                        }],
                    },
                    {
                        label: 'Inventory',
                        collapsible: true,
                        open: @js(request()->routeIs('user.products', 'user.products.*', 'user.suppliers', 'user.suppliers.*', 'user.purchase-orders', 'user.purchase-orders.*', 'user.categories', 'user.inventory-movements', 'user.inventory-movements.*')),
                        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                        items: [
                            { name: 'Products', href: @js(route('user.products')), active: @js(request()->routeIs('user.products')) },
                            { name: 'Categories', href: @js(route('user.categories')), active: @js(request()->routeIs('user.categories')) },
                            { name: 'Inventory Movements', href: @js(route('user.inventory-movements')), active: @js(request()->routeIs('user.inventory-movements', 'user.inventory-movements.*')) },
                            { name: 'Expiry Tracker', href: '#', active: false },
                            { name: 'Suppliers', href: @js(route('user.suppliers')), active: @js(request()->routeIs('user.suppliers')) },
                            { name: 'Purchase Orders', href: @js(route('user.purchase-orders')), active: @js(request()->routeIs('user.purchase-orders', 'user.purchase-orders.*')) },
                        ],
                    },
                    {
                        label: 'Management',
                        collapsible: true,
                        open: @js(request()->routeIs('user.expenses')),
                        icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                        items: [
                            { name: 'Customers', href: '#', active: false },
                            { name: 'Returns & Refunds', href: '#', active: false },
                            { name: 'Expenses', href: @js(route('user.expenses')), active: @js(request()->routeIs('user.expenses')) },
                            { name: 'Reports', href: '#', active: false },
                        ],
                    },
                    {
                        label: 'Account',
                        items: [
                            { name: 'My Profile', href: '#', active: false, icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
                            { name: 'Settings', href: '#', active: false, icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 001.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
                        ],
                    },
                ],
                get unreadCount() {
                    return this.notifications.filter(notification => !notification.read).length;
                },
                toggleSidebar() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    try {
                        localStorage.setItem('pp-sidebar-collapsed', this.sidebarCollapsed ? '1' : '0');
                    } catch (error) {
                        // The current state still works for this page when storage is unavailable.
                    }
                },
                openMobileSidebar() {
                    this.mobileOpen = true;
                },
                closeMobileSidebar() {
                    this.mobileOpen = false;
                    this.$nextTick(() => {
                        const menuButton = document.getElementById('menu-btn');
                        if (menuButton) menuButton.focus();
                    });
                },
                onGroupClick(section) {
                    if (this.sidebarCollapsed && window.innerWidth >= 1024) {
                        this.toggleSidebar();
                    }
                    section.open = !section.open;
                },
                markRead(notification) {
                    notification.read = true;
                },
                markAllRead() {
                    this.notifications.forEach(notification => notification.read = true);
                },
            };
        }
    </script>
    @livewireStyles
</head>
<body class="font-poppins text-gray-800 antialiased" x-data="userDashboard()" x-cloak>
    <div id="sidebar-overlay"
         class="fixed inset-0 bg-gray-900/50 z-20 lg:hidden"
         aria-hidden="true"
         x-show="mobileOpen"
         x-transition.opacity
         @click="closeMobileSidebar()"
         @keydown.escape.window="closeMobileSidebar()"
         style="display: none"></div>

    <div class="flex min-h-[100dvh]">
        <x-user.sidebar />

        <div class="flex-1 flex flex-col min-w-0">
            <x-user.header />
            {{ $slot }}
        </div>
    </div>

    <x-ui.toast />

    @livewireScripts
</body>
</html>
