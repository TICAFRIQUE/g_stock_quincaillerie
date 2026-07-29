<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Mail\IdentifiantsUtilisateurMail;
use App\Models\Magasin;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UtilisateurController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $query = User::query()
            ->with(['magasin', 'roles'])
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where(function ($sub) use ($recherche) {
                    $sub->where('name', 'like', "%{$recherche}%")
                        ->orWhere('email', 'like', "%{$recherche}%");
                });
            });

        $utilisateurs = $this->appliquerTri($query, $request, ['name', 'email', 'actif', 'created_at'])
            ->paginate(20)
            ->withQueryString();

        return view('utilisateurs.index', ['utilisateurs' => $utilisateurs]);
    }

    public function create(): View
    {
        return view('utilisateurs.create', [
            'utilisateur' => null,
            'magasins' => Magasin::where('actif', true)->orderBy('nom')->get(),
            'roles' => Role::where('name', '!=', 'Superadmin')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);
        $motDePasse = $this->genererMotDePasseProvisoire();

        $utilisateur = User::create([
            'name' => $donnees['name'],
            'email' => $donnees['email'],
            'password' => $motDePasse,
            'magasin_id' => $donnees['magasin_id'] ?? null,
            'actif' => $donnees['actif'],
        ]);

        $utilisateur->assignRole($donnees['role']);

        Mail::to($utilisateur->email)->queue(new IdentifiantsUtilisateurMail($utilisateur, $motDePasse, nouveauCompte: true));

        return redirect()->route('utilisateurs.index')
            ->with('succes', "Utilisateur créé. Les identifiants ont été envoyés à {$utilisateur->email}.")
            ->with('motDePasseGenere', $motDePasse)
            ->with('utilisateurGenere', $utilisateur->email);
    }

    public function edit(User $utilisateur): View
    {
        abort_if($utilisateur->hasRole('Superadmin'), 403, 'Le compte Superadmin ne se gère pas depuis cet écran.');

        return view('utilisateurs.edit', [
            'utilisateur' => $utilisateur,
            'magasins' => Magasin::where('actif', true)->orderBy('nom')->get(),
            'roles' => Role::where('name', '!=', 'Superadmin')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $utilisateur): RedirectResponse
    {
        abort_if($utilisateur->hasRole('Superadmin'), 403);

        $donnees = $this->valider($request, $utilisateur);

        $utilisateur->update([
            'name' => $donnees['name'],
            'email' => $donnees['email'],
            'magasin_id' => $donnees['magasin_id'] ?? null,
            'actif' => $donnees['actif'],
        ]);

        $utilisateur->syncRoles([$donnees['role']]);

        return redirect()->route('utilisateurs.index')->with('succes', 'Utilisateur mis à jour.');
    }

    public function reinitialiserMotDePasse(User $utilisateur): RedirectResponse
    {
        abort_if($utilisateur->hasRole('Superadmin'), 403);

        $motDePasse = $this->genererMotDePasseProvisoire();
        $utilisateur->update(['password' => $motDePasse]);

        Mail::to($utilisateur->email)->queue(new IdentifiantsUtilisateurMail($utilisateur, $motDePasse, nouveauCompte: false));

        return redirect()->route('utilisateurs.index')
            ->with('succes', "Mot de passe réinitialisé. Les nouveaux identifiants ont été envoyés à {$utilisateur->email}.")
            ->with('motDePasseGenere', $motDePasse)
            ->with('utilisateurGenere', $utilisateur->email);
    }

    public function destroy(User $utilisateur): RedirectResponse
    {
        abort_if($utilisateur->hasRole('Superadmin'), 403);
        abort_if($utilisateur->id === auth()->id(), 403, 'Vous ne pouvez pas supprimer votre propre compte.');

        try {
            $utilisateur->delete();
        } catch (QueryException) {
            return redirect()->route('utilisateurs.index')->with('erreur', 'Cet utilisateur a des données associées (ventes, sessions…), désactivez-le plutôt.');
        }

        return redirect()->route('utilisateurs.index')->with('succes', 'Utilisateur supprimé.');
    }

    private function valider(Request $request, ?User $utilisateur = null): array
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.($utilisateur?->id)],
            'magasin_id' => ['nullable', 'exists:magasins,id'],
            'role' => ['required', 'exists:roles,name', 'not_in:Superadmin'],
            'actif' => ['boolean'],
        ]);

        $donnees['actif'] = $request->boolean('actif', true);

        return $donnees;
    }

    /**
     * Alphanumérique en majuscules uniquement (pas de symboles, pas de
     * minuscules) : mot de passe provisoire plus simple à lire/retaper pour
     * un utilisateur non technique qui le reçoit par e-mail.
     */
    private function genererMotDePasseProvisoire(): string
    {
        return Str::upper(Str::random(8));
    }
}
