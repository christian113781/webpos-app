@php
    $loginUrl     = Route::has('login') ? route('login') : url('/login');
    $registerUrl  = Route::has('register') ? route('register') : url('/register');
    $dashboardRoute = auth()->check() ? auth()->user()->dashboardRoute() : 'dashboard';
    $dashboardUrl = Route::has($dashboardRoute) ? route($dashboardRoute) : url('/dashboard');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>TalipapaPOS — Point of Sale for Small Businesses in the Philippines</title>
  <meta name="description" content="Sell faster, track stock, manage suppliers and customers, and see your profit — one simple POS for sari-sari stores, cafés, pharmacies, groceries and every small shop.">
  <link rel="icon" type="image/png" href="{{ Vite::asset('resources/img/favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
  <script>document.documentElement.classList.add('js')</script>
</head>
<body class="font-poppins text-gray-800 antialiased bg-white"
      x-data="{ menu: false, sticky: false }"
      @keydown.escape.window="menu = false"
      @resize.window.debounce.100ms="if (innerWidth >= 768) menu = false"
      @scroll.window.passive="sticky = scrollY > 640">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[9999] focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:font-semibold focus:text-white">Skip to content</a>

<!-- ───────── NAV ───────── -->
<header class="sticky top-0 z-40 bg-white/85 backdrop-blur border-b border-brand-100">
  <nav class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between" aria-label="Main">
    <a href="{{ url('/') }}" aria-label="TalipapaPOS home"><img src="{{ Vite::asset('resources/img/logo.png') }}" alt="TalipapaPOS" class="h-9 w-auto" width="193" height="36"></a>
    <ul class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
      <li><a class="hover:text-brand-600" href="#features">Features</a></li>
      <li><a class="hover:text-brand-600" href="#how">How it works</a></li>
      <li><a class="hover:text-brand-600" href="#pricing">Pricing</a></li>
      <li><a class="hover:text-brand-600" href="#faq">FAQ</a></li>
    </ul>
    <div class="hidden md:flex items-center gap-3">
      @auth
        <a href="{{ $dashboardUrl }}" class="text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-4 py-2.5 shadow-card">Open dashboard</a>
      @else
        <a href="{{ $loginUrl }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900 px-3 py-2">Sign in</a>
        <a href="{{ $registerUrl }}" class="text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 rounded-xl px-4 py-2.5 shadow-card">Start free trial</a>
      @endauth
    </div>
    <button class="md:hidden p-2.5 -me-2.5 text-gray-700 rounded-lg hover:bg-brand-50 min-w-[44px] min-h-[44px] flex items-center justify-center" @click="menu = !menu" :aria-expanded="String(menu)" aria-controls="mobile-menu" aria-label="Toggle menu">
      <svg x-show="!menu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/></svg>
      <svg x-show="menu" style="display:none" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
  </nav>
  <div id="mobile-menu" x-show="menu" x-transition.opacity.duration.150ms style="display:none" @click.outside="menu = false"
       class="md:hidden absolute inset-x-0 top-full border-t border-brand-100 bg-white px-4 py-4 space-y-1 text-base font-medium shadow-[0_20px_40px_rgba(11,46,34,.15)] max-h-[calc(100dvh-4rem)] overflow-y-auto">
    <a @click="menu=false" class="block px-3 py-3 rounded-lg hover:bg-brand-50 active:bg-brand-100" href="#features">Features</a>
    <a @click="menu=false" class="block px-3 py-3 rounded-lg hover:bg-brand-50 active:bg-brand-100" href="#how">How it works</a>
    <a @click="menu=false" class="block px-3 py-3 rounded-lg hover:bg-brand-50 active:bg-brand-100" href="#pricing">Pricing</a>
    <a @click="menu=false" class="block px-3 py-3 rounded-lg hover:bg-brand-50 active:bg-brand-100" href="#faq">FAQ</a>
    <div class="flex gap-3 pt-3">
      @auth
        <a href="{{ $dashboardUrl }}" class="flex-1 text-center py-3 rounded-xl bg-brand-600 text-white font-semibold">Open dashboard</a>
      @else
        <a href="{{ $loginUrl }}" class="flex-1 text-center py-3 rounded-xl border border-brand-200 text-brand-700 font-semibold">Sign in</a>
        <a href="{{ $registerUrl }}" class="flex-1 text-center py-3 rounded-xl bg-brand-600 text-white font-semibold">Start free</a>
      @endauth
    </div>
  </div>
</header>

<main id="main">

<!-- ───────── HERO ───────── -->
<section class="hero-bg relative overflow-hidden">
  <div class="blob w-72 h-72 bg-brand-200/70 -top-10 -left-16" aria-hidden="true"></div>
  <div class="blob w-80 h-80 bg-brand-400/25 top-40 right-0" aria-hidden="true"></div>
  <div class="dot-grid absolute -right-10 top-6 w-[34rem] h-[34rem] hidden lg:block" aria-hidden="true"></div>
  <div class="relative max-w-6xl mx-auto px-4 sm:px-6 pt-10 pb-24 sm:pt-14 lg:pt-20 lg:pb-28 grid lg:grid-cols-2 gap-12 items-center">
    <div>
      <span class="badge bg-brand-100 text-brand-700">POS for every small business in the Philippines</span>
      <h1 class="mt-5 text-[2rem] sm:text-5xl font-extrabold leading-[1.15] text-gray-900 tracking-tight">
        Run your tindahan without <span class="grad-text">notebooks or Excel.</span>
      </h1>
      <p class="mt-5 text-lg text-gray-600 leading-relaxed max-w-xl">
        Sell faster, keep track of stock, manage suppliers and customers, and see your profit — all in one simple point of sale.
      </p>
      <div class="mt-8 flex flex-col sm:flex-row gap-3">
        <a href="{{ $registerUrl }}" class="inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-xl px-6 py-3.5 shadow-card min-h-[48px]">Start free trial
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5-5 5M6 12h12"/></svg></a>
        <a href="#how" class="inline-flex items-center justify-center gap-2 bg-white border border-brand-200 text-brand-700 hover:bg-brand-50 font-semibold rounded-xl px-6 py-3.5 min-h-[48px]">See how it works</a>
      </div>
      <p class="mt-4 text-sm text-gray-500">No credit card required · Works on desktop, tablet and phone</p>
    </div>

    <!-- app mockup (decorative) -->
    <div class="relative lg:pl-4" aria-hidden="true">
      <span class="chip-float -top-4 end-4 hidden sm:inline-flex"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.1 19h13.8c1.5 0 2.4-1.6 1.7-2.9L13.7 4.1a2 2 0 00-3.4 0L3.4 16.1C2.7 17.4 3.6 19 5.1 19z"/></svg>Low stock: Iced Tea</span>
      <span class="chip-float top-1/2 -end-3 sm:-end-6 hidden sm:inline-flex" style="animation-delay:-2s"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Receipt sent</span>
      <div class="bg-white rounded-2xl shadow-[0_20px_60px_rgba(27,154,98,.18)] border border-brand-100 overflow-hidden">
        <div class="flex items-center gap-1.5 px-4 py-3 bg-brand-50/60 border-b border-brand-100">
          <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span><span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
          <span class="ms-3 text-xs text-gray-400 bg-white rounded-md px-3 py-1 border border-brand-100">talipapapos / Dashboard</span>
        </div>
        <div class="p-5">
          <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-brand-100 p-3"><p class="text-[10px] font-semibold tracking-wide text-gray-400 uppercase">Today's sales</p><p class="text-lg font-bold mt-1">₱8,420</p><p class="text-[11px] text-brand-600 mt-0.5">▲ 12% vs yesterday</p></div>
            <div class="rounded-xl border border-brand-100 p-3"><p class="text-[10px] font-semibold tracking-wide text-gray-400 uppercase">Low stock</p><p class="text-lg font-bold mt-1">3</p><p class="text-[11px] text-amber-600 mt-0.5">Needs reorder</p></div>
            <div class="rounded-xl border border-brand-100 p-3"><p class="text-[10px] font-semibold tracking-wide text-gray-400 uppercase">Profit</p><p class="text-lg font-bold mt-1">₱2,150</p><p class="text-[11px] text-brand-600 mt-0.5">This week</p></div>
          </div>
          <div class="mt-4 rounded-xl border border-brand-100 p-4">
            <p class="text-xs font-semibold text-gray-700">Sales — last 7 days</p>
            <div class="mt-3 h-28 flex items-end gap-2">
              <span class="flex-1 rounded-t bg-brand-200" style="height:45%"></span><span class="flex-1 rounded-t bg-brand-200" style="height:62%"></span>
              <span class="flex-1 rounded-t bg-brand-200" style="height:38%"></span><span class="flex-1 rounded-t bg-brand-200" style="height:80%"></span>
              <span class="flex-1 rounded-t bg-brand-200" style="height:55%"></span><span class="flex-1 rounded-t bg-brand-200" style="height:72%"></span>
              <span class="flex-1 rounded-t bg-gradient-to-t from-brand-700 to-brand-400" style="height:96%"></span>
            </div>
          </div>
        </div>
      </div>
      <div class="float-card absolute -bottom-8 -start-2 sm:-start-8 w-64 bg-white rounded-2xl shadow-[0_16px_40px_rgba(27,154,98,.22)] border border-brand-100 p-4 text-xs">
        <div class="flex justify-between font-semibold text-sm"><span>Current Sale</span><span class="text-gray-400">(3)</span></div>
        <div class="flex justify-between mt-2"><span>Iced Coffee × 2</span><span class="font-semibold">₱180.00</span></div>
        <div class="flex justify-between mt-1"><span>Ensaymada × 1</span><span class="font-semibold">₱45.00</span></div>
        <div class="mt-2 rounded-lg bg-brand-50 border border-brand-200 px-2.5 py-2 flex justify-between"><span class="font-semibold">Paid via GCash</span><span class="text-brand-700">✓</span></div>
        <div class="mt-2 pt-2 border-t border-dashed border-gray-300 flex justify-between text-sm font-bold"><span>Total</span><span>₱225.00</span></div>
      </div>
    </div>
  </div>
</section>


<!-- ───────── BUILT FOR ───────── -->
<section class="py-8 bg-white border-y border-brand-100" aria-label="Businesses that use TalipapaPOS">
  <p class="text-center text-xs font-semibold tracking-wider uppercase text-gray-400 mb-4">Built for every kind of small shop</p>
  <div class="marquee"><div class="marquee-track"><span class="biz-chip">🛒 Sari-sari stores</span><span class="biz-chip">☕ Cafés</span><span class="biz-chip">🥖 Bakeries</span><span class="biz-chip">💊 Pharmacies</span><span class="biz-chip">🛍️ Boutiques</span><span class="biz-chip">🍲 Carinderias</span><span class="biz-chip">🔧 Hardware</span><span class="biz-chip">🥬 Groceries</span><span class="biz-chip">📱 Gadget shops</span><span class="biz-chip">🧁 Milk tea shops</span><span class="biz-chip">🐾 Pet shops</span><span class="biz-chip">📚 School supplies</span><span class="contents" aria-hidden="true"><span class="biz-chip">🛒 Sari-sari stores</span><span class="biz-chip">☕ Cafés</span><span class="biz-chip">🥖 Bakeries</span><span class="biz-chip">💊 Pharmacies</span><span class="biz-chip">🛍️ Boutiques</span><span class="biz-chip">🍲 Carinderias</span><span class="biz-chip">🔧 Hardware</span><span class="biz-chip">🥬 Groceries</span><span class="biz-chip">📱 Gadget shops</span><span class="biz-chip">🧁 Milk tea shops</span><span class="biz-chip">🐾 Pet shops</span><span class="biz-chip">📚 School supplies</span></span></div></div>
</section>

<!-- ───────── FEATURES ───────── -->
<section id="features" class="py-20">
  <div class="max-w-6xl mx-auto px-4 sm:px-6">
    <div class="max-w-2xl mx-auto text-center">
      <p class="text-sm font-semibold text-brand-600 uppercase tracking-wider">Features</p>
      <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">Everything a small shop needs, in one system</h2>
      <p class="mt-3 text-gray-600">From the counter to the stockroom to the end-of-day report — for sari-sari stores, cafés, groceries, pharmacies, boutiques and more.</p>
    </div>
    <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <article class="feature-card reveal bg-white rounded-2xl shadow-card border border-brand-100 p-6">
        <span class="settings-ico"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3a1 1 0 00.7 1.7H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>
        <h3 class="mt-4 font-semibold text-gray-900">Fast point of sale</h3>
        <p class="mt-1.5 text-sm text-gray-600 leading-relaxed">Search or scan items, take cash, GCash or card, get instant change and print or share receipts.</p>
      </article>
      <article class="feature-card reveal bg-white rounded-2xl shadow-card border border-brand-100 p-6">
        <span class="settings-ico"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
        <h3 class="mt-4 font-semibold text-gray-900">Products &amp; inventory</h3>
        <p class="mt-1.5 text-sm text-gray-600 leading-relaxed">Keep your catalogue, SKUs, costs, prices and reorder levels in one place. Low-stock alerts included.</p>
      </article>
      <article class="feature-card reveal bg-white rounded-2xl shadow-card border border-brand-100 p-6">
        <span class="settings-ico"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
        <h3 class="mt-4 font-semibold text-gray-900">Expiry tracking</h3>
        <p class="mt-1.5 text-sm text-gray-600 leading-relaxed">Selling food, medicine or anything that expires? See batches by 30 / 60 / 90 days and log disposals.</p>
      </article>
      <article class="feature-card reveal bg-white rounded-2xl shadow-card border border-brand-100 p-6">
        <span class="settings-ico"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
        <h3 class="mt-4 font-semibold text-gray-900">Suppliers &amp; purchase orders</h3>
        <p class="mt-1.5 text-sm text-gray-600 leading-relaxed">Draft, order and receive stock, and keep a clean directory of everyone you buy from.</p>
      </article>
      <article class="feature-card reveal bg-white rounded-2xl shadow-card border border-brand-100 p-6">
        <span class="settings-ico"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.4-1.8M17 20H7m10 0v-2c0-.7-.1-1.3-.4-1.8M7 20H2v-2a3 3 0 015.4-1.8M7 20v-2c0-.7.1-1.3.4-1.8m0 0a5 5 0 019.2 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
        <h3 class="mt-4 font-semibold text-gray-900">Customers &amp; discounts</h3>
        <p class="mt-1.5 text-sm text-gray-600 leading-relaxed">Save regulars, apply Senior Citizen / PWD discounts with ID, and review each customer's purchase history.</p>
      </article>
      <article class="feature-card reveal bg-white rounded-2xl shadow-card border border-brand-100 p-6">
        <span class="settings-ico"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
        <h3 class="mt-4 font-semibold text-gray-900">Expenses, returns &amp; reports</h3>
        <p class="mt-1.5 text-sm text-gray-600 leading-relaxed">Record expenses and refunds, then see sales, top items and real profit — export to CSV anytime.</p>
      </article>
    </div>
  </div>
</section>

<!-- ───────── HOW IT WORKS ───────── -->
<section id="how" class="py-20 bg-brand-50/60 border-y border-brand-100">
  <div class="max-w-6xl mx-auto px-4 sm:px-6">
    <div class="max-w-2xl mx-auto text-center">
      <p class="text-sm font-semibold text-brand-600 uppercase tracking-wider">How it works</p>
      <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">Up and running in three steps</h2>
    </div>
    <ol class="relative mt-12 grid md:grid-cols-3 gap-6">
      <span class="step-line-h hidden md:block" aria-hidden="true"></span>
      <li class="reveal relative bg-white rounded-2xl shadow-card p-6 pt-8 text-center">
        <span class="step-num mx-auto">1</span>
        <h3 class="mt-5 font-semibold text-gray-900">Add your products</h3>
        <p class="mt-1.5 text-sm text-gray-600">Enter your items with prices and stock levels, and add the suppliers you buy from.</p>
      </li>
      <li class="reveal relative bg-white rounded-2xl shadow-card p-6 pt-8 text-center">
        <span class="step-num mx-auto">2</span>
        <h3 class="mt-5 font-semibold text-gray-900">Sell at the counter</h3>
        <p class="mt-1.5 text-sm text-gray-600">Scan or search, pick a payment method and let TalipapaPOS handle VAT, discounts and change.</p>
      </li>
      <li class="reveal relative bg-white rounded-2xl shadow-card p-6 pt-8 text-center">
        <span class="step-num mx-auto">3</span>
        <h3 class="mt-5 font-semibold text-gray-900">Know your numbers</h3>
        <p class="mt-1.5 text-sm text-gray-600">Get low-stock alerts, reorder from suppliers and check sales and profit any time.</p>
      </li>
    </ol>
  </div>
</section>

<!-- ───────── PRICING ───────── -->
<section id="pricing" class="pricing-bg py-20" x-data="{ yearly: false, price: { basic: 599, standard: 999, premium: 1599 },
    show(p){ return (this.yearly ? p * 10 : p).toLocaleString('en-PH'); } }">
  <div class="max-w-6xl mx-auto px-4 sm:px-6">
    <div class="max-w-2xl mx-auto text-center">
      <p class="text-sm font-semibold text-brand-600 uppercase tracking-wider">Pricing</p>
      <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">Simple plans that grow with your shop</h2>
      <p class="mt-3 text-gray-600">Start with a free trial. Upgrade or cancel anytime.</p>
    </div>

    <div class="mt-8 flex justify-center">
      <div class="inline-flex items-center gap-1 p-1 rounded-full bg-brand-50 border border-brand-100" role="group" aria-label="Billing period">
        <button type="button" @click="yearly = false" :aria-pressed="String(!yearly)" class="px-4 py-2 rounded-full text-sm font-semibold min-h-[40px] transition" :class="!yearly ? 'bg-white text-gray-900 shadow-card' : 'text-gray-500 hover:text-gray-800'">Monthly</button>
        <button type="button" @click="yearly = true" :aria-pressed="String(yearly)" class="px-4 py-2 rounded-full text-sm font-semibold min-h-[40px] transition flex items-center gap-2" :class="yearly ? 'bg-white text-gray-900 shadow-card' : 'text-gray-500 hover:text-gray-800'">Annually <span class="badge bg-brand-100 text-brand-700 !py-0.5">2 months free</span></button>
      </div>
    </div>

    <div class="mt-14 grid md:grid-cols-3 gap-6 items-stretch">
      <!-- Basic -->
      <article class="reveal bg-white rounded-2xl border border-brand-100 shadow-card p-6 flex flex-col">
        <h3 class="font-bold text-gray-900">Basic</h3>
        <p class="mt-1 text-xs text-gray-500">For solo owners just getting started.</p>
        <p class="mt-5 flex items-baseline gap-1"><span class="text-4xl font-extrabold text-gray-900">₱<span x-text="show(price.basic)"></span></span><span class="text-xs text-gray-400" x-text="yearly ? '/year' : '/month'"></span></p>
        <ul class="mt-6 space-y-2.5 flex-1">
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Point of sale &amp; receipts</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Up to 50 products</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Up to 2 staff accounts</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Dashboard</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Inventory module</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Barcode scanner</li>
          <li class="plan-li text-gray-400"><svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.7 7.3a1 1 0 00-1.4 1.4L8.6 10l-1.3 1.3a1 1 0 101.4 1.4L10 11.4l1.3 1.3a1 1 0 001.4-1.4L11.4 10l1.3-1.3a1 1 0 00-1.4-1.4L10 8.6 8.7 7.3z" clip-rule="evenodd"/></svg>Expenses &amp; reports</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Email support</li>
        </ul>
        <a href="{{ $registerUrl }}?plan=basic" class="mt-8 block text-center rounded-xl border border-gray-900 text-gray-900 hover:bg-gray-900 hover:text-white font-semibold py-3 min-h-[48px] transition">Get started</a>
      </article>

      <!-- Standard (featured) -->
      <article class="reveal order-first md:order-none relative bg-brand-900 text-white rounded-2xl shadow-[0_20px_50px_rgba(11,46,34,.35)] p-6 flex flex-col md:-my-4 md:py-10">
        <span class="absolute -top-3 left-1/2 -translate-x-1/2 badge bg-brand-400 text-brand-900 shadow-card">★ Most popular</span>
        <h3 class="font-bold">Standard</h3>
        <p class="mt-1 text-xs text-brand-200">For growing shops with a small team.</p>
        <p class="mt-5 flex items-baseline gap-1"><span class="text-4xl font-extrabold">₱<span x-text="show(price.standard)"></span></span><span class="text-xs text-brand-200" x-text="yearly ? '/year' : '/month'"></span></p>
        <ul class="mt-6 space-y-2.5 flex-1">
          <li class="plan-li"><svg class="text-brand-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Everything in Basic</li>
          <li class="plan-li"><svg class="text-brand-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Up to 500 products</li>
          <li class="plan-li"><svg class="text-brand-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Up to 5 staff accounts</li>
          <li class="plan-li"><svg class="text-brand-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Dashboard &amp; reports</li>
          <li class="plan-li"><svg class="text-brand-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Expenses module <span class="plan-new">New</span></li>
          <li class="plan-li"><svg class="text-brand-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Suppliers &amp; purchase orders</li>
          <li class="plan-li"><svg class="text-brand-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Returns &amp; refunds</li>
          <li class="plan-li"><svg class="text-brand-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Priority support</li>
        </ul>
        <a href="{{ $registerUrl }}?plan=standard" class="mt-8 block text-center rounded-xl bg-brand-500 hover:bg-brand-400 text-white font-semibold py-3 min-h-[48px] shadow-card transition">Get started</a>
      </article>

      <!-- Premium -->
      <article class="reveal bg-white rounded-2xl border border-brand-100 shadow-card p-6 flex flex-col">
        <h3 class="font-bold text-gray-900">Premium</h3>
        <p class="mt-1 text-xs text-gray-500">For multi-branch or high-volume stores.</p>
        <p class="mt-5 flex items-baseline gap-1"><span class="text-4xl font-extrabold text-gray-900">₱<span x-text="show(price.premium)"></span></span><span class="text-xs text-gray-400" x-text="yearly ? '/year' : '/month'"></span></p>
        <ul class="mt-6 space-y-2.5 flex-1">
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Everything in Standard</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Unlimited products</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Unlimited staff accounts</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Multi-branch management</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Expiry tracker</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Bulk product upload (unlimited)</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Custom reports on request</li>
          <li class="plan-li"><svg class="text-brand-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>Dedicated support</li>
        </ul>
        <a href="{{ $registerUrl }}?plan=premium" class="mt-8 block text-center rounded-xl border border-gray-900 text-gray-900 hover:bg-gray-900 hover:text-white font-semibold py-3 min-h-[48px] transition">Get started</a>
      </article>
    </div>
    <p class="mt-8 text-center text-xs text-gray-400">Prices in Philippine pesos. Sample pricing — adjust to your own plans.</p>
  </div>
</section>

<!-- ───────── FAQ ───────── -->
<section id="faq" class="py-20 bg-brand-50/60 border-y border-brand-100">
  <div class="max-w-3xl mx-auto px-4 sm:px-6">
    <h2 class="text-3xl font-bold text-gray-900 text-center">Frequently asked questions</h2>
    <div class="mt-10 space-y-3" x-data="{ open: 0 }">
      <template x-for="(f, i) in [
        { q: 'What kinds of businesses can use TalipapaPOS?', a: 'Any small shop — sari-sari stores, cafés and carinderias, groceries, pharmacies, boutiques, hardware and more. If you sell items at a counter, it fits.' },
        { q: 'Do I need to install anything?', a: 'No. TalipapaPOS runs in your browser — open it on a computer, tablet or phone.' },
        { q: 'Does it handle VAT and Senior Citizen / PWD discounts?', a: 'Yes. Prices include 12% VAT, and Senior/PWD sales are VAT-exempt with a 20% discount applied automatically when you add the customer ID.' },
        { q: 'Can I track items that expire?', a: 'Yes. The Expiry Tracker groups batches into 30, 60 and 90-day buckets and lets you log disposals and returns.' },
        { q: 'Where is my data stored?', a: 'Your data is saved securely in your TalipapaPOS account, so you can open it from any device and export reports to CSV anytime.' },
        { q: 'Can I try it first?', a: 'Absolutely — start a free trial, no credit card required, and add your own products, suppliers and customers.' }
      ]" :key="i">
        <div class="bg-white rounded-2xl border border-brand-100 shadow-card">
          <h3>
            <button type="button" class="w-full flex items-center justify-between gap-4 text-start px-5 py-4 font-semibold min-h-[48px]" @click="open = open === i ? -1 : i" :aria-expanded="String(open === i)" :aria-controls="'faq-' + i">
              <span x-text="f.q"></span>
              <svg class="w-5 h-5 text-brand-600 transition-transform flex-shrink-0" :class="open === i && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
          </h3>
          <div :id="'faq-' + i" x-show="open === i" x-transition x-cloak class="px-5 pb-4 text-sm text-gray-600 leading-relaxed" x-text="f.a"></div>
        </div>
      </template>
    </div>
  </div>
</section>

<!-- ───────── CTA ───────── -->
<section class="py-20">
  <div class="max-w-5xl mx-auto px-4 sm:px-6">
    <div class="reveal cta-bg relative overflow-hidden rounded-3xl text-white text-center px-6 py-16 shadow-card">
      <div class="cta-dots absolute inset-0" aria-hidden="true"></div>
      <div class="absolute -top-16 -left-16 w-52 h-52 rounded-full border border-white/10" aria-hidden="true"></div>
      <div class="absolute -bottom-20 -right-10 w-64 h-64 rounded-full border border-white/10" aria-hidden="true"></div>
      <div class="relative"><h2 class="text-3xl sm:text-4xl font-bold">Ready to put your shop on autopilot?</h2>
      <p class="mt-3 text-brand-100 max-w-xl mx-auto">Start your free trial today — no credit card required.</p>
      <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ $registerUrl }}" class="inline-flex justify-center bg-white text-brand-700 hover:bg-brand-50 font-semibold rounded-xl px-6 py-3.5 min-h-[48px]">Start free trial</a>
        <a href="{{ $loginUrl }}" class="inline-flex justify-center border border-white/40 hover:bg-white/10 font-semibold rounded-xl px-6 py-3.5 min-h-[48px]">Sign in</a>
      </div></div>
    </div>
  </div>
</section>
</main>

<!-- ───────── FOOTER ───────── -->
<footer class="bg-slate-50 border-t border-brand-100 pb-20 md:pb-0" x-data="{ email: '', done: false, bad: false,
    subscribe() { this.bad = !/^\S+@\S+\.\S+$/.test(this.email); if (!this.bad) { this.done = true; this.email = ''; } } }">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-14 pb-8">
    <div class="grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-[1.4fr_1fr_1fr_1.4fr] lg:gap-x-10">
      <div class="col-span-2 lg:col-span-1">
        <a href="{{ url('/') }}" aria-label="TalipapaPOS home"><img src="{{ Vite::asset('resources/img/logo.png') }}" alt="TalipapaPOS" class="h-9 w-auto" width="193" height="36" loading="lazy"></a>
        <p class="mt-4 text-[13px] leading-relaxed text-slate-500 max-w-xs">TalipapaPOS is point-of-sale software for Philippine small businesses — sales, stock, customers, suppliers and expenses in one workspace.</p>
        <div class="mt-5 flex gap-3">
          <a href="#" class="social-btn" aria-label="TalipapaPOS on Facebook"><svg class="w-[18px] h-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.5-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H7.8v3h2.6V21h3.1z"/></svg></a>
          <a href="#" class="social-btn" aria-label="TalipapaPOS on LinkedIn"><svg class="w-[18px] h-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.9 8.9H3.7V20h3.2V8.9zM5.3 4A1.9 1.9 0 105.3 7.8 1.9 1.9 0 005.3 4zM20.3 13.4c0-3-1.6-4.7-3.9-4.7-1.4 0-2.3.7-2.7 1.4V8.9h-3.1V20h3.2v-6c0-1.6.6-2.6 1.9-2.6 1.2 0 1.8.9 1.8 2.6V20h3.2v-6.6z"/></svg></a>
          <a href="mailto:hello@talipapapos.com" class="social-btn" aria-label="Email us"><svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></a>
        </div>
      </div>

      <nav aria-label="Product">
        <h2 class="text-[11px] font-bold tracking-wider uppercase text-slate-900">Product</h2>
        <ul class="mt-4 space-y-3">
          <li><a class="footer-link" href="{{ $loginUrl }}">Sign in</a></li>
          <li><a class="footer-link" href="#features">Features</a></li>
          <li><a class="footer-link" href="#pricing">Pricing</a></li>
          <li><a class="footer-link" href="#how">How it works</a></li>
        </ul>
      </nav>

      <nav aria-label="Legal and support">
        <h2 class="text-[11px] font-bold tracking-wider uppercase text-slate-900">Legal &amp; Support</h2>
        <ul class="mt-4 space-y-3">
          <li><a class="footer-link" href="#">About Us</a></li>
          <li><a class="footer-link" href="#">Contact</a></li>
          <li><a class="footer-link" href="#">Documentation</a></li>
          <li><a class="footer-link" href="#">Security</a></li>
          <li><a class="footer-link" href="#">Privacy Policy</a></li>
          <li><a class="footer-link" href="#">Terms of Service</a></li>
          <li><a class="footer-link" href="#">Cookie Policy</a></li>
        </ul>
      </nav>

      <div class="col-span-2 lg:col-span-1 rounded-2xl bg-white border border-brand-100 p-5 lg:bg-transparent lg:border-0 lg:p-0">
        <h2 class="text-[11px] font-bold tracking-wider uppercase text-slate-900">Stay updated</h2>
        <p class="mt-4 text-[13px] leading-relaxed text-slate-500">Get product updates and tips for running your shop in your inbox.</p>
        <form class="mt-4 flex flex-col min-[420px]:flex-row gap-2" @submit.prevent="subscribe()" novalidate>
          <label for="news-email" class="sr-only">Email address</label>
          <input id="news-email" type="email" x-model="email" autocomplete="email" placeholder="you@email.com" :aria-invalid="String(bad)" aria-describedby="news-msg"
                 class="min-w-0 flex-1 px-3.5 py-2.5 rounded-xl border bg-white text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 min-h-[44px]" :class="bad ? 'border-red-400' : 'border-gray-200'">
          <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold min-h-[44px]">Subscribe</button>
        </form>
        <p id="news-msg" class="mt-2 text-xs" role="status" aria-live="polite">
          <span x-show="bad" x-cloak class="text-red-600">Please enter a valid email address.</span>
          <span x-show="done" x-cloak class="text-brand-700">Thanks! You're on the list.</span>
        </p>
      </div>
    </div>

    <div class="mt-12 pt-6 border-t border-slate-200 flex flex-col-reverse items-center text-center sm:flex-row sm:text-start gap-3 sm:justify-between text-xs text-slate-400">
      <p>© {{ date('Y') }} TalipapaPOS. All rights reserved.</p>
      <p class="flex items-center gap-4">Made in the Philippines 🇵🇭 <a href="#main" class="footer-link !text-xs inline-flex items-center gap-1">Back to top <span aria-hidden="true">↑</span></a></p>
    </div>
  </div>
</footer>

<script>
  (function () {
    var els = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('in'); }); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target, sibs = el.parentElement ? Array.prototype.indexOf.call(el.parentElement.children, el) : 0;
        el.style.transitionDelay = (Math.min(sibs, 5) * 80) + 'ms';
        el.classList.add('in');
        setTimeout(function () { el.classList.add('done'); }, 900);
        io.unobserve(el);
      });
    }, { threshold: 0.12 });
    els.forEach(function (e) { io.observe(e); });
  })();
</script>
@livewireScripts
</body>
</html>