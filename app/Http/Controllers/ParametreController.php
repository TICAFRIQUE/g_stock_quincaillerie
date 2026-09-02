<?php

namespace App\Http\Controllers;

use App\Models\Devise;
use App\Models\Parametre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessExceptionInterface;
use Symfony\Component\Process\Process;

class ParametreController extends Controller
{
    public function edit(): View
    {
        return view('parametres.edit', [
            'parametre' => Parametre::actuel(),
            'devises' => Devise::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'duree_validite_devis_jours' => ['required', 'integer', 'min:1', 'max:365'],
            'devise_id' => ['nullable', 'exists:devises,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $parametre = Parametre::actuel();
        unset($donnees['logo']);
        $parametre->update($donnees);

        if ($request->hasFile('logo')) {
            $parametre->addMediaFromRequest('logo')->toMediaCollection('logo');
            Parametre::invaliderCache();
        }

        return redirect()->route('parametres.edit')->with('succes', 'Paramètres mis à jour.');
    }

    /**
     * Sauvegarde à la demande : dump mysqldump complet, streamé vers un
     * fichier temporaire (jamais gardé entièrement en mémoire) puis proposé
     * en téléchargement. Le mot de passe passe par une variable d'env du
     * sous-processus (MYSQL_PWD), jamais en argument de ligne de commande,
     * pour ne pas apparaître dans la liste des processus du serveur.
     */
    public function backup(): mixed
    {
        $connexion = config('database.connections.'.config('database.default'));

        if (($connexion['driver'] ?? null) !== 'mysql') {
            return back()->with('erreur', 'Sauvegarde uniquement disponible pour une base MySQL.');
        }

        $fichierTemporaire = tempnam(sys_get_temp_dir(), 'backup_');

        $commande = [
            'mysqldump',
            '-h', (string) $connexion['host'],
            '-P', (string) $connexion['port'],
            '-u', (string) $connexion['username'],
            '--single-transaction',
            '--routines',
            (string) $connexion['database'],
        ];

        // Symfony Process remplace tout l'environnement du sous-processus quand
        // $env est fourni : sans PATH/SystemRoot hérités, mysqldump.exe échoue
        // à initialiser Winsock sous Windows. On fusionne donc avec l'environnement
        // courant plutôt que de le remplacer.
        $env = getenv() + ['MYSQL_PWD' => (string) $connexion['password']];
        $process = new Process($commande, env: $env);
        $process->setTimeout(null);

        try {
            $handle = fopen($fichierTemporaire, 'w');
            $process->run(function (string $type, string $buffer) use ($handle) {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });
            fclose($handle);

            if (! $process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput() ?: 'mysqldump a échoué sans message d\'erreur.');
            }
        } catch (ProcessExceptionInterface|\RuntimeException $e) {
            @unlink($fichierTemporaire);
            Log::error('Échec de la sauvegarde base de données : '.$e->getMessage());

            return back()->with('erreur', "La sauvegarde a échoué : mysqldump n'est pas disponible ou a rencontré une erreur. Vérifiez sa présence sur le serveur.");
        }

        $nomFichier = 'sauvegarde-'.now()->format('Y-m-d-His').'.sql';

        $reponse = response()->download($fichierTemporaire, $nomFichier)->deleteFileAfterSend(true);

        // Un téléchargement de fichier ne recharge jamais la page : le spinner
        // générique du bouton (voir app.js) ne se réinitialiserait donc jamais
        // tout seul. Ce cookie, sondé côté client (document.cookie), sert de
        // signal de fin — httpOnly: false est indispensable, sinon le JS ne
        // peut pas le lire du tout (valeur par défaut de Cookie : true).
        $reponse->headers->setCookie(new Cookie(
            name: 'telechargement_pret',
            value: '1',
            expire: time() + 60,
            path: '/',
            httpOnly: false,
        ));

        return $reponse;
    }
}
