<?php

use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\FrontBookingController;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WelcomeController;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/', function () {

    return redirect('/admin');

    // return view('welcome');
});

// Route::get('/home', [WelcomeController::class, 'index'])->name('welcome');
// Route::get('/booking', [FrontBookingController::class, 'index'])->name('booking');


Route::get('/factura', function () {
    $settings = Setting::first();
    $generalSettings = $settings?->general;


    $order = Order::find(1);

    return view('pdf.factura', compact('generalSettings', 'order'));
});


Route::post('orders/{order}/toggle-invoice', [OrderController::class, 'toggleInvoice'])->name('orders.toggleInvoice');
Route::get('/oauth2/authorize/google/{center}', [GmailController::class, 'redirectToGoogle'])->name('google.authorize');
Route::get('/oauth2/callback/google', [GmailController::class, 'handleGoogleCallback'])->name('google.callback');

Route::middleware('auth')->get('/admin/backups/download/{filepath}', [BackupDownloadController::class, 'download'])
    ->where('filepath', '.*')
    ->name('filament.backups.download');
