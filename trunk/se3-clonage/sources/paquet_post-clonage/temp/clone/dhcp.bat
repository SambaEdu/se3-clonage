@echo off
title Passage en client DHCP avant clonage

@rem On passe en client DHCP pour éviter l affichage de conflits IP apres clonage.
@rem Ce passage est temporaire et ne préjuge pas du choix DHCP ou IP fixe apres clonage

@echo on
@echo PASSAGE EN CLIENT DHCP
:@call params.bat > NUL
netsh interface ip set address "Connexion au r‚seau local" dhcp
:netsh interface ip set address "%INTERFACE%" dhcp
@rem pause
