; Programme destiné à quitter un domaine SambaEdu3 sur des machines NT/2K/XP
; (testé uniquement sous XP (y a-t-il des modifs à apporter sur les VBS dans les cas NT/2K?))
; Les opérations effectuées sont les suivantes:
;  - Suppression du compte 'adminse3'
;  - Suppression du dossier "c:\Documents and Settings\adminse3"
;    (pour éviter lors d'une intégration future l'apparition d'un dossier "c:\Documents and Settings\adminse3.NOMDUPOSTE")
;  - Suppression des fichiers de wpkg
;  - Suppression du dossier "c:\netinst"
;  - Sortie du domaine avec NETDOM
;  - Rejoindre le groupe de travail 'TEST'
;  - Reboot.

; $Id$
; Stephane Boireau (27)
; Modification: 13/06/2010

$DEFAULT_WORKGROUP = "TEST"

Dim $WPKG_files[4]=["wpkg-client.vbs", "wpkg.log", "wpkg.txt", "system32\wpkg.xml"]

If @OSTYPE == "WIN32_WINDOWS" Then
	MsgBox(0,"Information","Ce programme concerne les OS NT/2K/XP uniquement.")
	Exit
Else
	If IsAdmin() Then
		If @UserName == "adminse3" Then
			MsgBox(0,"Information","Ce programme ne doit pas être exécuté" & @CRLF & "en tant que 'adminse3' puisque le script" & @CRLF & "va tenter de supprimer ce compte.")
			Exit
		Else

			;=================================================================================
			;$nbmax=25
			; Compte des dossiers dans Documents and Settings
			$DOSSIER="C:\Documents and Settings"
			; Recherche des dossiers dans $DOSSIER
			; (remarque: "." et ".." sont renvoyés).
			$recherche = FileFindFirstFile($DOSSIER & "\*.*")

			; Si la recherche a renvoyé des réponses:
			If $recherche = -1 Then
				; Cela ne peut pas se produire.
				MsgBox(0, "Erreur", "Aucun fichier/dossier ne se trouve dans " & $DOSSIER)
			EndIf

			$nbmax = 1
			While 1
				$dossier_courant = FileFindNextFile($recherche)
				If @error Then ExitLoop
				$nbmax = $nbmax + 1
			WEnd
			;=================================================================================

			;MsgBox(0,"INFO","$nbmax = " & $nbmax)

			; Pour tenir compte de . et .. ???
			$nbmax = $nbmax + 2

			;Tableau de $nbmax choix:
			;$nbmax=100
			Dim $DOSSIER_TMP_HOME[$nbmax]
			Dim $DOSSIER_HOME[$nbmax]

			$DOSSIER="C:\Documents and Settings"
			; Recherche des dossiers dans $DOSSIER
			; (remarque: "." et ".." sont renvoyés).
			$recherche = FileFindFirstFile($DOSSIER & "\*.*")

			; Si la recherche a renvoyé des réponses:
			If $recherche = -1 Then
				MsgBox(0, "Erreur", "Aucun fichier/dossier ne se trouve dans " & $DOSSIER)
				;Exit
			EndIf

			$j = 1
			$i = 1
			While 1
				$DOSSIER_TMP_HOME[$i] = FileFindNextFile($recherche)
				If @error Then ExitLoop

				;MsgBox(0,"Info",$DOSSIER_TMP_HOME[$i])
				If $DOSSIER_TMP_HOME[$i] == "." OR $DOSSIER_TMP_HOME[$i] == ".." OR $DOSSIER_TMP_HOME[$i] == @UserName OR $DOSSIER_TMP_HOME[$i] == "All Users" OR $DOSSIER_TMP_HOME[$i] == "Default User" OR $DOSSIER_TMP_HOME[$i] == "LocalService" OR $DOSSIER_TMP_HOME[$i] == "NetworkService" Then
					$bidon = "On n'affiche pas."
				Else
					$DOSSIER_HOME[$j] = $DOSSIER_TMP_HOME[$i]
					;MsgBox(0,"Info","$DOSSIER_HOME[" & $j & "] = " & $DOSSIER_HOME[$j])
					$j = $j + 1
				EndIf
				$i = $i + 1
			WEnd

			; Fin de la recherche
			; (Close the search handle)
			FileClose($recherche)

			$nbdossiers = $j - 1




			;Include constants
			#include <GUIConstants.au3>

			;Initialize variables
			Global $GUIWidth
			Global $GUIHeight

			$GUIWidth = 400
			;$GUIHeight = 330
			$GUIHeight = 40 + $nbdossiers * 20 + 25 + 25 + 10

            If $GUIHeight < 170 Then
				$GUIHeight = 170
			EndIf

            If $GUIHeight > @DesktopHeight Then
				$GUIHeight = @DesktopHeight - 50
			EndIf

			; Et si le nombre de dossiers est tel que la fenêtre dépasse la hauteur de l'écran?
			; A FAIRE: Ajouter un test.




			$FENETRE="Quitte SambaEdu3"
			; Création de la fenêtre
			GUICreate($FENETRE, $GUIWidth, $GUIHeight)

			; Affichage d'un titre:
			;$Label_info = GuiCtrlCreateLabel(" Choix des dossiers à supprimer dans 'C:\Documents and Settings'", 10, 10, 325, 20, $SS_SUNKEN)
			$Label_info = GuiCtrlCreateLabel(" Choix des dossiers à supprimer dans 'C:\Documents and Settings'", 10, 10, 325, 20)


			$x0=20
			$x1=35
			;$x2=150
			$x2=200

			$y0=42
			$y1=40

			; ========================
			$Check_quitte_se3 = GUICtrlCreateCheckbox("",$x2,$y1,10,10)
			$x3=$x2+15;
			$Label_quitte_se3 = GuiCtrlCreateLabel("Quitter le domaine", $x3, $y1, 130, 20)
			ControlCommand($FENETRE,"",$Check_quitte_se3,"Check","")
			; ========================

			$Label_WORKGROUP = GuiCtrlCreateLabel("Nom du Workgroup à rejoindre:", $x2, $y1+20, 180, 20)
			$Champ_WORKGROUP = GUICtrlCreateInput($DEFAULT_WORKGROUP, $x2, $y1+40, 180, 20)
			$Label_WORKGROUP_INFO = GuiCtrlCreateLabel("(15 caractères maxi parmi A-Z 0-9 et -)", $x2, $y1+65, 180, 20)





			;$x=($GUIWidth-2*70-35)/2
			$x=$x2+($GUIWidth-2*70-$x2-15)/2-10

			; Création du bouton "OK"
			;$OK_Btn = GUICtrlCreateButton("OK", 75, 165, 70, 25)
			$OK_Btn = GUICtrlCreateButton("OK", $x, $y1+85, 70, 25)


			$x=$x+70+15

			; Création du bouton "CANCEL"
			;$Cancel_Btn = GUICtrlCreateButton("Cancel", 165, 165, 70, 25)
			$Cancel_Btn = GUICtrlCreateButton("Cancel", $x, $y1+85, 70, 25)







			;Tableau de $nbmax choix:
			;$nbmax=25
			Dim $Check_DOSSIER[$nbmax]
			Dim $Label_DOSSIER[$nbmax]


			$i = 1
			For $i = 1 To $nbdossiers
				$Check_DOSSIER[$i] = GUICtrlCreateCheckbox("",$x0,$y0,10,10)
				;$Label_DOSSIER[$i] = GuiCtrlCreateLabel($DOSSIER_HOME[$i], $x1, $y1, 80, 20)
				$Label_DOSSIER[$i] = GuiCtrlCreateLabel($DOSSIER_HOME[$i], $x1, $y1, 130, 20)
				If $DOSSIER_HOME[$i] == "adminse3" Then
					ControlCommand($FENETRE,"",$Check_DOSSIER[$i],"Check","")
				EndIf
				;MsgBox(0,"Info","$DOSSIER_HOME[" & $i & "] = " & $DOSSIER_HOME[$i])
				$y0=$y1+20
				$y1=$y0-2
			Next


			$y0=$y1+25
			;MsgBox(0,"Info","$y0=" & $y0)
			If $y0 < 135 then
				$y0 = 135
			EndIf



			;$x=($GUIWidth-2*70-35)/2

			; Création du bouton "OK"
			;$OK_Btn = GUICtrlCreateButton("OK", 75, 165, 70, 25)
			;$OK_Btn = GUICtrlCreateButton("OK", $x, $y0, 70, 25)


			;$x=$x+70+35

			; Création du bouton "CANCEL"
			;$Cancel_Btn = GUICtrlCreateButton("Cancel", 165, 165, 70, 25)
			;$Cancel_Btn = GUICtrlCreateButton("Cancel", $x, $y0, 70, 25)




			; On rend la fenêtre visible (modification de statut)
			GUISetState(@SW_SHOW)

			; On fait une boucle jusqu'à ce que:
			; - l'utilisateur presse ESC
			; - l'utilisateur presse ALT+F4
			; - l'utilisateur clique sur le bouton de fermeture de la fenêtre
			While 1
				; Après chaque boucle, on contrôle si l'utilisateur a cliqué sur quelque chose
				$msg = GUIGetMsg()

				Select
					; On teste si l'utilisateur a cliqué sur le bouton de fermeture
					Case $msg = $GUI_EVENT_CLOSE
						; On détruit la fenêtre et ses contrôles
						GUIDelete()
						; Et on quitte le script
						Exit

					; On teste si l'utilisateur a cliqué sur le bouton OK
					Case $msg = $OK_Btn

						$quitte_domaine=GUICtrlRead($Check_quitte_se3)

						$DOSSIER_TMP = "C:\tmp"
						DirCreate($DOSSIER_TMP)
						$FICH = FileOpen($DOSSIER_TMP & "\quitte_se3.bat",2)
						If $quitte_domaine == 1 Then
							FileWriteLine($FICH,"cd " & @ScriptDir & "\outils" & @CRLF)
							FileWriteLine($FICH,"call suppr_adminse3.vbs" & @CRLF)
						EndIf

						;=======================================================
						; Récupération du contenu des champs de formulaire

						For $i = 1 To $nbdossiers
							$suppr_ou_pas = GUICtrlRead($Check_DOSSIER[$i])
							If $suppr_ou_pas == 1 Then
								FileWriteLine($FICH,"rmdir ""c:\Documents and Settings\" & $DOSSIER_HOME[$i] & """ /q /s" & @CRLF)
							EndIf
						Next

						If $quitte_domaine == 1 Then
							FileWriteLine($FICH,"rmdir ""c:\netinst"" /q /s" & @CRLF)

							;FileWriteLine($FICH,"Set sCurrentName=%COMPUTERNAME%" & @CRLF)
							;FileWriteLine($FICH,"Set sDomainOld=SAMBAEDU3" & @CRLF)
							;FileWriteLine($FICH,"NETDOM.EXE REMOVE %sCurrentName% /D:%sDomainOld%" & @CRLF)
						EndIf

						If $quitte_domaine == 1 Then
							For $i=0 To UBound($WPKG_files) - 1
								;MsgBox(0,"Info","$WPKG_files["&$i&"]=" & $WPKG_files[$i])
								If FileExists(@WindowsDir & "\" & $WPKG_files[$i]) Then
									If FileRecycle(@WindowsDir & "\" & $WPKG_files[$i]) Then
										MsgBox(0,"Information","Suppression de " & @WindowsDir & "\" & $WPKG_files[$i] & " réussie.",1)
									Else
										MsgBox(0,"Erreur","La suppression de " & @WindowsDir & "\" & $WPKG_files[$i] & " a échoué.")
									EndIf
									; On pourrait ajouter un test sur le fait qu'un processus utilise le fichier... ou tester la clé Running...
								EndIf
							Next

							$LISTE_MENAGE_FICHIERS=@ScriptDir & "\outils\liste_menage.txt"
							If FileExists($LISTE_MENAGE_FICHIERS) Then
								$FICH_MENAGE=FileOpen($LISTE_MENAGE_FICHIERS,0)
								If $FICH_MENAGE = -1 Then
									MsgBox(0, "Erreur", "Il n'a pas été possible d'ouvrir le fichier" & @CRLF & "'" & $LISTE_MENAGE_FICHIERS & "'!")
								Else
									While 1
										$LIGNE = FileReadLine($FICH_MENAGE)
										If @error = -1 Then ExitLoop
										If $LIGNE <> "" And FileExists($LIGNE) Then
											If FileRecycle($LIGNE) Then
												MsgBox(0,"Information","Suppression de " & $LIGNE & " réussie.",1)
											Else
												MsgBox(0,"Erreur","La suppression de " & $LIGNE & " a échoué.")
											EndIf
										EndIf
									WEnd
								EndIf
							Else
								MsgBox(0, "Info", "Aucun fichier optionnel de ménage de fichiers " & @CRLF & "'" & $LISTE_MENAGE_FICHIERS & "' n'a été trouvé!")
							EndIf
						EndIf
						

						$NOM_WORKGROUP = GUICtrlRead($Champ_WORKGROUP)
						If $NOM_WORKGROUP == "" Then
							; On ne fait rien
							$bidon="oui"
						Else
							FileWriteLine($FICH,"cd " & @ScriptDir & "\outils" & @CRLF)
							If StringIsAlNum(StringReplace(StringReplace($NOM_WORKGROUP,"-",""),"_","")) AND StringLen($NOM_WORKGROUP)<16 Then
								FileWriteLine($FICH,"call rejoin_workgrp2.vbs " & $NOM_WORKGROUP & @CRLF)
							Else
								FileWriteLine($FICH,"call rejoin_workgrp2.vbs " & $DEFAULT_WORKGROUP & @CRLF)
							EndIf

							; Faire rebooter l'OS:
							FileWriteLine($FICH,"call reboot.vbs" & @CRLF)
						EndIf

						;FileWriteLine($FICH,"" & @CRLF)

						FileClose($FICH)

						RunWait($DOSSIER_TMP & "\quitte_se3.bat")

						; La suppression ne fonctionne pas.
						; Le reboot est précédé d'une fermeture des applis en cours...
						;FileRecycle($DOSSIER_TMP & "\quitte_se3.bat")
						;FileRecycleEmpty()

						GUIDelete()
						ExitLoop


					; On teste si l'utilisateur a cliqué sur le bouton CANCEL
					Case $msg = $Cancel_Btn
						MsgBox(64, "Abandon!", "Vous avez souhaité abandonner l'opération.")
						GUIDelete()
						Exit
				EndSelect
			WEnd
		EndIf
	Else
		MsgBox(0,"Information","Ce programme doit être exécuté" & @CRLF & "avec les droits administrateur.")
		Exit
	EndIf
EndIf

Exit
