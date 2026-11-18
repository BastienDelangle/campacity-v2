<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\MessageController;
use App\Http\Controllers\Account\OrganizationController as AccountOrganizationController;
use App\Http\Controllers\Account\OrganizationMemberController;
use App\Http\Controllers\Account\PartnerProfileController;
use App\Http\Controllers\Account\PublicationController as AccountPublicationController;
use App\Http\Controllers\Account\PublicationStatusController;
use App\Http\Controllers\Account\PurchaseController;
use App\Http\Controllers\Account\VisibilityController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\CommuneSearchController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\PublicationReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BrowseController::class, 'home'])->name('home');
Route::get('/annonces', [BrowseController::class, 'section'])->defaults('section', 'all')->name('publications.index');
Route::get('/cote-urbain', [BrowseController::class, 'section'])->defaults('section', 'urban')->name('browse.urban');
Route::get('/cote-rural', [BrowseController::class, 'section'])->defaults('section', 'rural')->name('browse.rural');
Route::get('/formations-et-rendez-vous', [BrowseController::class, 'section'])->defaults('section', 'agenda')->name('browse.agenda');
Route::get('/partenaires', [PartnerController::class, 'index'])->name('partners.index');
Route::get('/partenaires/{organization}', [PartnerController::class, 'show'])->name('partners.show');
Route::get('/offres-de-visibilite', [PartnerController::class, 'offers'])->name('visibility.offers');
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
    Route::get('/publications/{publication}/visibilite', [VisibilityController::class, 'create'])->name('publications.visibility.create');
    Route::post('/publications/{publication}/visibilite', [VisibilityController::class, 'store'])->name('publications.visibility.store');
    Route::get('/organisations/{organization}/partenaire-premium', [PartnerProfileController::class, 'edit'])->name('organizations.partner.edit');
    Route::post('/organisations/{organization}/partenaire-premium', [PartnerProfileController::class, 'subscribe'])->name('organizations.partner.subscribe');
    Route::put('/organisations/{organization}/partenaire-premium', [PartnerProfileController::class, 'update'])->name('organizations.partner.update');
    Route::get('/achats', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/achats/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::post('/achats/{purchase}/annuler', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
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
