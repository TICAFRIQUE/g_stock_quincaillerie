<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Devis {{ $devis->numero }}</title>
    <style>
        /* Feuille de style volontairement autonome (pas de Bootstrap) : ce
           document sert aussi de source au PDF (dompdf), dont le support CSS
           est limité — tables et boîtes simples uniquement, pas de flex/grid. */
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 13px;
            color: #241e19;
            margin: 0;
            padding: 28px;
        }
        table { border-collapse: collapse; }
        .text-end { text-align: right; }
        .actions { margin-bottom: 20px; }
        .actions a, .actions button {
            display: inline-block;
            margin-right: 8px;
            padding: 6px 14px;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid #e8590c;
            border-radius: 4px;
            color: #e8590c;
            background: #fff;
            cursor: pointer;
        }
        .entete { width: 100%; margin-bottom: 24px; }
        .entete td { vertical-align: top; }
        .logo { max-height: 70px; max-width: 220px; }
        .entreprise-nom { font-size: 16px; font-weight: bold; }
        .devis-titre { font-size: 22px; font-weight: bold; text-align: right; color: #e8590c; letter-spacing: 1px; }
        .devis-meta { text-align: right; font-size: 12px; color: #555; line-height: 1.6; }
        .bloc-client { margin: 16px 0 24px; padding: 12px 14px; background: #f6f3ef; border: 1px solid #ddd3c8; }
        .bloc-client .label { font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 4px; }
        table.lignes { width: 100%; margin-top: 8px; }
        table.lignes th, table.lignes td { border: 1px solid #ccc; padding: 6px 8px; font-size: 12px; }
        table.lignes th { background: #f0ece6; text-align: left; }
        table.totaux { width: 45%; margin-left: 55%; margin-top: 12px; }
        table.totaux td { padding: 5px 8px; }
        table.totaux .net td { font-weight: bold; font-size: 15px; border-top: 2px solid #241e19; }
        .mention { margin-top: 30px; font-size: 11px; color: #888; text-align: center; }
        @media print {
            .actions { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    @unless ($pourPdf ?? false)
        <div class="actions">
            <button type="button" onclick="window.print()">Imprimer</button>
            <a href="{{ route('devis.pdf', $devis) }}">Télécharger en PDF</a>
            <a href="{{ route('devis.excel', $devis) }}">Télécharger en Excel</a>
            <a href="{{ route('devis.show', $devis) }}">Retour au devis</a>
        </div>
    @endunless

    <table class="entete">
        <tr>
            <td style="width: 55%;">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                    <br>
                @endif
                <span class="entreprise-nom">{{ $parametre->nom }}</span><br>
                @if ($parametre->adresse) {{ $parametre->adresse }}<br> @endif
                @if ($parametre->numero) Tél : {{ $parametre->numero }} @endif
            </td>
            <td style="width: 45%;">
                <div class="devis-titre">DEVIS</div>
                <div class="devis-meta">
                    N° {{ $devis->numero }}<br>
                    Date : {{ $devis->created_at->format('d/m/Y') }}<br>
                    Valide jusqu'au : {{ $devis->date_validite->format('d/m/Y') }}<br>
                    Magasin : {{ $devis->magasin->nom }}
                </div>
            </td>
        </tr>
    </table>

    <div class="bloc-client">
        <div class="label">Client</div>
        <strong>{{ $devis->client->nom }}</strong><br>
        @if ($devis->client->telephone) Tél : {{ $devis->client->telephone }}<br> @endif
        @if ($devis->client->adresse) {{ $devis->client->adresse }} @endif
    </div>

    <table class="lignes">
        <thead>
            <tr>
                <th>Désignation</th>
                <th>Unité</th>
                <th class="text-end">Qté</th>
                <th class="text-end">Prix unitaire</th>
                <th class="text-end">Remise</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($devis->lignes as $ligne)
                @php
                    $prixUnitaire = $ligne->uniteVente->prix ?? $ligne->produit->prix_piece;
                    $sousTotalLigne = $prixUnitaire * $ligne->quantite;
                    $remiseLigne = \App\Support\Remise::resoudre($ligne->remise_type, $ligne->remise_valeur, $sousTotalLigne);
                @endphp
                <tr>
                    <td>{{ $ligne->produit->libelle_affichage }}</td>
                    <td>{{ $ligne->uniteVente->libelle ?? $ligne->produit->unite_base_libelle }}</td>
                    <td class="text-end">{{ $ligne->quantite }}</td>
                    <td class="text-end">{{ number_format($prixUnitaire, 0, ',', ' ') }} F</td>
                    <td class="text-end">{{ $remiseLigne > 0 ? '− '.number_format($remiseLigne, 0, ',', ' ').' F' : '—' }}</td>
                    <td class="text-end">{{ number_format($sousTotalLigne - $remiseLigne, 0, ',', ' ') }} F</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totaux">
        <tr class="net">
            <td>Total net</td>
            <td class="text-end">{{ number_format($montants['total_net'], 0, ',', ' ') }} F</td>
        </tr>
    </table>

    <div class="mention">
        Ce document est un devis, pas une facture — montants indicatifs, valables jusqu'au {{ $devis->date_validite->format('d/m/Y') }}.
    </div>
</body>
</html>
