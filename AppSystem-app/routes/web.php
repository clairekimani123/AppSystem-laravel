<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


// Route::middleware(['web'])->group(function () {
    // Define your web routes here
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('password.reset');
// });
