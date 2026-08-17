<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'username' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        // Le code à 4 chiffres est stocké comme n'importe quel mot de passe
        // (colonne password, hashé) : seul le champ de connexion change.
        if (! Auth::attempt(['username' => $donnees['username'], 'password' => $donnees['code']])) {
            throw ValidationException::withMessages([
                'username' => 'Identifiants incorrects.',
            ]);
        }

        if (! Auth::user()->actif) {
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => 'Ce compte est désactivé.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
