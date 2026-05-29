<?php

use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Photographer\EventController;
use App\Http\Controllers\Public\DownloadController;
use App\Http\Controllers\Public\MediaController;
use App\Http\Controllers\Public\OrderController;
use App\Http\Controllers\Public\PaymentController;
use App\Http\Controllers\Public\TrackingController;
use App\Livewire\Public\Cart;
use App\Livewire\Public\Checkout;
use App\Livewire\Public\EventPage;
use App\Livewire\Public\PhotoDetail;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:admin,photographer'])->group(function (): void {
    Route::resource('events', EventController::class)->except(['store', 'update', 'destroy']);
    Route::get('events/{event}/photos', [EventController::class, 'photos'])->name('events.photos');
    Route::get('events/{event}/orders', [EventController::class, 'orders'])->name('events.orders');
    Route::get('events/{event}/orders/export', [EventController::class, 'export_orders'])->name('events.orders.export');
    Route::get('events/{event}/orders/{order}', [EventController::class, 'order'])->name('events.orders.show');
    Route::get('events/{event}/qr-code.svg', [EventController::class, 'qr_code'])->name('events.qr-code');
    Route::get('events/{event}/poster', [EventController::class, 'poster'])->name('events.poster');
    Route::post('events/{event}/members', [EventController::class, 'add_member'])->name('events.members.store');
    Route::delete('events/{event}/members/{member}', [EventController::class, 'remove_member'])->name('events.members.destroy');
    Route::post('events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
    Route::post('events/{event}/archive', [EventController::class, 'archive'])->name('events.archive');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
});

Route::middleware(['auth', 'verified', 'role:customer'])->group(function (): void {
    Route::get('/my/photos', [CustomerOrderController::class, 'index'])->name('customer.orders.index');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/e/{slug}', EventPage::class)->name('public.events.show');
Route::get('/e/{slug}/search', EventPage::class)->name('public.events.search');
Route::get('/e/{slug}/photos/{event_photo}', PhotoDetail::class)->name('public.photos.show');
Route::get('/track/events/{slug}/whatsapp', [TrackingController::class, 'whatsapp'])->name('tracking.events.whatsapp');
Route::get('/media/photos/{event_photo}/thumbnail', [MediaController::class, 'thumbnail'])->name('media.photos.thumbnail');
Route::get('/media/photos/{event_photo}/watermarked', [MediaController::class, 'watermarked'])->name('media.photos.watermarked');
Route::get('/cart', Cart::class)->name('cart.show');
Route::get('/checkout', Checkout::class)->name('checkout.show');
Route::post('/payments/mock/{order}/{download_token}/approve', [PaymentController::class, 'approve_mock'])->name('payments.mock.approve');
Route::post('/payments/mercado-pago/webhook', [PaymentController::class, 'mercado_pago_webhook'])->name('payments.mercado-pago.webhook');
Route::get('/orders/{order}/pending/{download_token}', [OrderController::class, 'pending'])->name('orders.pending');
Route::get('/orders/{order}/success/{download_token}', [OrderController::class, 'success'])->name('orders.success');
Route::get('/orders/{order}/downloads/{download_token}', [OrderController::class, 'downloads'])->name('orders.downloads');
Route::get('/downloads/{order}/{event_photo}', DownloadController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('orders.download');

require __DIR__.'/auth.php';
