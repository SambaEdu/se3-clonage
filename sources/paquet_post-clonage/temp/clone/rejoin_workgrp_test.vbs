' D'après le script rejoin_se3_XP.vbs de:
' Sandrine Dangreville matice creteil
' Il s'agit de rejoindre un groupe de travail 'XXX'
' Stephane Boireau AS Bernay/Pont-Audemer (27)
' 26 juillet 2005

'Option Explicit
Dim oWsh 'Windows Script Host Shell object

Set oWsh = CreateObject("WScript.Shell")
Set oWshEnvironment = oWsh.Environment("Process")
Set oWshnet= Wscript.CreateObject("WScript.Network")

strComputer = "."
Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colCompSystems = objWMIService.ExecQuery("SELECT * FROM Win32_ComputerSystem")
For Each objComputer in colCompSystems
    intunjoindomain=objComputer.JoinDomainOrWorkgroup("test","","",0)
next

' Est-ce que cela fonctionne aussi sous WNT/2K?
