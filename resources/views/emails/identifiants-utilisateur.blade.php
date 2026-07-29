<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #212529; background: #f4f6f4; padding: 24px;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 24px;">
        <h2 style="color: #163126; margin-top: 0;">{{ $parametre->nom }}</h2>

        <p>Bonjour {{ $utilisateur->name }},</p>

        @if ($nouveauCompte)
            <p>Un compte vient d'être créé pour vous sur {{ $parametre->nom }}. Voici vos identifiants de connexion :</p>
        @else
            <p>Votre mot de passe a été réinitialisé. Voici vos nouveaux identifiants :</p>
        @endif

        <table style="width: 100%; margin: 16px 0; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; color: #6c757d;">E-mail</td>
                <td style="padding: 8px 0; font-weight: bold;">{{ $utilisateur->email }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6c757d;">Mot de passe</td>
                <td style="padding: 8px 0; font-weight: bold; font-family: monospace;">{{ $motDePasse }}</td>
            </tr>
        </table>

        <p style="color: #6c757d; font-size: 0.9em;">Pour votre sécurité, nous vous recommandons de changer ce mot de passe après votre première connexion.</p>
    </div>
</body>
</html>
