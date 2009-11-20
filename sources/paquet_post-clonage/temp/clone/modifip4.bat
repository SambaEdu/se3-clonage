@echo off
title Modification de la configuration IP
@cd c:\
@echo on

@echo *******************************
@echo * MODIF DES PARAMETRES RESEAU *
@echo *******************************
@echo .
@echo *****************************
@echo * DEFINITION DES CONSTANTES *
@echo *****************************
@echo .

@call C:\temp\clone\params.bat > NUL
@type C:\temp\clone\params.bat

if exist C:\temp\clone\temoin_dhcp.txt set DHCP=oui

@echo .
@echo ********************************
@echo * RECUPERATION DE L ADRESSE IP *
@echo ********************************
@echo .

@call C:\temp\clone\ip.bat > NUL
@type C:\temp\clone\ip.bat


@echo .
@echo *****************
@echo * MODIFICATIONS *
@echo *****************
@echo .

@echo Les operations de changement d'IP, DNS,...
@echo sont assez longues.
@echo Soyez patient...
@echo .

@if "%DHCP%" == "oui" goto dhcp
@goto suite
:dhcp
@echo PASSAGE EN CLIENT DHCP
netsh interface ip set address "Connexion au r‚seau local" dhcp
@echo ========================================================
@goto suite1

:suite
@rem Si l'IP ou le masque est vide, on ne change pas l'IP.
@rem Donc on reste en DHCP puisque le dispositif passe le modèle en DHCP avant le clonage
@if "x%IP%" == "x" goto suite1
@if "x%NETMASK%" == "x" goto suite1
@echo MODIFICATION IP
@if "%GW%" == "none" goto suite0
netsh interface ip set address "Connexion au r‚seau local" static %IP% %NETMASK% %GW% 1 
@echo ========================================================
@goto suite1
:suite0
netsh interface ip set address "Connexion au r‚seau local" static %IP% %NETMASK% %GW%
@echo ========================================================

:suite1
@if "x%DNS1%" == "x" goto suite2
@echo MODIFICATION DNS
netsh interface ip delete dns "Connexion au r‚seau local" all
@echo ========================================================
@if "%DNS1%" == "aucun" goto suite2
netsh interface ip set dns "Connexion au r‚seau local" static %DNS1%
@rem netsh interface ip add dns "Connexion au r‚seau local" %DNS2% %RANG_eventuel% 
@echo ========================================================


:suite2
@if "x%WINS%" == "x" goto suite3
@echo MODIFICATION WINS
netsh interface ip delete wins "Connexion au r‚seau local" all
@echo ========================================================
@if "%WINS%" == "aucun" goto suite3
netsh interface ip set wins "Connexion au r‚seau local" static %WINS%
call c:\temp\clone\corrige_options_wins.exe
@echo ========================================================


:suite3
@echo .
@echo *****************
@echo * RECAPITULATIF *
@echo *****************
@echo .
ipconfig /all

@rem pause
