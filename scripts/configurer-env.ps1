<#
    Renseigne le .env pour une installation locale chez un client
    (appele par scripts\installer.bat). Edite les cles existantes en
    place, n'en ajoute aucune : la source de verite pour les cles
    disponibles reste .env.example.
#>
param(
    [Parameter(Mandatory = $true)][string]$AppUrl,
    [Parameter(Mandatory = $true)][string]$DbName,
    [Parameter(Mandatory = $true)][string]$DbUser,
    [Parameter(Mandatory = $false)][string]$DbPass = "__NOPASS__"
)

$ErrorActionPreference = "Stop"

# powershell.exe -File avale les arguments "" (chaine vide) passes en ligne de
# commande depuis cmd.exe : installer.bat envoie ce jeton a la place d'un mot
# de passe MySQL vide (cas frequent avec root/Laragon en local).
if ($DbPass -eq "__NOPASS__") {
    $DbPass = ""
}
$envPath = Join-Path (Split-Path -Parent $PSScriptRoot) ".env"

if (-not (Test-Path $envPath)) {
    Write-Error ".env introuvable a $envPath"
    exit 1
}

$valeurs = @{
    "APP_ENV"            = "production"
    "APP_DEBUG"           = "false"
    "APP_URL"             = $AppUrl
    "LOG_LEVEL"           = "error"
    "DB_DATABASE"         = $DbName
    "DB_USERNAME"         = $DbUser
    "DB_PASSWORD"         = $DbPass
    "QUEUE_CONNECTION"    = "sync"
}

$lignes = Get-Content -LiteralPath $envPath

foreach ($cle in $valeurs.Keys) {
    $valeur = $valeurs[$cle]
    $remplace = $false

    for ($i = 0; $i -lt $lignes.Count; $i++) {
        if ($lignes[$i] -match "^$cle=") {
            $lignes[$i] = "$cle=$valeur"
            $remplace = $true
            break
        }
    }

    if (-not $remplace) {
        $lignes += "$cle=$valeur"
    }
}

[System.IO.File]::WriteAllLines($envPath, $lignes, (New-Object System.Text.UTF8Encoding $false))

Write-Host "Fichier .env mis a jour."
