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

// On active les rapports d'erreurs:
//error_reporting(E_ALL);

// CSS pour mes tableaux:
echo "<link type='text/css' rel='stylesheet' href='tftp.css' />\n";

if ((is_admin("system_is_admin",$login)=="Y")||(ldap_get_right("parc_can_clone",$login)=="Y"))
{

	$mode_rech=isset($_POST['mode_rech']) ? $_POST['mode_rech'] : (isset($_GET['mode_rech']) ? $_GET['mode_rech'] : NULL);

	//$action=isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : NULL);

	$parc=isset($_POST['parc']) ? $_POST['parc'] : (isset($_GET['parc']) ? $_GET['parc'] : NULL);

	$parametrage_action=isset($_POST['parametrage_action']) ? $_POST['parametrage_action'] : (isset($_GET['parametrage_action']) ? $_GET['parametrage_action'] : NULL);

	$id_machine=isset($_POST['id_machine']) ? $_POST['id_machine'] : (isset($_GET['id_machine']) ? $_GET['id_machine'] : NULL);
	$num_op=isset($_POST['num_op']) ? $_POST['num_op'] : (isset($_GET['num_op']) ? $_GET['num_op'] : NULL);


	$restriction_parcs="n";
	if(is_admin("system_is_admin",$login)!="Y") {
		$restriction_parcs="y";
		$tab_delegated_parcs=list_delegated_parcs($login);
		if(count($tab_delegated_parcs)==0) {
			echo "<p>Aucun parc ne vous a été délégué.</p>\n";
			include ("pdp.inc.php");
			die();
		}
	}


	$suppr=isset($_POST['suppr']) ? $_POST['suppr'] : NULL;
	if(isset($suppr)){
		for($i=0;$i<count($suppr);$i++){
			// Suppression du fichier en /tftpboot/pxelinux.cfg/

			$temoin_erreur="y";
			$nom="";
	
			$sql="SELECT name FROM se3_dhcp WHERE id='$suppr[$i]';";
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
				die();
			}


			// Récupérer l'adresse MAC:
			$sql="SELECT mac FROM se3_dhcp WHERE id='$suppr[$i]';";
			//echo "$sql<br />\n";
			$res_mac=mysql_query($sql);
			if(mysql_num_rows($res_mac)==0) {
				$sql="SELECT mac FROM se3_tftp_action WHERE id='$suppr[$i]';";
				//echo "$sql<br />\n";
				$res_mac=mysql_query($sql);
				if(mysql_num_rows($res_mac)==0) {
					echo "<span style='color:red'>ERREUR:</span> L'adresse MAC de la machine d'identifiant $suppr[$i] n'a pas été trouvée dans les tables 'se3_dhcp' ni 'se3_tftp_action'. Il se peut qu'il subsiste un fichier /tftpboot/pxelinux.cfg/01-ADRESSE_MAC qui pourrait perturber le démarrage de la machine.<br />\n";
				}
				else {
					$lig_mac=mysql_fetch_object($res_mac);
					//$corrige_mac=strtolower(strtr($mac_machine,":","-"));
					$corrige_mac=strtolower(strtr($lig_mac->mac,":","-"));
					//echo "Test de /tftpboot/pxelinux.cfg/01-$corrige_mac<br />\n";
					if(file_exists("/tftpboot/pxelinux.cfg/01-$corrige_mac")) {
						//echo "Suppression de /tftpboot/pxelinux.cfg/01-$corrige_mac<br />\n";
						unlink("/tftpboot/pxelinux.cfg/01-$corrige_mac");
					}
				}
			}
			else {
				$lig_mac=mysql_fetch_object($res_mac);
				//$corrige_mac=strtolower(strtr($mac_machine,":","-"));
				$corrige_mac=strtolower(strtr($lig_mac->mac,":","-"));
				//echo "Test de /tftpboot/pxelinux.cfg/01-$corrige_mac<br />\n";
				if(file_exists("/tftpboot/pxelinux.cfg/01-$corrige_mac")) {
					//echo "Suppression de /tftpboot/pxelinux.cfg/01-$corrige_mac<br />\n";
					unlink("/tftpboot/pxelinux.cfg/01-$corrige_mac");
				}
			}

			// Suppression de l'action dans la table:
			$sql="DELETE FROM se3_tftp_action WHERE id='$suppr[$i]';";
			//echo "$sql<br />\n";
			$suppression=mysql_query($sql);

			// Suppression de la tâche recup_rapport.php?
			//$dossier="/var/se3/tmp/tftp/$suppr[$i]";
			$dossier="/etc/se3/www-tools/tftp/$suppr[$i]";
			$lanceur_recup="$dossier/lanceur_recup_rapport_action_tftp.sh";
			if(file_exists($lanceur_recup)) {
				unlink($lanceur_recup);
			}
		}
	}

	// Création de la table dès que possible:
	creation_tftp_tables();

	echo "<h1>".gettext("Consultation TFTP")."</h1>\n";

	if(is_admin("system_is_admin",$login)!="Y") {
		$mode_rech="parc";
	}

	if(!isset($mode_rech)){
		echo "<p>Choisissez le mode de consultation:</p>\n";
		echo "<ul>\n";
		echo "<li><a href='".$_SERVER['PHP_SELF']."?mode_rech=parc'>Sélectionner un parc</a>.</li>\n";

		$sql="SELECT 1=1 FROM se3_tftp_action WHERE type='sauvegarde';";
		$test=mysql_query($sql);
		if(mysql_num_rows($test)>0) {
			echo "<li><a href='".$_SERVER['PHP_SELF']."?mode_rech=svg'>Afficher les sauvegardes en attente</a>.</li>\n";
		}

		$sql="SELECT 1=1 FROM se3_tftp_action WHERE type='restauration';";
		$test=mysql_query($sql);
		if(mysql_num_rows($test)>0) {
			echo "<li><a href='".$_SERVER['PHP_SELF']."?mode_rech=rest'>Afficher les restaurations en attente</a>.</li>\n";
		}

		$sql="SELECT 1=1 FROM se3_tftp_action WHERE type LIKE 'udpcast_%';";
		$test=mysql_query($sql);
		if(mysql_num_rows($test)>0) {
			echo "<li><a href='".$_SERVER['PHP_SELF']."?mode_rech=clone'>Afficher les clonages en attente</a>.</li>\n";
		}

		$sql="SELECT 1=1 FROM se3_tftp_action WHERE type='rapport';";
		$test=mysql_query($sql);
		if(mysql_num_rows($test)>0) {
			echo "<li><a href='".$_SERVER['PHP_SELF']."?mode_rech=rapport'>Afficher les remontées de rapports en attente</a>.</li>\n";
		}

		$sql="SELECT 1=1 FROM se3_tftp_action WHERE type='unattend_xp';";
		$test=mysql_query($sql);
		if(mysql_num_rows($test)>0) {
			echo "<li><a href='".$_SERVER['PHP_SELF']."?mode_rech=unattend_xp'>Afficher les installations unattend XP en attente</a>.</li>\n";
		}

		echo "</ul>\n";

		echo "<p><a href='index.php'>Retour à l'index</a>.</p>\n";
	}
	else {
		if($mode_rech=="parc"){
			if(!isset($parc)){
				echo "<p>Choisissez un ou des parcs:</p>\n";

				$list_parcs=search_machines("objectclass=groupOfNames","parcs");
				if (count($list_parcs)==0) {
					echo "<br><br>";
					echo gettext("Il n'existe aucun parc. Vous devez d'abord créer un parc");
					include ("pdp.inc.php");
					exit;
				}
				sort($list_parcs);

				echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
				echo "<input type=\"hidden\" name=\"mode_rech\" value=\"$mode_rech\" />\n";

				// Affichage des parcs sur 3/4 colonnes
				$nb_parcs_par_colonne=round(count($list_parcs)/3);
				echo "<table border='0'>\n";
				echo "<tr valign='top'>\n";
				echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
				echo "<td align='left'>\n";
				for ($loop=0; $loop < count($list_parcs); $loop++) {
					//array_push($parcs,$list_parcs[$loop]["cn"]);

					if(($loop>0)&&(round($loop/$nb_parcs_par_colonne)==$loop/$nb_parcs_par_colonne)){
						echo "</td>\n";
						echo "<td align='left'>\n";
					}

					if(($restriction_parcs=="n")||(in_array($list_parcs[$loop]["cn"], $tab_delegated_parcs))) {
						echo "<label for='parc_$loop'><input type='checkbox' id='parc_$loop' name='parc[]' value=\"".$list_parcs[$loop]["cn"]."\" />".$list_parcs[$loop]["cn"]."</label>\n";
						echo "<br />\n";
					}
				}

				echo "</td>\n";
				echo "</tr>\n";
				echo "</table>\n";

				echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider\" /></p>\n";

				echo "</form>\n";
			}
			else {
				echo "<script type='text/javascript' src='../includes/prototype.js'></script>\n";
				// Afficher un tableau des parcs avec les machines qui ont une action programmée...

				echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
				echo "<input type=\"hidden\" name=\"mode_rech\" value=\"$mode_rech\" />\n";

				$max_eff_parc=0;
				for($i=0;$i<count($parc);$i++){
					echo "<input type='hidden' name='parc[]' value='$parc[$i]' />\n";

					echo "<h2>Parc $parc[$i]</h2>\n";

					$mp=gof_members($parc[$i],"parcs",1);
					$nombre_machine=count($mp);
					sort($mp);

					//echo "<table border='1'>\n";
					echo "<table class='crob'>\n";
					echo "<tr>\n";

					echo "<th>Nom</th>\n";
					echo "<th>Etat</th>\n";
					echo "<th>Session</th>\n";
					echo "<th>Config DHCP</th>\n";

					echo "<th>Action</th>\n";

					echo "<th>Supprimer l'action<br />\n";
					echo "<a href='#' onclick='check_suppr($i,\"check\");return false'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Tout cocher\" title=\"Tout cocher\" /></a>\n";
					echo " / <a href='#' onclick='check_suppr($i,\"uncheck\");return false'><img src=\"../elements/images/disabled.gif\" border='0' alt=\"Tout décocher\" title=\"Tout décocher\" /></a>\n";
					echo "</th>\n";

					echo "<th>Rapports</th>\n";
					echo "<th>Sauvegardes<br />antérieures</th>\n";

					//echo "<th></th>\n";
					echo "</tr>\n";

					for ($loop=0; $loop < count($mp); $loop++) {
						$mpenc=urlencode($mp[$loop]);

						// Test si on a une imprimante ou une machine
						$resultat=search_imprimantes("printer-name=$mpenc","printers");
						$suisje_printer="non";
						for ($loopp=0; $loopp < count($resultat); $loopp++) {
							if ($mpenc==$resultat[$loopp]['printer-name']) {
								$suisje_printer="yes";
								continue;
							}
						}

						if($suisje_printer=="non") {
							// Réinitialisation:
							$id_machine="";

							echo "<tr>\n";
							echo "<td width='20%'>".$mp[$loop]."</td>\n";

							// Etat: allumé ou éteint
							echo "<td width='20%'>";
							$mp_curr=search_machines2("(&(cn=$mpenc)(objectClass=ipHost))","computers");
							if ($mp_curr[0]["ipHostNumber"]) {
								$iphost=$mp_curr[0]["ipHostNumber"];

								echo "<div id='divip$loop'>Patientez</div>\n";

								echo "<script type='text/javascript'>
									// <![CDATA[
									new Ajax.Updater($('divip$loop'),'ajax_lib.php?ip=$iphost&mode=ping_ip',{method: 'get'});
									//]]>
								</script>\n";

							}
							echo "</td>\n";


							// Session: ouverte ou pas... sous quelle identité
							echo "<td width='20%'>\n";
							echo "<div id='divsession$loop'>Patientez</div>\n";

							echo "<script type='text/javascript'>
								// <![CDATA[
								new Ajax.Updater($('divsession$loop'),'ajax_lib.php?nom_machine=".$mp[$loop]."&mode=session',{method: 'get'});
								//]]>
							</script>\n";

							echo "</td>\n";


							// Etat config DHCP:
							// Par la suite il ne faudra pas prendre les IP dans l'annuaire,
							// mais dans la config DHCP parce que ce sont ces IP qui seront attribuées lors du boot PXE
							echo "<td width='20%'>\n";
							//$mp_curr=search_machines("(&(cn=$mpenc)(objectClass=ipHost))","computers");
							if ($mp_curr[0]["macAddress"]) {
								$sql="SELECT * FROM se3_dhcp WHERE mac='".$mp_curr[0]["macAddress"]."';";
								//echo "$sql<br />";
								$res=mysql_query($sql);
								if(mysql_num_rows($res)>0) {
									$lig=mysql_fetch_object($res);
									$id_machine=$lig->id;

									//echo $lig->id;
									echo "<img src=\"../elements/images/enabled.gif\" border='0' alt=\"$lig->ip\" title=\"$lig->ip\" />";
								}
								else {
									echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse IP attribuée\" title=\"Pas d'adresse IP attribuée\" />";
								}
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse MAC dans l'annuaire???\" title=\"Pas d'adresse MAC dans l'annuaire???\" />";
							}
							echo "</td>\n";


							// Action programmée
							echo "<td width='20%'>\n";
							/*
							foreach($mp_curr[0] as $champ => $valeur) {
								echo "\$mp_curr[0]['$champ']=$valeur<br />";
							}
							*/
							$temoin_action="n";
							if($id_machine!=""){
								$sql="SELECT * FROM se3_tftp_action WHERE id='".$id_machine."';";
								$res=mysql_query($sql);
								if(mysql_num_rows($res)>0) {
									$lig=mysql_fetch_object($res);
									echo "<a href='visu_action.php?id_machine=$id_machine' target='_blank'>$lig->type</a> (<i>$lig->num_op</i>)";

									//echo " <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<center>".preg_replace("/'/","",preg_replace("/'/","",visu_tache($mp_curr[0]["macAddress"],'light')))."</center>')")."\"><img name=\"action_image$loop\" src=\"../elements/images/detail.gif\" /></u>";

									//echo " <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<center>".preg_replace("/'/","",visu_tache($mp_curr[0]["macAddress"],'light'))."</center>')")."\"><img name=\"action_image$loop\"  src=\"../elements/images/detail.gif\"></u>";
									// CELA MERDOUILLE QUAND LA TACHE EST UN CLONAGE... JE NE SAISIS PAS POURQUOI...
									echo " <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<center>".preg_replace('/"/','',preg_replace("/'/","",visu_tache($mp_curr[0]["macAddress"],'light')))."</center>')")."\"><img name=\"action_image$loop\"  src=\"../elements/images/detail.gif\"></u>";

									$temoin_action="y";

									//echo " <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<center>".preg_replace("/'/"," ",visu_tache($mp_curr[0]["macAddress"],'light'))."</center>')")."\"><img name=\"action_image$loop\"  src=\"../elements/images/detail.gif\" /></u>";

									//echo " <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<center>".visu_tache($mp_curr[0]["macAddress"],'light')."</center>')")."\"><img name=\"action_image$loop\"  src=\"../elements/images/detail.gif\" /></u>";
								}
								else {
									echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'action programmée\" title=\"Pas d'action programmée\" />";
								}
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Il faut commencer par effectuer la configuration DHCP\" title=\"Il faut commencer par effectuer la configuration DHCP\" />";
							}
							echo "</td>\n";

							if($temoin_action=="y") {
								echo "<td>\n";
								echo "<input type='checkbox' name='suppr[]' id='suppr_".$i."_".$loop."' value='$id_machine' />\n";
								echo "</td>\n";
							}
							else {
								echo "<td>\n";
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'action à supprimer\" title=\"Pas d'action à supprimer\" />\n";
								echo "</td>\n";
							}

							// Rapports
							echo "<td width='20%'>\n";
							if($id_machine!=""){
								$sql="SELECT * FROM se3_tftp_rapports WHERE id='".$id_machine."';";
								$res=mysql_query($sql);
								if(mysql_num_rows($res)>0) {
									$lig=mysql_fetch_object($res);
									echo "<a href='visu_rapport.php?id_machine=$id_machine' target='_blank'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Visualiser le(s) rapport(s) existant(s)\" title=\"Visualiser le(s) rapport(s) existant(s)\" /></a>";
								}
								else {
									echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Aucun rapport existant\" title=\"Aucun rapport existant\" />";
								}
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Aucun rapport existant\" title=\"Aucun rapport existant\" />";
							}
							echo "</td>\n";


							// Sauvegardes existantes
							echo "<td width='20%'>\n";
							if($id_machine!=""){
								$sql="SELECT * FROM se3_tftp_sauvegardes WHERE id='".$id_machine."';";
								$res=mysql_query($sql);
								if(mysql_num_rows($res)>0) {
									$lig=mysql_fetch_object($res);
									echo "<a href='visu_svg.php?id_machine=$id_machine' target='_blank'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Visualiser la(les) sauvegarde(s) existante(s)\" title=\"Visualiser la(les) sauvegarde(s) existante(s)\" /></a>";
								}
								else {
									echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Aucune sauvegarde existante\" title=\"Aucune sauvegarde existante\" />";
								}
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Aucune sauvegarde existante\" title=\"Aucune sauvegarde existante\" />";
							}
							echo "</td>\n";

							echo "</tr>\n";
						}
					}
					echo "</table>\n";
					if($max_eff_parc<$loop) {$max_eff_parc=$loop;}
				}

				echo "<script type='text/javascript'>
	function check_suppr(num_parc,mode) {
		for(i=0;i<$max_eff_parc;i++){
			if(document.getElementById('suppr_'+num_parc+'_'+i)){
				if(mode=='check'){
					document.getElementById('suppr_'+num_parc+'_'+i).checked=true;
				}
				else{
					document.getElementById('suppr_'+num_parc+'_'+i).checked=false;
				}
			}
		}
	}
</script>\n";

				echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider les suppressions\" /></p>\n";
				echo "</form>\n";

				echo "<p><i>NOTE:</i> Ajouter l'affichage du rapport s'il existe, des sauvegardes existantes,...</p>";

				//echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du(des) parc(s)</a>.</p>\n";
			}
		}
		//elseif($mode_rech=="svg"){
		//elseif(($mode_rech=="svg")||($mode_rech=="rest")){
		elseif(($mode_rech=="svg")||($mode_rech=="rest")||($mode_rech=="rapport")||($mode_rech=="unattend_xp")){
			echo "<script type='text/javascript' src='../includes/prototype.js'></script>\n";

			echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
			echo "<input type=\"hidden\" name=\"mode_rech\" value=\"$mode_rech\" />\n";

			// Afficher un tableau des parcs avec les machines qui ont une action programmée...
			//for($i=0;$i<count($parc);$i++){

				if($mode_rech=="svg") {
					echo "<h2>Sauvegardes en attente</h2>\n";

					$sql="SELECT * FROM se3_tftp_action WHERE type='sauvegarde' ORDER BY num_op,name;";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)==0){
						echo "<p>Aucune sauvegarde n'est en attente.</p>\n";
						include ("pdp.inc.php");
						exit();
					}
				}
				elseif($mode_rech=="rest") {
					echo "<h2>Restaurations en attente</h2>\n";

					$sql="SELECT * FROM se3_tftp_action WHERE type='restauration' ORDER BY num_op,name;";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)==0){
						echo "<p>Aucune restauration n'est en attente.</p>\n";
						include ("pdp.inc.php");
						exit();
					}
				}
				elseif($mode_rech=="rapport") {
					echo "<h2>Remontées de rapports programmées</h2>\n";

					$sql="SELECT * FROM se3_tftp_action WHERE type='rapport' ORDER BY num_op,name;";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)==0){
						echo "<p>Aucune remontée de rapport n'est en attente.</p>\n";
						include ("pdp.inc.php");
						exit();
					}
				}
				elseif($mode_rech=="unattend_xp") {
					echo "<h2>Remontées de rapports programmées</h2>\n";

					$sql="SELECT * FROM se3_tftp_action WHERE type='unattend_xp' ORDER BY num_op,name;";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)==0){
						echo "<p>Aucune installation unattend xp n'est en attente.</p>\n";
						include ("pdp.inc.php");
						exit();
					}
				}

				//$mp=gof_members($parc[$i],"parcs",1);
				$nombre_machine=mysql_num_rows($res);
				$mp=array();
				//sort($mp);
				while($lig=mysql_fetch_object($res)) {
					$mp[]=$lig->name;
				}

				//echo "<table border='1'>\n";
				echo "<table class='crob'>\n";
				echo "<tr>\n";

				//echo "<th>Numéro d'opération</th>\n";
				echo "<th>Nom</th>\n";
				echo "<th>Etat</th>\n";
				echo "<th>Session</th>\n";
				echo "<th>Config DHCP</th>\n";

				echo "<th>Action</th>\n";

				echo "<th>Supprimer l'action<br />\n";
				echo "<a href='#' onclick='check_suppr(\"check\");return false'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Tout cocher\" title=\"Tout cocher\" /></a>\n";
				echo " / <a href='#' onclick='check_suppr(\"uncheck\");return false'><img src=\"../elements/images/disabled.gif\" border='0' alt=\"Tout décocher\" title=\"Tout décocher\" /></a>\n";
				echo "</th>\n";

				echo "<th>Rapports</th>\n";
				echo "<th>Sauvegardes<br />antérieures</th>\n";

				//echo "<th></th>\n";
				echo "</tr>\n";

				for ($loop=0; $loop < count($mp); $loop++) {
					$mpenc=urlencode($mp[$loop]);

					// Test si on a une imprimante ou une machine
					$resultat=search_imprimantes("printer-name=$mpenc","printers");
					$suisje_printer="non";
					for ($loopp=0; $loopp < count($resultat); $loopp++) {
						if ($mpenc==$resultat[$loopp]['printer-name']) {
							$suisje_printer="yes";
							continue;
						}
					}

					if($suisje_printer=="non") {
						// Réinitialisation:
						$id_machine="";

						echo "<tr>\n";
						//echo "<td width='20%'></td>\n";
						echo "<td width='20%'>".$mp[$loop]."</td>\n";

						// Etat: allumé ou éteint
						echo "<td width='20%'>";
						$mp_curr=search_machines2("(&(cn=$mpenc)(objectClass=ipHost))","computers");
						if ($mp_curr[0]["ipHostNumber"]) {
							$iphost=$mp_curr[0]["ipHostNumber"];

							echo "<div id='divip$loop'>Patientez</div>\n";
							echo "<script type='text/javascript'>
								// <![CDATA[
								new Ajax.Updater($('divip$loop'),'ajax_lib.php?ip=$iphost&mode=ping_ip',{method: 'get'});
								//]]>
							</script>\n";
						}
						echo "</td>\n";


						// Session: ouverte ou pas... sous quelle identité
						echo "<td width='20%'>\n";
						echo "<div id='divsession$loop'>Patientez</div>\n";
						echo "<script type='text/javascript'>
							// <![CDATA[
							new Ajax.Updater($('divsession$loop'),'ajax_lib.php?nom_machine=".$mp[$loop]."&mode=session',{method: 'get'});
							//]]>
						</script>\n";
						echo "</td>\n";


						// Etat config DHCP:
						// Par la suite il ne faudra pas prendre les IP dans l'annuaire,
						// mais dans la config DHCP parce que ce sont ces IP qui seront attribuées lors du boot PXE
						echo "<td width='20%'>\n";
						//$mp_curr=search_machines("(&(cn=$mpenc)(objectClass=ipHost))","computers");
						if ($mp_curr[0]["macAddress"]) {
							$sql="SELECT * FROM se3_dhcp WHERE mac='".$mp_curr[0]["macAddress"]."';";
							//echo "$sql<br />";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								$lig=mysql_fetch_object($res);
								$id_machine=$lig->id;

								//echo $lig->ip;
								echo "<img src=\"../elements/images/enabled.gif\" border='0' alt=\"$lig->ip\" title=\"$lig->ip\" />";
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse IP attribuée\" title=\"Pas d'adresse IP attribuée\" />";
							}
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse MAC dans l'annuaire???\" title=\"Pas d'adresse MAC dans l'annuaire???\" />";
						}
						echo "</td>\n";


						// Action programmée
						echo "<td width='20%'>\n";
						/*
						foreach($mp_curr[0] as $champ => $valeur) {
							echo "\$mp_curr[0]['$champ']=$valeur<br />";
						}
						*/
						if($id_machine!=""){
							$sql="SELECT * FROM se3_tftp_action WHERE id='".$id_machine."';";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								$lig=mysql_fetch_object($res);
								//echo "<a href='visu_action.php?id_machine=$id_machine' target='_blank'>$lig->type</a>";
								echo "<a href='visu_action.php?id_machine=$id_machine' target='_blank'>$lig->type</a> (<i>$lig->num_op</i>)";
								//echo " <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<center>".preg_replace('/\n/',"",preg_replace("/'/","",visu_tache($mp_curr[0]["macAddress"],'light')))."</center>')")."\"><img name=\"action_image$loop\"  src=\"../elements/images/detail.gif\"></u>";
								echo " <u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('<center>".preg_replace("/'/","",visu_tache($mp_curr[0]["macAddress"],'light'))."</center>')")."\"><img name=\"action_image$loop\"  src=\"../elements/images/detail.gif\"></u>";
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'action programmée\" title=\"Pas d'action programmée\" />";
							}
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Il faut commencer par effectuer la configuration DHCP\" title=\"Il faut commencer par effectuer la configuration DHCP\" />";
						}
						echo "</td>\n";

						echo "<td>\n";
						echo "<input type='checkbox' name='suppr[]' id='suppr_".$loop."' value='$id_machine' />\n";
						echo "</td>\n";

						// Rapports
						echo "<td width='20%'>\n";
						if($id_machine!=""){
							$sql="SELECT * FROM se3_tftp_rapports WHERE id='".$id_machine."';";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								$lig=mysql_fetch_object($res);
								echo "<a href='visu_rapport.php?id_machine=$id_machine' target='_blank'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Visualiser le(s) rapport(s) existant(s)\" title=\"Visualiser le(s) rapport(s) existant(s)\" /></a>";
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Aucun rapport existant\" title=\"Aucun rapport existant\" />";
							}
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Aucun rapport existant\" title=\"Aucun rapport existant\" />";
						}
						echo "</td>\n";


						// Sauvegardes existantes
						echo "<td width='20%'>\n";
						if($id_machine!=""){
							$sql="SELECT * FROM se3_tftp_sauvegardes WHERE id='".$id_machine."';";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								$lig=mysql_fetch_object($res);
								echo "<a href='visu_svg.php?id_machine=$id_machine' target='_blank'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Visualiser la(les) sauvegarde(s) existante(s)\" title=\"Visualiser la(les) sauvegarde(s) existante(s)\" /></a>";
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Aucune sauvegarde existante\" title=\"Aucune sauvegarde existante\" />";
							}
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Aucune sauvegarde existante\" title=\"Aucune sauvegarde existante\" />";
						}
						echo "</td>\n";

						echo "</tr>\n";
					}
				}
				echo "</table>\n";
			//}

			echo "<script type='text/javascript'>
	function check_suppr(mode) {
		for(i=0;i<$nombre_machine;i++){
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

			echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider les suppressions\" /></p>\n";
			echo "</form>\n";

			//echo "<p><i>NOTE:</i> Ajouter l'affichage du rapport s'il existe, des sauvegardes existantes,...</p>";

			//echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du(des) parc(s)</a>.</p>\n";

		}
		elseif($mode_rech=="clone"){
			if(!isset($num_op)) {
				echo "<h2>Clonages en attente</h2>\n";

				$sql="SELECT * FROM se3_tftp_action WHERE type='udpcast_emetteur' ORDER BY num_op;";
				$res=mysql_query($sql);
				if(mysql_num_rows($res)>0) {

					echo "<table class='crob'>\n";
					echo "<tr>\n";
					echo "<th>Numéro d'opération</th>\n";
					echo "<th>Emetteur</th>\n";
					echo "<th>Clones</th>\n";
					echo "<th>Disque/Partition</th>\n";
					echo "<th>Port</th>\n";
					echo "<th>Compression</th>\n";
					echo "</tr>\n";
					while($lig=mysql_fetch_object($res)) {
						echo "<tr>\n";
						echo "<td><a href='".$_SERVER['PHP_SELF']."?num_op=$lig->num_op&amp;mode_rech=clone'>$lig->num_op</a></td>\n";
						echo "<td>$lig->name</td>\n";

						echo "<td>";
						$sql="SELECT * FROM se3_tftp_action WHERE num_op='$lig->num_op' AND type='udpcast_recepteur' ORDER BY name;";
						//echo "$sql<br />";
						$res2=mysql_query($sql);
						if(mysql_num_rows($res2)>0) {
							$cpt=0;
							while($lig2=mysql_fetch_object($res2)) {
								if($cpt>0) {echo ", ";}
									echo $lig2->name;
								$cpt++;
							}
						}
						else {
							echo "<span style='color:red'>Aucun récepteur</span>";
						}
						echo "</td>\n";

						// Rechercher dans le fichier de conf... ou dans $lig->infos
						$tab_infos=decoupe_infos($lig->infos);
						echo "<td>";
						if(isset($tab_infos['disk'])) {echo $tab_infos['disk'];}else{echo "<span style='color:red'>NaN</span>";}
						echo "</td>\n";
						echo "<td>";
						if(isset($tab_infos['port'])) {echo $tab_infos['port'];}else{echo "<span style='color:red'>NaN</span>";}
						echo "</td>\n";
						echo "<td>";
						if(isset($tab_infos['compr'])) {echo $tab_infos['compr'];}else{echo "<span style='color:red'>NaN</span>";}
						echo "</td>\n";
						echo "</tr>\n";
					}
					echo "</table>\n";

					echo "<p><i>A FAIRE:</i></p>\n";
					echo "<ul>\n";
					echo "<li>Permettre d'ajouter une ou des machines dans une opération de clonage</li>\n";
					echo "<li>Supprimer automatiquement les opérations udpcast_recepteur si on supprime l'action udpcast_emetteur</li>\n";
					echo "</ul>\n";
				}
				else {
					echo "<p>Aucun clonage n'est en attente.</p>\n";
				}
			}
			else {
				echo "<h2>Clonage n°$num_op</h2>\n";

				$sql="SELECT * FROM se3_tftp_action WHERE num_op='$num_op';";
				$res=mysql_query($sql);
				$nombre_machine=mysql_num_rows($res);
				if($nombre_machine>0) {

					echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
					echo "<input type=\"hidden\" name=\"mode_rech\" value=\"$mode_rech\" />\n";

					echo "<table class='crob'>\n";
					echo "<tr>\n";
					echo "<th>Numéro d'opération</th>\n";
					echo "<th>Nom</th>\n";
					echo "<th>Statut</th>\n";
					echo "<th>Supprimer l'action<br />\n";
					echo "<a href='#' onclick='check_suppr(\"check\");return false'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Tout cocher\" title=\"Tout cocher\" /></a>\n";
					echo " / <a href='#' onclick='check_suppr(\"uncheck\");return false'><img src=\"../elements/images/disabled.gif\" border='0' alt=\"Tout décocher\" title=\"Tout décocher\" /></a>\n";
					echo "</th>\n";
					echo "</tr>\n";

					$cpt=0;
					while($lig=mysql_fetch_object($res)) {
						echo "<tr>\n";
						echo "<td>$lig->num_op</td>\n";
						echo "<td>$lig->name</td>\n";
						echo "<td>";
						if($lig->type=="udpcast_emetteur") {echo "Emetteur";} else {echo "Récepteur";}
						echo "</td>\n";

						echo "<td>\n";
						echo "<input type='checkbox' name='suppr[]' id='suppr_".$cpt."' value='$lig->id' />\n";
						echo "</td>\n";

						echo "</tr>\n";
						$cpt++;
					}

					echo "</table>\n";

					echo "<script type='text/javascript'>
	function check_suppr(mode) {
		for(i=0;i<$nombre_machine;i++){
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

					echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider les suppressions\" /></p>\n";
					echo "</form>\n";
				}
				else {
					echo "<p>Aucune machine ne correspond au numéro d'opération choisi.</p>\n";
				}
			}
			//echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au menu de consultation</a>.</p>\n";
		}
		echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au menu de consultation</a>.</p>\n";
	}
}
else {
	print (gettext("Vous n'avez pas les droits nécessaires pour ouvrir cette page..."));
}

// Footer
include ("pdp.inc.php");

?>
