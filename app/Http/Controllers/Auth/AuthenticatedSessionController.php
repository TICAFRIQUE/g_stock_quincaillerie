<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Limite les tentatives par (identifiant + IP), pas juste par IP : un
     * code à 4 chiffres ne couvre que 10 000 combinaisons — sans ce verrou,
     * un script les épuise en quelques minutes pour un identifiant connu.
     */
    private const MAX_TENTATIVES = 5;

    private const DECOMPTE_SECONDES = 60;

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

        $cle = $this->cleLimitation($request, $donnees['username']);

        if (RateLimiter::tooManyAttempts($cle, self::MAX_TENTATIVES)) {
            $secondes = RateLimiter::availableIn($cle);

            throw ValidationException::withMessages([
                'username' => "Trop de tentatives. Réessayez dans {$secondes} secondes.",
            ]);
        }

        // Le code à 4 chiffres est stocké comme n'importe quel mot de passe
        // (colonne password, hashé) : seul le champ de connexion change.
        if (! Auth::attempt(['username' => $donnees['username'], 'password' => $donnees['code']])) {
            RateLimiter::hit($cle, self::DECOMPTE_SECONDES);

            throw ValidationException::withMessages([
                'username' => 'Identifiants incorrects.',
            ]);
        }

        RateLimiter::clear($cle);

        if (! Auth::user()->actif) {
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => 'Ce compte est désactivé.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function cleLimitation(Request $request, string $username): string
    {
        return Str::transliterate(Str::lower($username)).'|'.$request->ip();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
