' D'après le script rejoin_se3_XP.vbs de:
' Sandrine Dangreville matice creteil
' Il s'agit de rejoindre un groupe de travail 'XXX'
' Stephane Boireau AS Bernay/Pont-Audemer (27)
' 08/10/2005

'Option Explicit
Dim oWsh 'Windows Script Host Shell object

Set oWsh = CreateObject("WScript.Shell")
Set oWshEnvironment = oWsh.Environment("Process")
Set oWshnet= Wscript.CreateObject("WScript.Network")

Set args = Wscript.Arguments

'testarg=MsgBox("Argument: " & args(0),vbCritical + vbOKCancel + 0,"Titre")

if Wscript.Arguments.Count then
    workgroup=args(0)
else
    workgroup="test"
End If

strComputer = "."

'if PN="Microsoft Windows XP" then
	Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
	Set colCompSystems = objWMIService.ExecQuery("SELECT * FROM Win32_ComputerSystem")
	For Each objComputer in colCompSystems
		'intunjoindomain=objComputer.UnjoinDomainOrWorkgroup(sAdminPwd,sAdminName,0)
		intunjoindomain=objComputer.UnjoinDomainOrWorkgroup("","",0)
	next
'end if


Set objWMIService = GetObject("winmgmts:{impersonationLevel=impersonate}!\\" & strComputer & "\root\cimv2")
Set colCompSystems = objWMIService.ExecQuery("SELECT * FROM Win32_ComputerSystem")
For Each objComputer in colCompSystems
    'Ca ne passe pas avec args(0)
    'intunjoindomain=objComputer.JoinDomainOrWorkgroup(args(0),"","",0)
    intunjoindomain=objComputer.JoinDomainOrWorkgroup(workgroup,"","",0)
next

' Est-ce que cela fonctionne aussi sous WNT/2K?
