@echo off
title Passage en client DHCP avant clonage

@rem On passe en client DHCP pour �viter l affichage de conflits IP apres clonage.
@rem Ce passage est temporaire et ne pr�juge pas du choix DHCP ou IP fixe apres clonage

@echo on
@echo PASSAGE EN CLIENT DHCP
netsh interface ip set address "Connexion au r�seau local" dhcp
@rem pause
