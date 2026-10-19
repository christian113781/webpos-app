<div>
  <div class="bg-white rounded-2xl shadow-card border border-brand-100 p-6 sm:p-8">
    <div class="text-center">
      <h1 class="text-2xl font-bold text-gray-900">Create your account</h1>
      <p class="text-sm text-gray-500 mt-1">It only takes a minute.</p>
    </div>

    @if (session('error'))
      <p class="mt-4 rounded-xl bg-red-50 border border-red-200 px-4 py-2.5 text-sm text-red-700" role="alert">{{ session('error') }}</p>
    @endif

    <a href="{{ route('google.redirect') }}" class="mt-6 w-full flex items-center justify-center gap-3 px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-sm font-semibold text-gray-700 transition-colors min-h-[44px] focus-visible:ring-2 focus-visible:ring-brand-400">
      <svg class="w-5 h-5" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.5 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.6 5.9c4.4-4.1 7-10.1 7-17.6z"/><path fill="#FBBC05" d="M10.5 28.7A14.5 14.5 0 019.5 24c0-1.6.3-3.2.8-4.7l-7.9-6.1A24 24 0 000 24c0 3.9.9 7.5 2.6 10.8l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.6-5.9c-2.1 1.4-4.9 2.3-8.3 2.3-6.3 0-11.6-4-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/></svg>
      Continue with Google
    </a>

    <div class="flex items-center gap-3 my-5" role="separator" aria-label="or sign up with email">
      <span class="flex-1 h-px bg-gray-200"></span>
      <span class="text-xs text-gray-500">or sign up with email</span>
      <span class="flex-1 h-px bg-gray-200"></span>
    </div>

    <form wire:submit="register" novalidate class="space-y-4">
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full name</label>
        <input id="name" type="text" wire:model="name" autocomplete="name" autofocus required
               class="w-full px-4 py-2.5 rounded-xl border text-sm bg-white focus:outline-none focus:ring-2 min-h-[44px] @error('name') border-red-400 focus:ring-red-200 focus:border-red-400 @else border-gray-200 focus:ring-brand-400 focus:border-brand-400 @enderror" placeholder="Maria Santos" @error('name') aria-invalid="true" aria-describedby="name-err" @enderror>
        @error('name') <p id="name-err" class="text-xs text-red-500 mt-1.5" role="alert">{{ $message }}</p> @enderror
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
        <input id="email" type="email" wire:model="email" autocomplete="email" required
               class="w-full px-4 py-2.5 rounded-xl border text-sm bg-white focus:outline-none focus:ring-2 min-h-[44px] @error('email') border-red-400 focus:ring-red-200 focus:border-red-400 @else border-gray-200 focus:ring-brand-400 focus:border-brand-400 @enderror" placeholder="you@yourshop.com" @error('email') aria-invalid="true" aria-describedby="email-err" @enderror>
        @error('email') <p id="email-err" class="text-xs text-red-500 mt-1.5" role="alert">{{ $message }}</p> @enderror
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
        <div class="relative" x-data="{ show: false }">
          <input id="password" :type="show ? 'text' : 'password'" wire:model="password" autocomplete="new-password" required
                 class="w-full px-4 py-2.5 rounded-xl border text-sm bg-white focus:outline-none focus:ring-2 min-h-[44px] pe-11 @error('password') border-red-400 focus:ring-red-200 focus:border-red-400 @else border-gray-200 focus:ring-brand-400 focus:border-brand-400 @enderror" placeholder="Create a password" @error('password') aria-invalid="true" aria-describedby="password-err" @enderror>
          <button type="button" @click="show = !show" class="absolute inset-y-0 end-0 px-3 text-gray-400 hover:text-gray-600 rounded-xl focus-visible:ring-2 focus-visible:ring-brand-400" :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="String(show)">
          <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
          </button>
        </div>
        @error('password') <p id="password-err" class="text-xs text-red-500 mt-1.5" role="alert">{{ $message }}</p> @enderror
      </div>

      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm password</label>
        <div class="relative" x-data="{ show: false }">
          <input id="password_confirmation" :type="show ? 'text' : 'password'" wire:model="password_confirmation" autocomplete="new-password" required
                 class="w-full px-4 py-2.5 rounded-xl border text-sm bg-white focus:outline-none focus:ring-2 min-h-[44px] pe-11 @error('password_confirmation') border-red-400 focus:ring-red-200 focus:border-red-400 @else border-gray-200 focus:ring-brand-400 focus:border-brand-400 @enderror" placeholder="Re-enter your password" @error('password_confirmation') aria-invalid="true" aria-describedby="password_confirmation-err" @enderror>
          <button type="button" @click="show = !show" class="absolute inset-y-0 end-0 px-3 text-gray-400 hover:text-gray-600 rounded-xl focus-visible:ring-2 focus-visible:ring-brand-400" :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="String(show)">
          <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
          </button>
        </div>
        @error('password_confirmation') <p id="password_confirmation-err" class="text-xs text-red-500 mt-1.5" role="alert">{{ $message }}</p> @enderror
      </div>

      <button type="submit" wire:loading.attr="disabled" class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-semibold transition-colors min-h-[44px] focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
        <span wire:loading.remove wire:target="register">Create account</span>
        <span wire:loading wire:target="register">Creating account…</span>
      </button>
    </form>
  </div>

  @if (Route::has('login'))
    <p class="text-center text-sm text-gray-500 mt-6">Already have an account?
      <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-600 hover:text-brand-700">Sign in</a></p>
  @endif
</div>