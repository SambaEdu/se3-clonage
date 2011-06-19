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
$_SESSION["pageaide"]="Le_module_Clonage_des_stations#Programmer_une_sauvegarde";

// CSS pour mes tableaux:
echo "<link type='text/css' rel='stylesheet' href='tftp.css' />\n";

if ((is_admin("system_is_admin",$login)=="Y")||(ldap_get_right("parc_can_clone",$login)=="Y"))
{
	$id_machine=isset($_POST['id_machine']) ? $_POST['id_machine'] : (isset($_GET['id_machine']) ? $_GET['id_machine'] : NULL);
	$suppr=isset($_POST['suppr']) ? $_POST['suppr'] : NULL;

	if(!isset($id_machine)) {
		echo "<p style='color:red'>Aucune machine n'est choisie.</p>";
		include ("pdp.inc.php");
		die();
	}

	$restriction_parcs="n";
	if(is_admin("system_is_admin",$login)!="Y") {
		$restriction_parcs="y";
		$tab_delegated_parcs=list_delegated_parcs($login);
		if(count($tab_delegated_parcs)==0) {
			echo "<p style='color:red'>Aucun parc ne vous a été délégué.</p>\n";
			include ("pdp.inc.php");
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
				// La machine est-elle dans un des parcs délégués?
				if(is_machine_in_parc($nom,$tab_delegated_parcs[$loop])) {
					$temoin_erreur='n';
					break;
				}
			}
		}
		if($temoin_erreur=="y") {
			echo "<p style='color:red'>La machine $nom n'est pas dans un de vos parcs delegues.</p>\n";
			include ("pdp.inc.php");
			die();
		}

	}

	if(isset($suppr)) {
		$chaine="";
		for($i=0;$i<count($suppr);$i++) {
			$sql="DELETE FROM se3_tftp_sauvegardes WHERE id='$id_machine' AND identifiant='$suppr[$i]';";
			$res=mysql_query($sql);
			if(!$res) {
				$chaine.="<span style='color:red;'>Erreur lors de la suppression du rapport de sauvegarde n°$suppr[$i].</span><br />\n";
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
		echo "<h1>Sauvegarde(s) sur $nom_machine</h1>\n";

		$sql="SELECT * FROM se3_tftp_sauvegardes WHERE id='$id_machine' ORDER BY date DESC;";
		$res2=mysql_query($sql);
		if(mysql_num_rows($res2)==0) {
			echo "<p>Aucune sauvegarde trouvée.</p>\n";
		}
		else {
			echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
			echo "<table class='crob'>\n";
			echo "<tr>\n";
			echo "<th>Nom</th>\n";
			echo "<th>Date</th>\n";
			echo "<th>Partition<br />sauvegardée</th>\n";
			echo "<th>Image</th>\n";
			//echo "<th>Statut</th>\n";
			echo "<th>Descriptif</th>\n";
			echo "<th>Partitionnement</th>\n";
			//echo "<th>Supprimer</th>\n";
			echo "<th><input type='submit' name='supprimer' value='Supprimer' /><br />\n";
			echo "<a href='#' onclick='check_suppr(\"check\");return false'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Tout cocher\" title=\"Tout cocher\" /></a>\n";
			echo " / <a href='#' onclick='check_suppr(\"uncheck\");return false'><img src=\"../elements/images/disabled.gif\" border='0' alt=\"Tout décocher\" title=\"Tout décocher\" /></a>\n";
			echo "</th>\n";
			echo "</tr>\n";
			$cpt=0;
			while($lig2=mysql_fetch_object($res2)) {
				echo "<tr>\n";
				echo "<td>$lig2->name</td>\n";
				echo "<td>".mysql_date_to_fr_date($lig2->date)."</td>\n";
				echo "<td>".$lig2->partition."</td>\n";
				echo "<td>".$lig2->image."</td>\n";
				/*
				echo "<td>\n";
				$tmp=ucfirst(strtolower($lig2->statut));
				if($tmp=="Succes") {
					echo "<span style='color:green;'>Succès</span>";
				}
				elseif($tmp=="Echec") {
					echo "<span style='color:red;'>Echec</span>";
				}
				else{
					echo $tmp;
				}
				echo "</td>\n";
				*/
				echo "<td>\n";

					//echo nl2br(htmlentities($lig2->descriptif));

					//if(!preg_match("/Infos sur ".$lig2->image."\n/",$lig2->descriptif)){
					if(!mb_ereg("Infos sur ".$lig2->image."\n",$lig2->descriptif)){
						echo nl2br(htmlentities($lig2->descriptif));
					}
					else {
						//echo "<table border='1'>\n";
						echo "<table class='crob' style='margin:1px;'>\n";
						$temoin_infos_image=0;
						$temoin_volume_image=0;
						$temoin_espace_dispo=0;
						$infos_complementaires_svg="";
						$tab_descr=explode("\n",$lig2->descriptif);
						for($i=0;$i<count($tab_descr);$i++){
							//if(preg_match("/^Infos sur ".$lig2->image."$/",$tab_descr[$i])){
							if(mb_ereg("^Infos sur ".$lig2->image."$",$tab_descr[$i])){
								$temoin_infos_image++;
							}

							//if(preg_match("/^Volume:$/",$tab_descr[$i])) {
							if(preg_match("/^Volume de la sauvegarde:$/",$tab_descr[$i])) {
								echo "</td></tr>\n";
								$temoin_infos_image=-100000;
								$temoin_volume_image++;
							}

							if(preg_match("/^Espace total\/occupé\/encore disponible:$/",$tab_descr[$i])) {
								break;
							}

							/*
							//if(preg_match("/^___+\*+___$/",$tab_descr[$i])) {
							//if(preg_match("/___+\*+___/",trim($tab_descr[$i]))) {
							//if(preg_match("/^___+/",trim($tab_descr[$i]))) {
							if(preg_match("/^___\+\*\+___$/",trim($tab_descr[$i]))) {
								echo "</table>\n";
								//echo "aaaaaaa";
								echo "</td></tr>\n";
								$temoin_infos_image=-100000;
								$temoin_volume_image=-100000;
								$temoin_espace_dispo++;
							}
							*/

							if($temoin_infos_image==1) {
								//echo "<tr><th>Infos</th><td colspan='2'>";
								echo "<tr><th>Infos</th><td>";
								//echo htmlentities($tab_descr[$i])."<br />\n";
								$temoin_infos_image++;
							}
							elseif($temoin_infos_image>1) {
								//echo nl2br(htmlentities($tab_descr[$i]));
								echo htmlentities($tab_descr[$i])."<br />\n";
								$temoin_infos_image++;
							}

							if($temoin_volume_image==1) {
								//echo "</td></tr>\n";
								echo "<tr><th>Volume</th><td>";
								//echo htmlentities($tab_descr[$i])."<br />\n";
								echo "<table class='crob' style='margin:1px;'>\n";
								$temoin_volume_image++;
							}
							elseif($temoin_volume_image>1) {
								//echo nl2br(htmlentities($tab_descr[$i]));
								//echo htmlentities($tab_descr[$i])."<br />\n";
								//if(trim($tab_descr[$i])!=""){
								if(($infos_complementaires_svg=="")&&(trim($tab_descr[$i])!="")) {
									unset($tab_vol);
									$tab_vol=explode(" ",preg_replace("/\t/"," ",$tab_descr[$i]));
									echo "<tr><td>".htmlentities($tab_vol[0])."</td><td>".basename(htmlentities($tab_vol[1]))."</td></tr>\n";
								}
								else {
									$infos_complementaires_svg.=$tab_descr[$i]."\n";
								}
								$temoin_volume_image++;
							}

							/*
							if($temoin_espace_dispo==1) {
								$temoin_espace_dispo++;
							}
							elseif($temoin_espace_dispo>1) {
								if(trim($tab_descr[$i])!="") {
									unset($tab_esp);
									$tab_esp=explode(" ",preg_replace("/ {2,}/"," ",preg_replace("/\t/"," ",$tab_descr[$i])));
									echo "<tr><th>V.total partition</th><td>".$tab_esp[1]."</td></tr>\n";
									echo "<tr><th>V.occupé</th><td>".$tab_esp[2]."</td></tr>\n";
									echo "<tr><th>V.libre</th><td>".$tab_esp[3]."</td></tr>\n";
								}
								$temoin_espace_dispo++;
							}
							*/
						}
						if($temoin_volume_image!=0) {
							echo "</table>\n";
							if($infos_complementaires_svg!='') {echo "<pre style='text-align:left;'>$infos_complementaires_svg</pre>";}
						}

						//echo "";

						echo "</td></tr>\n";
						if($lig2->df!="") {
							unset($tab_esp);
							$tab_esp=explode(" ",preg_replace("/ {2,}/"," ",preg_replace("/\t/"," ",$lig2->df)));
							echo "<tr><th>V.total partition</th><td>".$tab_esp[1]."</td></tr>\n";
							echo "<tr><th>V.occupé</th><td>".$tab_esp[2]."</td></tr>\n";
							echo "<tr><th>V.libre</th><td>".$tab_esp[3]."</td></tr>\n";
						}
						//echo "</td></tr>\n";
						echo "</table>\n";
					}

				echo "</td>\n";

				echo "<td>\n";
				echo "<pre>".htmlentities($lig2->partitionnement)."</pre>";
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

		echo "<p><i>NOTE:</i></p>
<ul>\n";
	//echo "<li>Modifier le traitement sur le descriptif pour extraire/mettre en valeur les infos.</li>\n";
	echo "<li>Permettre de supprimer un rapport de sauvegarde: FAIT.<br />
	En revanche, rien ne permet actuellement de gérer les doublons.</li>
</ul>\n";
	}
	else {
		echo "<h1>Visualisation de sauvegardes</h1>\n";
		echo "<p>ERREUR: Machine $id_machine inconnue dans la table 'se3_dhcp'.</p>\n";
	}
}

// Footer
include ("pdp.inc.php");

?>
