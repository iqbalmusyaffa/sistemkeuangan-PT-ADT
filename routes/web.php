<?php

use Illuminate\Support\Facades\Route;

// Route for the welcome page
Route::get('/', function () {
    return view('welcome');
});

// Route for the main application
// Route for the Vue application
Route::get('/{any}', function () {
    return view('app'); // This will load your main Vue app
})->where('any', '.*'); // This allows all routes to be handled by Vue
Route::get('/dashboard', function () {
    return view('dashboard'); // Pastikan Anda membuat view ini
});
