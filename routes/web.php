<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\MessageController;
use App\Http\Controllers\Account\OrganizationController as AccountOrganizationController;
use App\Http\Controllers\Account\OrganizationMemberController;
use App\Http\Controllers\Account\PublicationController as AccountPublicationController;
use App\Http\Controllers\Account\PublicationStatusController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\CommuneSearchController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\PublicationReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BrowseController::class, 'home'])->name('home');
Route::get('/annonces', [BrowseController::class, 'section'])->defaults('section', 'all')->name('publications.index');
Route::get('/cote-urbain', [BrowseController::class, 'section'])->defaults('section', 'urban')->name('browse.urban');
Route::get('/cote-rural', [BrowseController::class, 'section'])->defaults('section', 'rural')->name('browse.rural');
Route::get('/formations-et-rendez-vous', [BrowseController::class, 'section'])->defaults('section', 'agenda')->name('browse.agenda');
Route::get('/communes/recherche', CommuneSearchController::class)->middleware('throttle:60,1')->name('communes.search');

Route::get('/organisations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
Route::get('/annonces/{slug}', [PublicationController::class, 'show'])->name('publications.show');
Route::post('/annonces/{publication}/contact', [ContactMessageController::class, 'store'])->middleware('throttle:public-forms')->name('publications.contact');
Route::post('/annonces/{publication}/signaler', [PublicationReportController::class, 'store'])->middleware('throttle:public-forms')->name('publications.report');
Route::get('/annonces/{publication}/documents/{attachment}', [PublicationController::class, 'attachment'])->name('publications.attachment');

Route::middleware(['auth', 'verified'])->prefix('espace')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/profil', [AccountController::class, 'profile'])->name('profile');
    Route::delete('/compte', [AccountController::class, 'destroy'])->name('destroy');

    Route::resource('organisations', AccountOrganizationController::class)
        ->parameters(['organisations' => 'organization'])
        ->names('organizations')
        ->except('show');

    Route::resource('publications', AccountPublicationController::class)->except('show');
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    Route::get('/publications/{publication}/apercu', [AccountPublicationController::class, 'preview'])->name('publications.preview');
    Route::post('/publications/{publication}/soumettre', [PublicationStatusController::class, 'submit'])->name('publications.submit');
    Route::post('/publications/{publication}/archiver', [PublicationStatusController::class, 'archive'])->name('publications.archive');
    Route::post('/publications/{publication}/renouveler', [PublicationStatusController::class, 'renew'])->name('publications.renew');

    Route::post('/organisations/{organization}/responsables', [OrganizationMemberController::class, 'store'])->name('organizations.members.store');
    Route::put('/organisations/{organization}/responsables/{member}', [OrganizationMemberController::class, 'update'])->name('organizations.members.update');
    Route::delete('/organisations/{organization}/responsables/{member}', [OrganizationMemberController::class, 'destroy'])->name('organizations.members.destroy');
});
