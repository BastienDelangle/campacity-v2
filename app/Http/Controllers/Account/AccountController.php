<?php

namespace App\Http\Controllers\Account;

use App\Actions\Users\AnonymizeUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        return view('account.dashboard', [
            'organizationsCount' => $request->user()->organizations()->count(),
            'publicationsCount' => $request->user()->publications()->count(),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function destroy(Request $request, AnonymizeUser $anonymize): RedirectResponse
    {
        $request->validateWithBag('deleteAccount', [
            'password' => ['required', 'current_password'],
            'confirmation' => ['accepted'],
        ], attributes: ['confirmation' => 'confirmation']);

        $anonymize->handle($request->user());

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Votre compte a été supprimé et vos données personnelles effacées.');
    }
}
