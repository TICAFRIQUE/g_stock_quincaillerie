' Lance demarrer.bat entierement masque : aucune fenetre Laragon, aucune
' invite de commande visible - juste le navigateur qui s'ouvre au final.
' C'est ce fichier qu'il faut viser depuis le raccourci sur le Bureau du
' client (pas demarrer.bat directement, qui reste utile pour deboguer en
' mode visible depuis le Terminal Laragon si besoin).

Set objFSO = CreateObject("Scripting.FileSystemObject")
dossier = objFSO.GetParentFolderName(WScript.ScriptFullName)

Set objShell = CreateObject("WScript.Shell")
objShell.Run "cmd /c """ & dossier & "\demarrer.bat""", 0, False
