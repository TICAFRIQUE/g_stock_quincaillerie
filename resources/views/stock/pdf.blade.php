<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>État du stock</title>
    <style>
        /* Feuille de style volontairement autonome (pas de Bootstrap) : dompdf,
           dont le support CSS est limité — tables et boîtes simples uniquement. */
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #241e19;
            margin: 0;
            padding: 28px;
        }
        table { border-collapse: collapse; width: 100%; }
        .text-end { text-align: right; }
        .titre { font-size: 20px; font-weight: bold; color: #e8590c; letter-spacing: 1px; margin-bottom: 4px; }
        .meta { font-size: 11px; color: #555; margin-bottom: 16px; }
        table.lignes th, table.lignes td { border: 1px solid #ccc; padding: 5px 7px; font-size: 11px; }
        table.lignes th { background: #f0ece6; text-align: left; }
        .sous-seuil { color: #b02a37; font-weight: bold; }
    </style>
</head>
<body>
    <div class="titre">État du stock</div>
    <div class="meta">
        Édité le {{ now()->format('d/m/Y à H:i') }}
        @if ($filtres['magasin']) — Destination : {{ $filtres['magasin'] }} @endif
        @if ($filtres['produit']) — Produit : {{ $filtres['produit'] }} @endif
        @if ($filtres['sousSeuil']) — Sous le seuil d'alerte uniquement @endif
    </div>

    <table class="lignes">
        <thead>
            <tr>
                <th>Produit</th>
                <th>SKU</th>
                <th>Destination</th>
                <th class="text-end">Quantité</th>
                <th class="text-end">Seuil d'alerte</th>
                <th class="text-end">Prix de vente</th>
                <th class="text-end">Coût moyen pondéré</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stocks as $stock)
                @php
                    $sousSeuil = $stock->quantite <= $stock->produit->seuil_alerte;
                    $repartition = $stock->produit->repartirQuantite($stock->quantite);
                @endphp
                <tr class="{{ $sousSeuil ? 'sous-seuil' : '' }}">
                    <td>{{ $stock->produit->libelle_affichage }}</td>
                    <td>{{ $stock->produit->sku }}</td>
                    <td>{{ $stock->magasin->nom }}</td>
                    <td class="text-end">
                        {{ $stock->quantite }} {{ $stock->produit->unite_base_libelle_complet }}
                        @if ($repartition)
                            <br><span style="font-style: italic; font-size: 9px; color: #666;">
                                dont
                                @if ($repartition['reste'] > 0)
                                    {{ $repartition['reste'] }} {{ $stock->produit->unite_base_libelle_complet }} et
                                @endif
                                {{ $repartition['nombre'] }} {{ $repartition['unite']->libelle }}
                            </span>
                        @endif
                    </td>
                    <td class="text-end">{{ $stock->produit->seuil_alerte }}</td>
                    <td class="text-end">{{ montant($stock->produit->prix_piece) }}</td>
                    <td class="text-end">{{ montant($stock->cout_moyen_pondere) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-end">Aucun stock enregistré.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
