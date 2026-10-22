<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\User\Dashboard as UserDashboard;
use App\Livewire\User\Product\Index as ProductIndex;
use App\Livewire\User\Product\Create as ProductCreate;
use App\Livewire\User\Product\Edit as ProductEdit;
use App\Livewire\User\PurchaseOrder\Index as PurchaseOrderIndex;
use App\Livewire\User\Supplier\Create as SupplierCreate;
use App\Livewire\User\Supplier\Edit as SupplierEdit;
use App\Livewire\User\Supplier\Index as SupplierIndex;
use App\Livewire\User\Category\Index as CategoryIndex;
use App\Livewire\User\InventoryMovement\Index as InventoryMovementIndex;
use App\Livewire\User\InventoryMovement\AdjustStock as InventoryMovementAdjustStock;
use App\Livewire\User\InventoryMovement\Show as InventoryMovementShow;
use App\Livewire\User\Expense\Index as ExpenseIndex;

use App\Livewire\User\PurchaseOrder\Create as PurchaseOrderCreate;
use App\Livewire\User\PurchaseOrder\Edit as PurchaseOrderEdit;
use App\Livewire\User\PurchaseOrder\Show as PurchaseOrderShow;



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
    Route::middleware('role:user')->group(function () {
        Route::get('/dashboard', UserDashboard::class)
            ->name('dashboard');

        Route::get('/products', ProductIndex::class)
            ->name('user.products');
        Route::get('/products/create', ProductCreate::class)
            ->name('user.products.create');
        Route::get('/products/{product}/edit', ProductEdit::class)
            ->name('user.products.edit');

        Route::get('/suppliers', SupplierIndex::class)
            ->name('user.suppliers');
        Route::get('/suppliers/create', SupplierCreate::class)
            ->name('user.suppliers.create');
        Route::get('/suppliers/{supplier}/edit', SupplierEdit::class)
            ->name('user.suppliers.edit');

        Route::get('/purchase-orders', PurchaseOrderIndex::class)
            ->name('user.purchase-orders');

        Route::get('/purchase-orders/create', PurchaseOrderCreate::class)
            ->name('user.purchase-orders.create');
        Route::get('/purchase-orders/{purchaseOrder}/edit', PurchaseOrderEdit::class)
            ->name('user.purchase-orders.edit');
        Route::get('/purchase-orders/{purchaseOrder}', PurchaseOrderShow::class)
            ->name('user.purchase-orders.show');

        Route::get('/categories', CategoryIndex::class)
            ->name('user.categories');

        Route::get('/inventory-movements', InventoryMovementIndex::class)
            ->name('user.inventory-movements');

        Route::get('/inventory-movements/adjust-stock', InventoryMovementAdjustStock::class)
            ->name('user.inventory-movements.adjust-stock');

        Route::get('/inventory-movements/{inventoryMovement}', InventoryMovementShow::class)
            ->name('user.inventory-movements.show');

        Route::get('/expenses', ExpenseIndex::class)
            ->name('user.expenses');

    
    });





    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', AdminDashboard::class)
            ->name('admin.dashboard');
    });

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
