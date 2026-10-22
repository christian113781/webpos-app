 @php
     $user = auth()->user();
     $userInitials = \Illuminate\Support\Str::of($user->name)
         ->explode(' ')
         ->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))
         ->take(2)
         ->implode('');
 @endphp
 <header class="sticky top-0 z-10 bg-white border-b border-gray-100 shadow-sm">
      <div class="flex items-center gap-3 px-4 sm:px-6 py-3.5">
        <button id="collapse-btn"
                class="hidden lg:flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-brand-600 transition-colors flex-shrink-0"
                :class="{ 'is-collapsed': sidebarCollapsed }"
                @click="toggleSidebar()"
                :aria-expanded="(!sidebarCollapsed).toString()" aria-controls="sidebar"
                :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v18"/>
          </svg>
        </button>
        <button id="menu-btn"
                class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
                @click="mobileOpen ? closeMobileSidebar() : openMobileSidebar()"
                :aria-expanded="mobileOpen.toString()" aria-controls="sidebar"
                :aria-label="mobileOpen ? 'Close navigation menu' : 'Open navigation menu'">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <div class="flex-1 max-w-md">
          <div class="relative">
            <svg class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input id="global-search" type="search" placeholder="Search products, customers…"
                   class="w-full ps-9 pe-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg placeholder:text-gray-400 focus:bg-white focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none transition-colors min-h-[2.75rem]">
          </div>
        </div>
        <div class="flex items-center gap-2 ms-auto">
                    <div class="relative">
            <button @click="notifOpen = !notifOpen"
                    class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
                    :aria-label="'Notifications' + (unreadCount ? ' (' + unreadCount + ' unread)' : '')"
                    aria-haspopup="true" :aria-expanded="notifOpen.toString()" aria-controls="notif-panel">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
              <span class="absolute top-1.5 end-1.5 w-2 h-2 rounded-full bg-red-500" aria-hidden="true" x-show="unreadCount > 0"></span>
            </button>

            <div id="notif-panel" role="menu" aria-label="Notifications"
                 x-show="notifOpen" x-transition
                 @click.outside="notifOpen = false" @keydown.escape="notifOpen = false"
                 class="fixed inset-x-3 top-[4.5rem] sm:absolute sm:inset-x-auto sm:end-0 sm:top-full sm:mt-2 sm:w-96 bg-white rounded-2xl shadow-card border border-gray-100 z-40 overflow-hidden"
                 style="display:none">
              <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Notifications</h2>
                <button @click="markAllRead()" x-show="unreadCount > 0"
                        class="text-xs font-semibold text-brand-600 hover:text-brand-700">Mark all as read</button>
              </div>
              <ul class="max-h-[min(20rem,calc(100dvh-13rem))] overflow-y-auto overscroll-contain divide-y divide-gray-50" role="list">
                <template x-for="n in notifications" :key="n.id">
                  <li>
                    <button @click="markRead(n)" class="w-full flex items-start gap-3 px-4 py-3 text-start hover:bg-gray-50/60 transition-colors" :class="{ 'bg-brand-50/40': !n.read }">
                      <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" :class="n.iconBg" aria-hidden="true">
                        <svg class="w-4 h-4" :class="n.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="n.icon"/></svg>
                      </div>
                      <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 truncate" x-text="n.title"></p>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="n.message"></p>
                        <p class="text-[11px] text-gray-400 mt-1" x-text="n.time"></p>
                      </div>
                      <span class="w-2 h-2 rounded-full bg-brand-500 flex-shrink-0 mt-1.5" aria-hidden="true" x-show="!n.read"></span>
                    </button>
                  </li>
                </template>
                <li x-show="notifications.length === 0">
                  <p class="px-4 py-8 text-center text-sm text-gray-400">You're all caught up.</p>
                </li>
              </ul>
              <div class="px-4 py-3 border-t border-gray-100 text-center">
                <a href="#" @click.prevent="notifOpen = false" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all notifications</a>
              </div>
            </div>
          </div>
          <div class="relative">
            <button @click="profileOpen = !profileOpen"
                    class="flex items-center gap-2 p-1 pe-2 rounded-full hover:bg-gray-100 transition-colors"
                    aria-haspopup="true" :aria-expanded="profileOpen.toString()" aria-controls="profile-panel"
                    aria-label="Account menu for {{ $user->name }}">
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-400 to-brand-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0" aria-hidden="true">{{ $userInitials }}</div>
              <svg class="w-3.5 h-3.5 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div id="profile-panel" role="menu" aria-label="Account menu"
                 x-show="profileOpen" x-transition
                 @click.outside="profileOpen = false" @keydown.escape="profileOpen = false"
                 class="absolute end-0 top-full mt-2 w-64 bg-white rounded-2xl shadow-card border border-gray-100 z-40 overflow-hidden"
                 style="display:none">
              <div class="flex items-center gap-3 px-4 py-3.5 border-b border-gray-100">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-400 to-brand-700 flex items-center justify-center text-white text-sm font-bold flex-shrink-0" aria-hidden="true">{{ $userInitials }}</div>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-900 truncate">{{ $user->name }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                </div>
              </div>
              <ul class="py-1.5" role="list">
                <li>
                  <a href="{{ route('dashboard') }}" role="menuitem" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    My Profile
                  </a>
                </li>
                <li>
                  <a href="{{ route('dashboard') }}" role="menuitem" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                    Account Settings
                  </a>
                </li>
                <li>
                  <a href="#" role="menuitem" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Help &amp; Support
                  </a>
                </li>
              </ul>
              <div class="border-t border-gray-100 py-1.5">
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button role="menuitem" type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sign Out
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>