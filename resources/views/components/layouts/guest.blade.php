<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ isset($title) ? $title . ' — TalipapaPOS' : 'TalipapaPOS' }}</title>
  <link rel="icon" type="image/png" href="{{ asset('talipapapos/assets/img/favicon.png') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body class="font-poppins text-gray-800 antialiased bg-[#f5faf7]">

{{-- One centered column: logo on top, page content (the card) right under it. --}}
<div class="relative isolate min-h-[100dvh] overflow-hidden bg-gradient-to-b from-[#f5faf7] to-white">
  {{-- decorative background (matches the landing page hero) --}}
  <div class="pointer-events-none absolute -top-10 -left-16 -z-10 h-72 w-72 rounded-full bg-brand-200/70 blur-[60px]" aria-hidden="true"></div>
  <div class="pointer-events-none absolute top-40 right-0 -z-10 h-80 w-80 rounded-full bg-brand-400/25 blur-[60px]" aria-hidden="true"></div>

  <main id="main" class="relative flex min-h-[100dvh] flex-col items-center px-4 pt-10 pb-8 sm:pt-16">

    <a href="{{ url('/') }}" class="mb-8 flex flex-col items-center gap-3 text-center" aria-label="TalipapaPOS home">
      <img src="{{ Vite::asset('resources/img/logo.png') }}" alt="TalipapaPOS" class="h-11 w-auto" width="193" height="36">
      <span class="block text-xs text-gray-500">Point of sale &amp; inventory for your shop</span>
    </a>

    <div class="w-full max-w-md">
      {{ $slot }}
    </div>

    <p class="mt-auto pt-10 text-xs text-gray-400">
      &copy; {{ date('Y') }} TalipapaPOS
    </p>
  </main>
</div>

@livewireScripts
</body>
</html>