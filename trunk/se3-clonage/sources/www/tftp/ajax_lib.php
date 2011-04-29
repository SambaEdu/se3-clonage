<?php
/* $Id$
===========================================
   Projet SE3
   Dispositif SE3+TFTP+Sauvegarde/Restauration/Clonage
   Stephane Boireau
   Distribué selon les termes de la licence GPL
=============================================
*/

	require ("config.inc.php");
	require_once ("functions.inc.php");
	require_once ("lang.inc.php");
	require_once ("ihm.inc.php");
	require_once ("ldap.inc.php");
	require_once ("fonc_parc.inc.php");
    require_once ("fonc_outils.inc.php");

	require_once ("lib_action_tftp.php");

	//debug_var();
	$login=isauth();

	if (is_admin("system_is_admin",$login)!="Y") {
		echo "<p style='color:red'>Action non autorisee.</p>";
		die();
	}

	//echo "<script type='text/javascript' src='position.js'></script>\n";

	//====================================================

/*	function fping($ip) { // Ping une machine Return 1 si Ok 0 pas de ping
		return exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'");
	}
*/
	//====================================================

	function get_smbsess($mp_en_cours) {
		global $smbversion;
		//echo "\$smbversion=$smbversion<br />";

		// Initialisation
		$etat_session="";

		if ("$smbversion"=="samba3") {
			$smbsess=exec ("smbstatus |gawk -F' ' '{print \" \"$5\" \"$4\" \"}' |grep ' $mp_en_cours ' |cut -d' ' -f2 |head -n1");
			//echo "smbstatus |gawk -F' ' '{print \" \"$5\" \"$4\" \"}' |grep ' $mp_en_cours ' |cut -d' ' -f2 |head -n1";
		}
		else {
			$smbsess=exec ("smbstatus |gawk -F' ' '{print \" \"$5\" \"$4}' |grep ' $mp_en_cours ' |cut -d' ' -f3 |head -n1");
		}

		if ($smbsess=="") {
			$etat_session="<img type=\"image\" src=\"../elements/images/disabled.gif\">\n";
		} else {
			if ("$smbversion"=="samba3") {
				$login = exec ("smbstatus | grep -v 'root' |gawk -F' ' '{print \" \"$5\" \"$2}' |grep ' $smbsess ' |cut -d' ' -f3 |head -n1");
			}
			else {
				$login = exec ("smbstatus | grep -v 'root' |gawk -F' ' '{print \" \"$4\" \"$2}' |grep ' $smbsess ' |cut -d' ' -f3 |head -n1");
			}

			$texte="$login est actuellement connect&eacute; sur ce poste";
			//$etat_session.="<img src=\"../elements/images/enabled.gif\" border='0' alt='$texte' title='$texte' />\n";
			$etat_session.="<img src=\"../elements/images/enabled.gif\" border=\"0\" alt=\"$texte\" title=\"$texte\" />";

		}
		echo $etat_session;
	}

	//====================================================

	//ip=$ip_machine&nom=$nom_machine&mode=wake_shutdown_or_reboot&wake=$wake&shutdown_reboot=$shutdown_reboot
	function wake_shutdown_or_reboot($ip,$nom,$wake,$shutdown_reboot) {
		global $smbversion;
		//echo "\$smbversion=$smbversion<br />";

		/*
		echo "ip=$ip<br />";
		echo "nom=$nom<br />";
		echo "wake=$wake<br />";
		echo "shutdown_reboot=$shutdown_reboot<br />";
		*/

		if(fping($ip)) {
			if($shutdown_reboot=="wait1") {
				echo "Aucun signal envoy&eacute; (<i>il faudra rebooter manuellement</i>).";
			}
			elseif($shutdown_reboot=="wait2") {
				if ("$smbversion"=="samba3") {
					$smbsess=exec ("smbstatus |gawk -F' ' '{print \" \"$5\" \"$4\" \"}' |grep ' $nom ' |cut -d' ' -f2 |head -n1");
				}
				else {
					$smbsess=exec ("smbstatus |gawk -F' ' '{print \" \"$5\" \"$4}' |grep ' $nom ' |cut -d' ' -f3 |head -n1");
				}

				if($smbsess=="") {
					@start_poste("reboot", $nom);
					echo "Signal de reboot envoy&eacute;.";
				}
				else {
					if ("$smbversion"=="samba3") {
						$login = exec ("smbstatus | grep -v 'root' |gawk -F' ' '{print \" \"$5\" \"$2}' |grep ' $smbsess ' |cut -d' ' -f3 |head -n1");
					}
					else {
						$login = exec ("smbstatus | grep -v 'root' |gawk -F' ' '{print \" \"$4\" \"$2}' |grep ' $smbsess ' |cut -d' ' -f3 |head -n1");
					}
					echo "$login est actuellement connect&eacute;.";
				}
			}
			elseif($shutdown_reboot=="reboot") {
				@start_poste("reboot", $nom);
				echo "Signal de reboot envoy&eacute;.";
			}
		}
		else {
			if("$wake"=="y") {
				@start_poste("wol", $nom);
				echo "Signal de r&eacute;veil envoy&eacute;.";
			}
		}
	}

	//====================================================

	if($_GET['mode']=='ping_ip'){
		$resultat=fping($_GET['ip']);
		if($resultat){
			//echo "<img type=\"image\" src=\"../elements/images/enabled.gif\" border='0' alt='".$_GET['ip']."' title='".$_GET['ip']."' />";
			echo "<img type=\"image\" src=\"../elements/images/enabled.gif\" border=\"0\" alt=\"".$_GET['ip']."\" title=\"".$_GET['ip']."\" />";
		}
		else{
			//echo "<img type=\"image\" src=\"../elements/images/disabled.gif\" border='0' alt='".$_GET['ip']."' title='".$_GET['ip']."' />";
			echo "<img type=\"image\" src=\"../elements/images/disabled.gif\" border=\"0\" alt=\"".$_GET['ip']."\" title=\"".$_GET['ip']."\" />";
		}
	}
	elseif($_GET['mode']=='session') {
		get_smbsess($_GET['nom_machine']);
	}
	elseif($_GET['mode']=='wake_shutdown_or_reboot') {
		wake_shutdown_or_reboot($_GET['ip'],$_GET['nom'],$_GET['wake'],$_GET['shutdown_reboot']);
	}
	elseif($_GET['mode']=='check_versions_sysresccd') {
		$resultat2=exec("/usr/bin/sudo /usr/share/se3/scripts/se3_get_sysresccd.sh 'check_version'", $retour);
		foreach($retour as $key => $value) {
			//echo "\$retour[$key]=$value<br />";
			echo $value;
		}
	}

	//====================================================
?>
