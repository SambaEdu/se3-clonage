rem Ajouter un test sur l'OS...
if "%OS%"=="Windows_NT" goto xp

:w9x
rem arret au lieu de reboot:
rem RUNDLL32.EXE User.exe,ExitWindows

rem rundll32.exe shell32.dll,SHExitWindowsEx n
rem ou 'n' peut valloir:
rem 0 - LOGOFF
rem 1 - SHUTDOWN
rem 2 - REBOOT
rem 4 - FORCE
rem 8 - POWEROFF
rem (en combinant: 6 = 2+4 FORCE REBOOT)

rundll32.exe shell32.dll,SHExitWindowsEx 6
goto fin

:xp
%windir%\System32\SHUTDOWN.exe -r -t 0

:fin
