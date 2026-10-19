<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\User\Dashboard as UserDashboard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Socialite;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

// Google authentication
Route::get('/auth/redirect', function () {
    return Socialite::driver('google')->redirect();
})->name('google.redirect');

Route::get('/auth/callback', function () {
    $googleUser = Socialite::driver('google')->user();

    $user = User::firstOrNew([
        'google_id' => $googleUser->id,
    ]);
    $user->fill([
        'name' => $googleUser->name,
        'email' => $googleUser->email,
    ]);

    if (! $user->exists) {
        $user->system_role = 'user';
    }

    $user->save();
    Auth::login($user);

    return redirect()->route($user->dashboardRoute());
})->name('google.callback');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', UserDashboard::class)
        ->middleware('role:user')
        ->name('dashboard');

    Route::get('/admin/dashboard', AdminDashboard::class)
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
