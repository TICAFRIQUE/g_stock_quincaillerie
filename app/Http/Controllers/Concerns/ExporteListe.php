<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export PDF/Excel partagé par toutes les listes et rapports de l'app —
 * un seul gabarit PDF générique (resources/views/exports/liste.blade.php),
 * une seule implémentation Excel : chaque contrôleur ne fournit que ses
 * en-têtes de colonnes et ses lignes déjà formatées (chaînes/nombres),
 * jamais de logique métier dupliquée (voir CLAUDE.md — rapports = agrégats,
 * sans dupliquer la logique).
 */
trait ExporteListe
{
    /**
     * @param  array<int, string>  $entetes
     * @param  iterable<int, array<int, string|int|float|null>>  $lignes
     */
    protected function pdfDepuisListe(string $titre, array $entetes, iterable $lignes, string $nomFichier, ?string $sousTitre = null): Response
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.liste', [
            'titre' => $titre,
            'sousTitre' => $sousTitre,
            'entetes' => $entetes,
            'lignes' => $lignes,
        ]);

        return $pdf->download($nomFichier);
    }

    /**
     * @param  array<int, string>  $entetes
     * @param  iterable<int, array<int, string|int|float|null>>  $lignes
     */
    protected function excelDepuisListe(string $titre, array $entetes, iterable $lignes, string $nomFichier): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $feuille = $spreadsheet->getActiveSheet();
        // Un titre de feuille Excel n'accepte ni \ / ? * [ ] ni plus de 31
        // caractères (ex. "Casse / pertes" fait échouer setTitle() sinon).
        $feuille->setTitle(mb_substr(str_replace(['\\', '/', '?', '*', '[', ']'], '-', $titre), 0, 31));
        $feuille->fromArray($entetes, null, 'A1');

        $ligneIndex = 2;
        foreach ($lignes as $ligne) {
            $feuille->fromArray(array_values($ligne), null, 'A'.$ligneIndex);
            $ligneIndex++;
        }

        $derniereColonne = $this->lettreColonne(count($entetes));
        foreach (range('A', $derniereColonne) as $colonne) {
            $feuille->getColumnDimension($colonne)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nomFichier, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Conversion 1-indexée vers une lettre de colonne Excel (1 → A, 27 → AA…),
     * pour ne pas se limiter aux listes de moins de 26 colonnes.
     */
    private function lettreColonne(int $index): string
    {
        $lettre = '';
        while ($index > 0) {
            $index--;
            $lettre = chr(65 + ($index % 26)).$lettre;
            $index = intdiv($index, 26);
        }

        return $lettre;
    }
}
