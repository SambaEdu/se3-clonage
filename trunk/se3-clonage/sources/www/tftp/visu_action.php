<?php
/* $Id$
===========================================
   Projet SE3
   Dispositif SE3+TFTP+Sauvegarde/Restauration/Clonage
   Stephane Boireau
   Distribué selon les termes de la licence GPL
=============================================
*/

// loading libs and init
include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
//require_once "../dhcp/dhcpd.inc.php";
include "printers.inc.php";

require("lib_action_tftp.php");

//aide
$_SESSION["pageaide"]="Le_module_Clonage_des_stations#Consulter_le_r.C3.A9sultat_d.27une_action";

// CSS pour mes tableaux:
echo "<link type='text/css' rel='stylesheet' href='tftp.css' />\n";

if (is_admin("system_is_admin",$login)=="Y")
{
	$id_machine=isset($_POST['id_machine']) ? $_POST['id_machine'] : (isset($_GET['id_machine']) ? $_GET['id_machine'] : NULL);

	$sql="SELECT * FROM se3_tftp_action WHERE id='".$id_machine."';";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)>0) {
		$lig=mysql_fetch_object($res);
		echo "<h1>Action programmée sur $lig->name</h1>\n";
		$mac_machine=$lig->mac;

		visu_tache($mac_machine);

		echo "<p><i>NOTE:</i> Ajouter la possibilité de supprimer une tâche.</p>\n";

	}
	else {
		echo "<h1>Visualisation d'action programmée</h1>\n";
		$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine."';";
		$res=mysql_query($sql);
		if(mysql_num_rows($res)>0) {
			$lig=mysql_fetch_object($res);
			echo "<p>L'action programmée sur $lig->name doit être achevée.<br />La tâche n'est plus présente dans la table 'se3_tftp_action'.</p>\n";
		}
		else {
			echo "<p>ERREUR: Machine inconnue dans la table 'se3_dhcp'.</p>\n";
		}
	}
}

// Footer
include ("pdp.inc.php");

?>
