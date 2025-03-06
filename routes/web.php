<?php

use Illuminate\Support\Facades\Route;

// Route for the welcome page
Route::redirect('/', '/dashboard'); // Langsung redirect ke dashboard

// Route for the main application
// Route for the Vue application
Route::get('/{any}', function () {
    return view('app'); // This will load your main Vue app
})->where('any', '.*'); // This allows all routes to be handled by Vue
Route::get('/dashboard', function () {
    return view('dashboard'); // Pastikan file ini ada di resources/views/dashboard.blade.php
})->middleware(['auth']); // Pastikan hanya user yang login bisa mengakses
