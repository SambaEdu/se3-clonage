' D'après le script rejoin_se3_XP.vbs de:
' Sandrine Dangreville matice creteil
' Il s'agit de supprimer le compte adminse3
' Stephane Boireau AS Bernay/Pont-Audemer (27)
' 08 juillet 2005

'Option Explicit
Dim oWsh 'Windows Script Host Shell object
Dim oWshEnvironment 'Windows Script Host environment object

Dim OpSysSet, OpSys
Set OpSysSet = GetObject("winmgmts:{(Shutdown)}//./root/cimv2").ExecQuery("select * from Win32_OperatingSystem where Primary=true")
For Each OpSys In OpSysSet
	OpSys.Reboot()
Next
WScript.Quit
