<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth', 'verified'])->prefix('espace')->name('account.')->group(function () {
    Route::view('/', 'account.dashboard')->name('dashboard');
    Route::view('/profil', 'account.profile')->name('profile');
});
