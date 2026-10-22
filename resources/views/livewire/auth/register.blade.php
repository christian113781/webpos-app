<div>
  <x-ui.card flush class="border border-brand-100 p-6 sm:p-8">
    <div class="text-center">
      <h1 class="text-2xl font-bold text-gray-900">Create your account</h1>
      <p class="text-sm text-gray-500 mt-1">It only takes a minute.</p>
    </div>

    @if (session('error'))
      <p class="mt-4 rounded-xl bg-red-50 border border-red-200 px-4 py-2.5 text-sm text-red-700" role="alert">{{ session('error') }}</p>
    @endif

    <x-ui.button href="{{ route('google.redirect') }}" variant="secondary" class="mt-6 w-full min-h-[44px] gap-3">
      <svg class="w-5 h-5" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.5 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.6 5.9c4.4-4.1 7-10.1 7-17.6z"/><path fill="#FBBC05" d="M10.5 28.7A14.5 14.5 0 019.5 24c0-1.6.3-3.2.8-4.7l-7.9-6.1A24 24 0 000 24c0 3.9.9 7.5 2.6 10.8l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.6-5.9c-2.1 1.4-4.9 2.3-8.3 2.3-6.3 0-11.6-4-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/></svg>
      Continue with Google
    </x-ui.button>

    <div class="flex items-center gap-3 my-5" role="separator" aria-label="or sign up with email">
      <span class="flex-1 h-px bg-gray-200"></span>
      <span class="text-xs text-gray-500">or sign up with email</span>
      <span class="flex-1 h-px bg-gray-200"></span>
    </div>

    <form wire:submit="register" novalidate class="space-y-4">
      <x-ui.input name="name" label="Full name" wire:model="name" autocomplete="name" autofocus required placeholder="Maria Santos" />

      <x-ui.input name="email" label="Email" type="email" wire:model="email" autocomplete="email" required placeholder="you@yourshop.com" />

      <x-ui.input id="password" name="password" label="Password" type="password" wire:model="password" autocomplete="new-password" required placeholder="Create a password" />

      <x-ui.input id="password_confirmation" name="password_confirmation" label="Confirm password" type="password" wire:model="password_confirmation" autocomplete="new-password" required placeholder="Re-enter your password" />

      <x-ui.button type="submit" target="register" class="w-full min-h-[44px]">
        <span wire:loading.remove wire:target="register">Create account</span>
        <span wire:loading wire:target="register">Creating account…</span>
      </x-ui.button>
    </form>
  </x-ui.card>

  @if (Route::has('login'))
    <p class="text-center text-sm text-gray-500 mt-6">Already have an account?
      <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-600 hover:text-brand-700">Sign in</a></p>
  @endif
</div>