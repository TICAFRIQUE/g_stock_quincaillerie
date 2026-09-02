<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\AbonnementActivation;
use App\Models\ConfigurationAbonnement;
use App\Models\FormuleAbonnement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbonnementController extends Controller
{
    /**
     * Lecture seule, accessible à tout utilisateur connecté — sert aussi
     * d'écran de redirection quand l'abonnement est expiré (voir
     * AssureAbonnementActif), donc jamais de garde ici.
     */
    public function mon(Request $request): View
    {
        $derniere = AbonnementActivation::query()->with('formule')->latest('date_debut')->latest('id')->first();

        return view('abonnement.mon', [
            'derniere' => $derniere,
            'bloquant' => Abonnement::estBloquant(),
            'joursRestants' => Abonnement::joursRestants(),
            'historique' => AbonnementActivation::query()->with(['formule', 'auteur'])
                ->latest('date_debut')->latest('id')->paginate(15),
            'configuration' => ConfigurationAbonnement::actuel(),
            'estGestionnaire' => (bool) $request->user()?->estGestionnaireAbonnement(),
        ]);
    }

    public function gestion(): View
    {
        $derniere = AbonnementActivation::query()->with('formule')->latest('date_debut')->latest('id')->first();

        return view('abonnement.gestion', [
            'derniere' => $derniere,
            'joursRestants' => Abonnement::joursRestants(),
            'formules' => FormuleAbonnement::query()->orderByDesc('actif')->orderBy('jours')->get(),
            'historique' => AbonnementActivation::query()->with(['formule', 'auteur'])
                ->latest('date_debut')->latest('id')->paginate(15),
            'configuration' => ConfigurationAbonnement::actuel(),
        ]);
    }

    public function activer(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'formule_id' => ['nullable', 'exists:formules_abonnement,id'],
            'illimite' => ['required', 'boolean'],
            'jours' => ['required_if:illimite,false', 'nullable', 'integer', 'min:1'],
            'montant' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $formule = $donnees['formule_id'] ? FormuleAbonnement::findOrFail($donnees['formule_id']) : null;

        Abonnement::activer(
            formule: $formule,
            montant: $donnees['montant'],
            jours: $donnees['illimite'] ? null : (int) $donnees['jours'],
            illimite: $donnees['illimite'],
            note: $donnees['note'] ?? null,
            auteur: $request->user(),
        );

        return redirect()->route('abonnement.gestion')->with('succes', 'Abonnement activé.');
    }

    public function storeFormule(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'illimite' => ['required', 'boolean'],
            'jours' => ['required_if:illimite,false', 'nullable', 'integer', 'min:1'],
            'prix' => ['required', 'integer', 'min:0'],
        ]);

        FormuleAbonnement::create([
            'nom' => $donnees['nom'],
            'illimite' => $donnees['illimite'],
            'jours' => $donnees['illimite'] ? null : $donnees['jours'],
            'prix' => $donnees['prix'],
            'actif' => true,
        ]);

        return redirect()->route('abonnement.gestion')->with('succes', 'Formule créée.');
    }

    public function toggleFormule(FormuleAbonnement $formuleAbonnement): RedirectResponse
    {
        $formuleAbonnement->update(['actif' => ! $formuleAbonnement->actif]);

        return redirect()->route('abonnement.gestion')->with('succes', 'Formule mise à jour.');
    }

    public function updateConfiguration(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'telephone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        ConfigurationAbonnement::actuel()->update($donnees);

        return redirect()->route('abonnement.gestion')->with('succes', 'Coordonnées mises à jour.');
    }
}
