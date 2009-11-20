@echo off
title Modification du nom de machine et du SID
@cd c:\
@echo on

@echo ********************************
@echo *      RENOMMAGE DU POSTE      *
@if not exist c:\temp\clone\workgrp.bat goto suite
@echo *    CHANGEMENT DE WORKGROUP   *
:suite
@echo *              ET              *
@echo * MODIFICATION DU SID DU POSTE *
@echo ********************************
@echo .

@if not exist c:\temp\clone\workgrp.bat goto newsid
@call c:\temp\clone\workgrp.bat > NUL
@type c:\temp\clone\workgrp.bat

@if "x%WORKGRP%" == "x" goto newsid
@echo .
@echo Modification du groupe de travail...
@echo .
rem c:\temp\clone\netdom member \\%COMPUTERNAME% /joinworkgroup %WORKGRP%
call c:\temp\clone\rejoin_workgrp2.vbs %WORKGRP%


:newsid
@echo .
@call c:\temp\clone\nompc.bat > NUL
@type c:\temp\clone\nompc.bat

@echo .
@echo L operation de changement de SID est assez longue...
@echo Soyez patient...
@echo .

@if "x%NOMPC%" == "x" goto fin
c:\temp\clone\newsid /a %NOMPC%

:fin
