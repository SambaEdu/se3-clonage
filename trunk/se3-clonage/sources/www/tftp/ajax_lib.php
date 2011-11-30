<?php

/* $Id$
  ===========================================
  Projet SE3
  Dispositif SE3+TFTP+Sauvegarde/Restauration/Clonage
  Stephane Boireau
  Distribu� selon les termes de la licence GPL
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
//require_once ("parcs_ajax_lib.php");
//debug_var();
$login = isauth();

if ((is_admin("system_is_admin", $login) != "Y") && (ldap_get_right("parc_can_clone", $login) != "Y")) {
	echo "<p style='color:red'>Action non autorisee.</p>";
	die();
}

$restriction_parcs = "n";
if (is_admin("system_is_admin", $login) != "Y") {
	$restriction_parcs = "y";
	$tab_delegated_parcs = list_delegated_parcs($login);
	if (count($tab_delegated_parcs) == 0) {
		echo "<p style='color:red'>Aucun parc ne vous a ete delegue.</p>\n";
		die();
	}
}


if ($_GET['mode'] == 'ping_ip') {
	$resultat = fping($_GET['ip']);
	if ($resultat) {
		//echo "<img type=\"image\" src=\"../elements/images/enabled.gif\" border='0' alt='".$_GET['ip']."' title='".$_GET['ip']."' />";
		echo "<img type=\"image\" src=\"../elements/images/enabled.gif\" border=\"0\" alt=\"" . $_GET['ip'] . "\" title=\"" . $_GET['ip'] . "\" />";
	} else {
		//echo "<img type=\"image\" src=\"../elements/images/disabled.gif\" border='0' alt='".$_GET['ip']."' title='".$_GET['ip']."' />";
		echo "<img type=\"image\" src=\"../elements/images/disabled.gif\" border=\"0\" alt=\"" . $_GET['ip'] . "\" title=\"" . $_GET['ip'] . "\" />";
	}
} elseif ($_GET['mode'] == 'session') {
	$res = get_smbsess($_GET['nom_machine']);
	echo $res['html'];
} elseif ($_GET['mode'] == 'wake_shutdown_or_reboot') {
	wake_shutdown_or_reboot($_GET['ip'], $_GET['nom'], $_GET['wake'], $_GET['shutdown_reboot']);
} elseif ($_GET['mode'] == 'check_versions_sysresccd') {
	$resultat2 = exec("/usr/bin/sudo /usr/share/se3/scripts/se3_get_sysresccd.sh 'check_version'", $retour);
	foreach ($retour as $key => $value) {
		echo $value;
	}
} elseif ($_GET['mode'] == 'check_versions_udpcast') {
	$resultat2 = exec("/usr/bin/sudo /usr/share/se3/scripts/se3_get_udpcast.sh 'check_version'", $retour);
	foreach ($retour as $key => $value) {
		echo $value;
	}
} elseif ($_GET['mode'] == 'check_versions_slitaz') {
	$resultat2 = exec("/usr/bin/sudo /usr/share/se3/scripts/se3_get_slitaz.sh 'check_version'", $retour);
	foreach ($retour as $key => $value) {
		echo $value;
	}
}
?>
