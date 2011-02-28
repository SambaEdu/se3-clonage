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
$_SESSION["pageaide"]="Le_module_Clonage_des_stations#Programmer_un_rapport";

// CSS pour mes tableaux:
echo "<link type='text/css' rel='stylesheet' href='tftp.css' />\n";

if (is_admin("system_is_admin",$login)=="Y")
{
	$id_machine=isset($_POST['id_machine']) ? $_POST['id_machine'] : (isset($_GET['id_machine']) ? $_GET['id_machine'] : NULL);
	$suppr=isset($_POST['suppr']) ? $_POST['suppr'] : NULL;

	if(isset($suppr)) {
		$chaine="";
		for($i=0;$i<count($suppr);$i++) {
			$sql="DELETE FROM se3_tftp_rapports WHERE id='$id_machine' AND identifiant='$suppr[$i]';";
			$res=mysql_query($sql);
			if(!$res) {
				$chaine.="<span style='color:red;'>Erreur lors de la suppression du rapport n°$suppr[$i].</span><br />\n";
			}
		}
		echo $chaine;
	}

	//$sql="SELECT * FROM se3_tftp_action WHERE id='".$id_machine."';";
	$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine."';";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)>0) {
		$lig=mysql_fetch_object($res);
		$mac_machine=$lig->mac;
		$nom_machine=$lig->name;
		echo "<h1>Rapport(s) sur $nom_machine</h1>\n";

		$sql="SELECT * FROM se3_tftp_rapports WHERE id='$id_machine' ORDER BY date DESC;";
		$res2=mysql_query($sql);
		if(mysql_num_rows($res2)==0) {
			echo "<p>Aucun rapport trouvé.</p>\n";
		}
		else {
			echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
			echo "<table class='crob'>\n";
			echo "<tr>\n";
			echo "<th>Nom</th>\n";
			echo "<th>Date</th>\n";
			echo "<th>Tâche</th>\n";
			echo "<th>Statut</th>\n";
			echo "<th>Descriptif</th>\n";
			//echo "<th>Supprimer</th>\n";
			echo "<th><input type='submit' name='supprimer' value='Supprimer' /><br />\n";
			echo "<a href='#' onclick='check_suppr(\"check\");return false'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Tout cocher\" title=\"Tout cocher\" /></a>\n";
			echo " / <a href='#' onclick='check_suppr(\"uncheck\");return false'><img src=\"../elements/images/disabled.gif\" border='0' alt=\"Tout décocher\" title=\"Tout décocher\" /></a>\n";
			echo "</th>\n";
			echo "</tr>\n";
			//$nb_rapports=mysql_num_rows($res2);
			$cpt=0;
			while($lig2=mysql_fetch_object($res2)) {
				echo "<tr>\n";
				echo "<td>$lig2->name</td>\n";
				echo "<td>".mysql_date_to_fr_date($lig2->date)."</td>\n";
				echo "<td>".ucfirst(strtolower($lig2->tache))."</td>\n";
				echo "<td>\n";
				$tmp=ucfirst(strtolower($lig2->statut));
				if($tmp=="Succes") {
					echo "<span style='color:green;'>Succès</span>";
				}
				elseif($tmp=="Echec") {
					echo "<span style='color:red;'>Echec</span>";
				}
				elseif($tmp=="Valide") {
					echo "<span style='color:blue;'>Valide</span>";
				}
				else{
					echo $tmp;
				}
				echo "</td>\n";

				echo "<td>\n";
				echo nl2br(htmlentities($lig2->descriptif));
				echo "</td>\n";

				echo "<td>\n";
				echo "<input type='checkbox' name='suppr[]' id='suppr_$cpt' value='$lig2->identifiant' />\n";
				//echo  $lig2->identifiant;
				echo "</td>\n";
				echo "</tr>\n";
				$cpt++;
			}
			echo "</table>\n";
			echo "<input type='hidden' name='id_machine' value='$id_machine' />\n";
			//echo "<input type='submit' name='supprimer' value='Supprimer' />\n";
			echo "</form>\n";

			echo "<script type='text/javascript'>
	function check_suppr(mode) {
		for(i=0;i<$cpt;i++){
			if(document.getElementById('suppr_'+i)){
				if(mode=='check'){
					document.getElementById('suppr_'+i).checked=true;
				}
				else{
					document.getElementById('suppr_'+i).checked=false;
				}
			}
		}
	}
</script>\n";
		}

		//echo "<p><i>NOTE:</i> Ajouter la possibilité de supprimer des rapports.</p>\n";
	}
	else {
		echo "<h1>Visualisation de rapport</h1>\n";
		echo "<p>ERREUR: Machine $id_machine inconnue dans la table 'se3_dhcp'.</p>\n";
	}
}

// Footer
include ("pdp.inc.php");

?>
