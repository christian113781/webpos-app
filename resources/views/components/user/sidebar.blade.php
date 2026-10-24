<aside id="sidebar"
         aria-label="Primary navigation"
         class="bg-white flex flex-col shadow-card flex-shrink-0 relative z-30"
         :class="{ collapsed: sidebarCollapsed, 'mobile-open': mobileOpen }">

    <div class="logo-wrap flex items-center gap-3 px-6 py-4 min-h-[77px] border-b border-gray-100 flex-shrink-0">
      <a href="{{ route('dashboard') }}" class="flex items-center min-w-0" aria-label="TalipapaPOS — go to dashboard">
        <img src="{{ asset('talipapapos/assets/img/logo.png') }}" alt="TalipapaPOS" class="logo-full h-7 w-auto max-w-full" width="150" height="28">
        <img src="{{ asset('talipapapos/assets/img/favicon.png') }}" alt="TalipapaPOS" class="logo-mark w-9 h-9" width="36" height="36">
      </a>
    </div>

    <nav class="flex-1 sidebar-scroll py-3 px-2" aria-label="Primary">
      <template x-for="section in navSections" :key="section.label">
        <div>
       
          <p x-show="!section.collapsible" class="nav-section-label px-3 mb-2 mt-4 first:mt-0 text-[10px] font-semibold text-gray-400 uppercase tracking-wider" x-text="section.label"></p>
          <ul class="space-y-0.5" role="list" x-show="!section.collapsible">
            <template x-for="item in (section.collapsible ? [] : section.items)" :key="item.name">
              <li>
                <a :href="item.href" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors"
                   :aria-current="item.active ? 'page' : null" :title="item.name">
                  <svg class="w-5 h-5 flex-shrink-0" :class="item.active ? 'text-brand-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/>
                  </svg>
                  <span class="nav-label" x-text="item.name"></span>
                </a>
              </li>
            </template>
          </ul>

          <!-- dropdown group: icon + label + chevron, then text-only links -->
          <div x-show="section.collapsible" style="display:none">
            <button type="button"
                    class="nav-link w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm hover:bg-brand-50 hover:text-brand-700 transition-colors"
                    :class="section.items.some(i => i.active) ? 'text-brand-700 font-semibold' : 'text-gray-700'"
                    @click="onGroupClick(section)" :aria-expanded="String(!!section.open)" :aria-controls="'nav-group-' + section.label.toLowerCase()" :title="section.label">
              <svg class="w-5 h-5 flex-shrink-0" :class="section.items.some(i => i.active) ? 'text-brand-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="section.icon"/>
              </svg>
              <span class="nav-label flex-1 text-start" x-text="section.label"></span>
              <svg class="nav-label w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200" :class="section.open ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
            </button>
            <ul class="nav-sub mt-0.5 space-y-0.5" role="list" :id="'nav-group-' + section.label.toLowerCase()" x-show="section.open" x-transition.opacity.duration.150ms>
              <template x-for="item in (section.collapsible ? section.items : [])" :key="item.name">
                <li>
                  <a :href="item.href" class="nav-link nav-sub-link flex items-center ps-9 lg:ps-11 pe-3 py-2 rounded-xl text-sm text-gray-600 hover:bg-brand-50 hover:text-brand-700 transition-colors"
                     :aria-current="item.active ? 'page' : null">
                    <span class="nav-label" x-text="item.name"></span>
                  </a>
                </li>
              </template>
            </ul>
          </div>
        </div>
      </template>
    </nav>

    <div class="border-t border-gray-100 px-3 py-4 flex-shrink-0">
      <div class="user-wrap flex items-center gap-3">
        <div class="user-avatar w-8 h-8 rounded-full bg-gradient-to-br from-brand-400 to-brand-700 flex items-center justify-center flex-shrink-0 text-white text-xs font-bold" aria-hidden="true">{{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->take(2)->implode('') }}</div>
        <div class="user-info min-w-0">
          <p class="text-sm font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</p>
          <p class="text-[11px] text-gray-400 truncate">{{ ucfirst(auth()->user()->system_role) }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="user-info ms-auto">
          @csrf
          <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors" aria-label="Sign out">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          </button>
        </form>
      </div>
    </div>
  </aside>