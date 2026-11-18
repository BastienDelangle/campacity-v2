<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\OrganizationController as AccountOrganizationController;
use App\Http\Controllers\Account\OrganizationMemberController;
use App\Http\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/organisations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');

Route::middleware(['auth', 'verified'])->prefix('espace')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/profil', [AccountController::class, 'profile'])->name('profile');
    Route::delete('/compte', [AccountController::class, 'destroy'])->name('destroy');

    Route::resource('organisations', AccountOrganizationController::class)
        ->parameters(['organisations' => 'organization'])
        ->names('organizations')
        ->except('show');

    Route::post('/organisations/{organization}/responsables', [OrganizationMemberController::class, 'store'])->name('organizations.members.store');
    Route::put('/organisations/{organization}/responsables/{member}', [OrganizationMemberController::class, 'update'])->name('organizations.members.update');
    Route::delete('/organisations/{organization}/responsables/{member}', [OrganizationMemberController::class, 'destroy'])->name('organizations.members.destroy');
});
