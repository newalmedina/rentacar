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


// Route::get('/factura', function () {
//     $settings = Setting::first();
//     $generalSettings = $settings?->general;


//     $order = Order::find(1);

//     // Verifica si el centro tiene habilitado el mensaje
//     if (!$order->center->enable_start_message) {
//         //  $this->info("El centro {$order->center->name} no tiene habilitado el start message.");
//         return;
//     }

//     try {
//         // Envía el mail usando el Mailable que creamos
//         \Mail::to($order->customer->email)
//             ->cc($order->center->email) // copia al centro
//             ->send(new \App\Mail\StartMessageNotification($order));

//         // $this->info("Notificación start message enviada para la reserva {$order->code}");
//     } catch (\Exception $e) {
//         // Loguea el error sin romper la ejecución
//         //$this->error("Error al enviar notificación start message: " . $e->getMessage());
//     }
//     // return view('pdf.factura', compact('generalSettings', 'order'));
// });



Route::post('orders/{order}/toggle-invoice', [OrderController::class, 'toggleInvoice'])->name('orders.toggleInvoice');
Route::middleware('auth')->get('/oauth2/authorize/google/{center}', [GmailController::class, 'redirectToGoogle'])->name('google.authorize');
Route::get('/oauth2/callback/google', [GmailController::class, 'handleGoogleCallback'])->name('google.callback');

Route::middleware('auth')->get('/admin/backups/download/{filepath}', [BackupDownloadController::class, 'download'])
    ->where('filepath', '.*')
    ->name('filament.backups.download');


Route::get('/oauth2callback', [GmailController::class, 'oauthCallback']);
