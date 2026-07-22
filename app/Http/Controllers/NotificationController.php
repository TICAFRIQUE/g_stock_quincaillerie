<?php

namespace App\Http\Controllers;

use App\Notifications\StockSousSeuil;
use App\Notifications\VenteSignalee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function marquerLues(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->update(['read_at' => now()]);

        return back();
    }

    public function ouvrir(Request $request, string $notification): RedirectResponse
    {
        $notif = $request->user()->notifications()->findOrFail($notification);
        $notif->markAsRead();

        if ($notif->type === StockSousSeuil::class) {
            return redirect()->route('stock.index', ['magasin_id' => $notif->data['magasin_id'], 'sous_seuil' => 1]);
        }

        if ($notif->type === VenteSignalee::class) {
            return redirect()->route('ventes.ticket', $notif->data['vente_id']);
        }

        return redirect()->route('sessions.show', $notif->data['session_id']);
    }
}
