<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * A executer une seule fois, juste apres avoir migre une installation qui
 * existait avant le passage a la connexion par nom d'utilisateur (migration
 * 2026_08_17_100001_add_username_to_users_table) : les comptes crees avant
 * cette migration ont un username NULL et ne peuvent plus se connecter tant
 * qu'il n'est pas renseigne. Ne touche jamais au mot de passe/code existant.
 */
#[Signature('app:definir-usernames-manquants')]
#[Description("Genere un nom d'utilisateur pour les comptes existants qui n'en ont pas encore")]
class DefinirUsernamesManquants extends Command
{
    public function handle(): int
    {
        $utilisateurs = User::whereNull('username')->get();

        if ($utilisateurs->isEmpty()) {
            $this->info('Aucun compte a completer, tous ont deja un nom d\'utilisateur.');

            return self::SUCCESS;
        }

        foreach ($utilisateurs as $utilisateur) {
            $base = Str::slug($utilisateur->name, '');
            $base = $base !== '' ? $base : 'utilisateur';
            $username = $base;
            $suffixe = 1;

            while (User::where('username', $username)->exists()) {
                $suffixe++;
                $username = $base.$suffixe;
            }

            $utilisateur->update(['username' => $username]);
            $this->line("{$utilisateur->name} -> {$username}");
        }

        $this->info($utilisateurs->count().' compte(s) complete(s). Le code de connexion de chacun ne change pas : communiquez juste le nouveau nom d\'utilisateur affiche ci-dessus.');

        return self::SUCCESS;
    }
}
