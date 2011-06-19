<?php
/* $Id$
===========================================
   Projet SE3
   Dispositif SE3+TFTP+Sauvegarde/Restauration/Clonage
   Stephane Boireau
   Distribu� selon les termes de la licence GPL
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

if((is_admin("system_is_admin",$login)!="Y")&&(ldap_get_right("parc_can_clone",$login)!="Y")) {
	echo "<p style='color:red'>Action non autorisee.</p>";
	die();
}
else {
	$id_machine=isset($_POST['id_machine']) ? $_POST['id_machine'] : (isset($_GET['id_machine']) ? $_GET['id_machine'] : NULL);

	$restriction_parcs="n";
	if(is_admin("system_is_admin",$login)!="Y") {
		$restriction_parcs="y";
		$tab_delegated_parcs=list_delegated_parcs($login);
		if(count($tab_delegated_parcs)==0) {
			echo "<p style='color:red'>Aucun parc ne vous a �t� d�l�gu�.</p>\n";
			die();
		}

		$temoin_erreur="y";
		$nom="";

		$sql="SELECT name FROM se3_dhcp WHERE id='$id_machine';";
		$res=mysql_query($sql);
		if(mysql_num_rows($res)>0) {
			$lig=mysql_fetch_object($res);
			$nom=$lig->name;

			for($loop=0;$loop<count($tab_delegated_parcs);$loop++) {
				// La machine est-elle dans un des parcs d�l�gu�s?
				if(is_machine_in_parc($nom,$tab_delegated_parcs[$loop])) {
					$temoin_erreur='n';
					break;
				}
			}
		}
		if($temoin_erreur=="y") {
			echo "<p style='color:red'>La machine $nom n'est pas dans un de vos parcs delegues.</p>\n";
			die();
		}

	}

	$sql="SELECT * FROM se3_tftp_action WHERE id='".$id_machine."';";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)>0) {
		$lig=mysql_fetch_object($res);
		echo "<h1>Action programm�e sur $lig->name</h1>\n";
		$mac_machine=$lig->mac;

		visu_tache($mac_machine);

		echo "<p><i>NOTE:</i> Ajouter la possibilit� de supprimer une t�che.</p>\n";

	}
	else {
		echo "<h1>Visualisation d'action programm�e</h1>\n";
		$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine."';";
		$res=mysql_query($sql);
		if(mysql_num_rows($res)>0) {
			$lig=mysql_fetch_object($res);
			echo "<p>L'action programm�e sur $lig->name doit �tre achev�e.<br />La t�che n'est plus pr�sente dans la table 'se3_tftp_action'.</p>\n";
		}
		else {
			echo "<p>ERREUR: Machine inconnue dans la table 'se3_dhcp'.</p>\n";
		}
	}
}

// Footer
include ("pdp.inc.php");

?>
