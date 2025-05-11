<?php

use Illuminate\Support\Facades\Route;

// route redirect ke dashboard
// Route::redirect('/', '/dashboard'); // Langsung redirect ke dashboard

// Route universal untuk SPA Vue
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
// route untuk menampilkan view dashboard
// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth']);
