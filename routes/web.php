<?php

use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Photographer\EventController;
use App\Http\Controllers\Public\DownloadController;
use App\Http\Controllers\Public\MediaController;
use App\Http\Controllers\Public\OrderController;
use App\Livewire\Public\Cart;
use App\Livewire\Public\Checkout;
use App\Livewire\Public\EventPage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:admin,photographer'])->group(function (): void {
    Route::resource('events', EventController::class)->except(['store', 'update', 'destroy']);
    Route::get('events/{event}/photos', [EventController::class, 'photos'])->name('events.photos');
    Route::get('events/{event}/orders', [EventController::class, 'orders'])->name('events.orders');
    Route::post('events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
});

Route::middleware(['auth', 'verified', 'role:customer'])->group(function (): void {
    Route::get('/my/photos', [CustomerOrderController::class, 'index'])->name('customer.orders.index');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/e/{slug}', EventPage::class)->name('public.events.show');
Route::get('/e/{slug}/search', EventPage::class)->name('public.events.search');
Route::get('/media/photos/{event_photo}/thumbnail', [MediaController::class, 'thumbnail'])->name('media.photos.thumbnail');
Route::get('/media/photos/{event_photo}/watermarked', [MediaController::class, 'watermarked'])->name('media.photos.watermarked');
Route::get('/cart', Cart::class)->name('cart.show');
Route::get('/checkout', Checkout::class)->name('checkout.show');
Route::get('/orders/{order}/success/{download_token}', [OrderController::class, 'success'])->name('orders.success');
Route::get('/orders/{order}/downloads/{download_token}', [OrderController::class, 'downloads'])->name('orders.downloads');
Route::get('/orders/{order}/downloads/{download_token}/{event_photo}', DownloadController::class)->name('orders.download');

require __DIR__.'/auth.php';
