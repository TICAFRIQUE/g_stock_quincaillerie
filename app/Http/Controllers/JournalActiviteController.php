<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/**
 * Consultation du journal d'activité (traçabilité, CLAUDE.md §Traçabilité) :
 * réservé au gérant et au superadmin (permission rapport.voir), au même
 * titre que les autres rapports.
 */
class JournalActiviteController extends Controller
{
    public function index(Request $request): View
    {
        $debut = $request->filled('debut')
            ? Carbon::parse($request->string('debut'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $fin = $request->filled('fin')
            ? Carbon::parse($request->string('fin'))->endOfDay()
            : Carbon::now()->endOfDay();

        $activites = Activity::query()
            ->with('causer')
            ->whereBetween('created_at', [$debut, $fin])
            ->when($request->filled('type'), fn ($q) => $q->where('subject_type', $request->string('type')))
            ->when($request->filled('causeur_id'), fn ($q) => $q->where('causer_id', $request->integer('causeur_id')))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('journal.index', [
            'activites' => $activites,
            'debut' => $debut,
            'fin' => $fin,
            'types' => $this->typesDisponibles(),
            'utilisateurs' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * @return array<string, string> classe complète => libellé court affiché
     */
    private function typesDisponibles(): array
    {
        return Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->mapWithKeys(fn (string $fqcn) => [$fqcn => class_basename($fqcn)])
            ->all();
    }
}
