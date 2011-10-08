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
$_SESSION["pageaide"]="Le_module_Clonage_des_stations#Programmer_une_restauration";

// On active les rapports d'erreurs:
//error_reporting(E_ALL);

// Bibliothèque prototype Ajax pour afficher en décalé l'état des machines:
echo "<script type='text/javascript' src='../includes/prototype.js'></script>\n";

// CSS pour mes tableaux:
echo "<link type='text/css' rel='stylesheet' href='tftp.css' />\n";

if ((is_admin("system_is_admin",$login)=="Y")||(ldap_get_right("parc_can_clone",$login)=="Y"))
{
	// Choix des parcs:
	$parc=isset($_POST['parc']) ? $_POST['parc'] : (isset($_GET['parc']) ? $_GET['parc'] : NULL);
	// Choix des machines:
	$id_machine=isset($_POST['id_machine']) ? $_POST['id_machine'] : (isset($_GET['id_machine']) ? $_GET['id_machine'] : NULL);

	$parametrage_action=isset($_POST['parametrage_action']) ? $_POST['parametrage_action'] : (isset($_GET['parametrage_action']) ? $_GET['parametrage_action'] : NULL);

	$distrib=isset($_POST['distrib']) ? $_POST['distrib'] : "slitaz";
	$sysresccd_kernel=isset($_POST['sysresccd_kernel']) ? $_POST['sysresccd_kernel'] : "rescuecd";

	// Création de la table dès que possible:
	creation_tftp_tables();

	// Paramètres SliTaz:
	$nom_image=isset($_POST['nom_image']) ? $_POST['nom_image'] : (isset($_GET['nom_image']) ? $_GET['nom_image'] : NULL);
	$src_part=isset($_POST['src_part']) ? $_POST['src_part'] : (isset($_GET['src_part']) ? $_GET['src_part'] : NULL);
	$dest_part=isset($_POST['dest_part']) ? $_POST['dest_part'] : (isset($_GET['dest_part']) ? $_GET['dest_part'] : NULL);
	$auto_reboot=isset($_POST['auto_reboot']) ? $_POST['auto_reboot'] : (isset($_GET['auto_reboot']) ? $_GET['auto_reboot'] : NULL);
	$delais_reboot=isset($_POST['delais_reboot']) ? $_POST['delais_reboot'] : (isset($_GET['delais_reboot']) ? $_GET['delais_reboot'] : NULL);

	// Paramètres concernant l'action immédiate sur les machines choisies:
	$wake=isset($_POST['wake']) ? $_POST['wake'] : (isset($_GET['wake']) ? $_GET['wake'] : "n");
	$shutdown_reboot=isset($_POST['shutdown_reboot']) ? $_POST['shutdown_reboot'] : (isset($_GET['shutdown_reboot']) ? $_GET['shutdown_reboot'] : NULL);

	$type_src_part=isset($_POST['type_src_part']) ? $_POST['type_src_part'] : "partition";
	$src_srv=isset($_POST['src_srv']) ? $_POST['src_srv'] : "";
	$src_partage=isset($_POST['src_partage']) ? $_POST['src_partage'] : "";
	$src_sous_dossier=isset($_POST['src_sous_dossier']) ? $_POST['src_sous_dossier'] : "";
	$src_compte=isset($_POST['src_compte']) ? $_POST['src_compte'] : "";
	$src_mdp=isset($_POST['src_mdp']) ? $_POST['src_mdp'] : "";

	$type_svg=isset($_POST['type_svg']) ? $_POST['type_svg'] : "partimage";

	echo "<h1>".gettext("Action restauration TFTP")."</h1>\n";

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

	if(!isset($parc)){

		echo "<p>Choisissez un ou des parcs:</p>\n";

		$list_parcs=search_machines("objectclass=groupOfNames","parcs");
		if ( count($list_parcs)==0) {
			echo "<br><br>";
			echo gettext("Il n'existe aucun parc. Vous devez d'abord créer un parc");
			include ("pdp.inc.php");
			exit;
		}
		sort($list_parcs);

		echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";

		// Affichage des parcs sur 3/4 colonnes
		$nb_parcs_par_colonne=round(count($list_parcs)/3);
		echo "<table border='0'>\n";
		echo "<tr valign='top'>\n";
		echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
		echo "<td align='left'>\n";
		for ($loop=0; $loop < count($list_parcs); $loop++) {
			if(($loop>0)&&(round($loop/$nb_parcs_par_colonne)==$loop/$nb_parcs_par_colonne)){
				echo "</td>\n";
				echo "<td align='left'>\n";
			}

			if(($restriction_parcs=="n")||(in_array($list_parcs[$loop]["cn"], $tab_delegated_parcs))) {
				echo "<label for='parc_$loop'><input type='checkbox' id='parc_$loop' name='parc[]' value=\"".$list_parcs[$loop]["cn"]."\"";
				if(count($list_parcs)==1) {echo " checked";}
				echo " />".$list_parcs[$loop]["cn"]."</label>\n";
				echo "<br />\n";
			}
		}

		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider\" /></p>\n";

		echo "</form>\n";


		echo "<script type='text/javascript'>
nb_parcs=0;
id_parc='';
for(i=0;i<$loop;i++) {
	if(document.getElementById('parc_'+i)) {
		nb_parcs++;
		id_parc='parc_'+i;
	}
}
if(nb_parcs==1) {
	document.getElementById(id_parc).checked=true;
}
</script>\n";

		echo "<p><a href='index.php'>Retour à l'index</a>.</p>\n";
	}
	else {
		if(!isset($_POST['parametrage_action'])){

			echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
			echo "<input type=\"hidden\" name=\"parametrage_action\" value=\"1\" />\n";
			$max_eff_parc=0;
			for($i=0;$i<count($parc);$i++){

				echo "<h2>Parc $parc[$i]</h2>\n";
				echo "<input type=\"hidden\" name=\"parc[]\" value=\"$parc[$i]\" />\n";

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

				//echo "<th>Restauration</th>\n";
				echo "<th>Restauration<br />\n";
				echo "<a href='#' onclick='check_machine($i,\"check\");return false'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Tout cocher\" title=\"Tout cocher\" /></a>\n";
				echo " / <a href='#' onclick='check_machine($i,\"uncheck\");return false'><img src=\"../elements/images/disabled.gif\" border='0' alt=\"Tout décocher\" title=\"Tout décocher\" /></a>\n";
				echo "</th>\n";
				echo "<th>Actions programmées</th>\n";
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


						// Sélection des machines à sauvegarder:
						echo "<td width='20%'>\n";
						/*
						foreach($mp_curr[0] as $champ => $valeur) {
							echo "\$mp_curr[0]['$champ']=$valeur<br />";
						}
						*/
						if($id_machine!=""){
							echo "<input type='checkbox' name='id_machine[]' id='machine_".$i."_".$loop."' value='$id_machine' />\n";
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Il faut commencer par effectuer la configuration DHCP\" title=\"Il faut commencer par effectuer la configuration DHCP\" />";
						}
						echo "</td>\n";


						// Action programmée
						echo "<td>\n";
						if($id_machine!=""){
							$sql="SELECT * FROM se3_tftp_action WHERE id='".$id_machine."';";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								$lig=mysql_fetch_object($res);
								echo "<a href='visu_action.php?id_machine=$id_machine' target='_blank'>$lig->type programmé(e)</a>";
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'action programmée\" title=\"Pas d'action programmée\" />";
							}
						}
						echo "</td>\n";

						echo "</tr>\n";
					}
				}
				echo "</table>\n";
				if($max_eff_parc<$loop) {$max_eff_parc=$loop;}
			}

			echo "<script type='text/javascript'>
	function check_machine(num_parc,mode) {
		for(i=0;i<$max_eff_parc;i++){
			if(document.getElementById('machine_'+num_parc+'_'+i)){
				if(mode=='check'){
					document.getElementById('machine_'+num_parc+'_'+i).checked=true;
				}
				else{
					document.getElementById('machine_'+num_parc+'_'+i).checked=false;
				}
			}
		}
	}
</script>\n";

			echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider\" /></p>\n";
			echo "</form>\n";


		}
		else {
			$validation_parametres=isset($_POST['validation_parametres']) ? $_POST['validation_parametres'] : (isset($_GET['validation_parametres']) ? $_GET['validation_parametres'] : NULL);
			if(!isset($validation_parametres)) {
				echo "<h2>Paramétrage de la restauration</h2>\n";

				$nombre_machines=count($id_machine);
				if($nombre_machines==0){
					echo "<p>ERREUR: Il faut choisir au moins une machine.</p>\n";

					echo "<p><a href='#' onclick='history.go(-1);'>Retour au choix des machines à restaurer</a>.</p>\n";

					echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";
					include ("pdp.inc.php");
					exit();
				}

				echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\" name=\"form1\">\n";
				echo "<input type=\"hidden\" name=\"parametrage_action\" value=\"1\" />\n";
				// Liste des parcs:
				for($i=0;$i<count($parc);$i++){
					echo "<input type=\"hidden\" name=\"parc[]\" value=\"$parc[$i]\" />\n";
				}

				// Liste des machines sur lesquelles lancer la restauration:
				$chaine="";
				for($i=0;$i<count($id_machine);$i++){
					if($i>0) {$chaine.=", ";}
					$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine[$i]."';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)>0) {
						$lig=mysql_fetch_object($res);
						$chaine.=$lig->name;
						echo "<input type=\"hidden\" name=\"id_machine[]\" value=\"$id_machine[$i]\" />\n";
					}
				}
				if(count($id_machine)>1){$s="s";}else{$s="";}
				echo "<p>Machine$s concernée$s: $chaine</p>\n";



				$aujourdhui = getdate();
				$mois_se3 = sprintf("%02d",$aujourdhui['mon']);
				$jour_se3 = sprintf("%02d",$aujourdhui['mday']);
				$annee_se3 = $aujourdhui['year'];
				$heure_se3 = sprintf("%02d",$aujourdhui['hours']);
				$minute_se3 = sprintf("%02d",$aujourdhui['minutes']);
				$seconde_se3 = sprintf("%02d",$aujourdhui['seconds']);

				$date_se3=$annee_se3.$mois_se3.$jour_se3;

				echo "<p>Choisissez les paramètres de restauration: <br />\n";

				$temoin_sysresccd=check_sysresccd_files();

				if($temoin_sysresccd=="y") {
					// Il faut aussi le noyau et l'initram.igz dans /tftpboot, 
					echo "<input type='radio' name='distrib' id='distrib_slitaz' value='slitaz' onchange='affiche_sections_distrib()' /><label for='distrib_slitaz'>Utiliser la distribution SliTaz</label><br />\n";
					echo "<input type='radio' name='distrib' id='distrib_sysresccd' value='sysresccd' onchange='affiche_sections_distrib()' checked /><label for='distrib_sysresccd'>Utiliser la distribution SysRescCD</label> (<i>plus long à booter et 300Mo de RAM minimum, mais meilleure détection des pilotes</i>)<br />\n";


echo "<div id='div_sysresccd_kernel'>\n";
echo "<table border='0'>\n";
echo "<tr>\n";
echo "<td valign='top'>\n";
echo "Utiliser le noyau&nbsp;: ";
echo "</td>\n";
echo "<td>\n";
echo "<input type='radio' name='sysresccd_kernel' id='sysresccd_kernel_rescuecd' value='rescuecd' checked /><label for='sysresccd_kernel_rescuecd'>rescuecd</label><br />\n";
echo "<input type='radio' name='sysresccd_kernel' id='sysresccd_kernel_altker32' value='altker32' /><label for='sysresccd_kernel_altker32'>altker32</label><br />\n";
echo "<input type='radio' name='sysresccd_kernel' id='sysresccd_kernel_rescue64' value='rescue64' /><label for='sysresccd_kernel_rescue64'>rescue64</label><br />\n";
echo "<input type='radio' name='sysresccd_kernel' id='sysresccd_kernel_altker64' value='altker64' /><label for='sysresccd_kernel_altker64'>altker64</label><br />\n";
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "</div>\n";

				}
				else {
					echo "<input type=\"hidden\" name=\"distrib\" value=\"slitaz\" />\n";
				}

				echo "<table border='0'>\n";
				echo "<tr><td>Nom de l'image à restaurer: </td><td><input type='text' name='nom_image' value='' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Si vous laissez vide, la sauvegarde la plus récente réussie est utilisée.')")."\"><img name=\"action_image1\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td></tr>\n";

				echo "<tr><td>Partition à restaurer: </td><td><input type='text' name='dest_part' value='auto' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Proposer hda1, sda1,... selon les cas, ou laissez \'auto\' si la première partition du disque est bien la partition système à restaurer.')")."\"><img name=\"action_image2\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td></tr>\n";

				/*
				echo "<tr><td>Partition de stockage de l'image: </td><td><input type='text' name='src_part' value='auto' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Proposer hda5, sda5,... selon les cas, ou laissez \'auto\' si la première partition Linux (<i>ou à défaut W$ après la partition système</i>) est bien la partition de stockage.')")."\"><img name=\"action_image3\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td></tr>\n";
				*/

				$srcd_scripts_vers=crob_getParam('srcd_scripts_vers');
				if(($temoin_sysresccd=="y")&&($srcd_scripts_vers!='')&&($srcd_scripts_vers>=20111008)) {
					echo "<tr><td><input type='radio' name='type_src_part' id='type_src_part_partition' value='partition' checked /><label for='type_src_part_partition'> Partition de stockage de l'image: </label></td><td><input type='text' name='src_part' value='auto' />\n";
					echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Proposer hda5, sda5,... selon les cas, ou laissez \'auto\' si la première partition Linux (<i>ou à défaut W$ après la partition système</i>) est bien la partition de stockage.')")."\"><img name=\"action_image3\"  src=\"../elements/images/help-info.gif\"></u>\n";
					echo "</td></tr>\n";

					echo "<tr id='tr_src_part_smb'><td style='vertical-align:top'><b>Ou</b><br /><input type='radio' name='type_src_part' id='type_src_part_smb' value='smb' /><label for='type_src_part_smb'> Effectuer une restauration depuis un partage Window$/Samba&nbsp;:</label><br />(<i>tous les champs doivent être renseignés<br />si vous optez pour ce choix</i>)</td>\n";
					echo "<td>\n";
					echo "<br />\n";

					$svg_default_srv=crob_getParam('svg_default_srv');
					if($svg_default_srv=='') {$svg_default_srv=crob_getParam('se3ip');}
					$svg_default_partage=crob_getParam('svg_default_partage');
					$svg_default_dossier=crob_getParam('svg_default_dossier');
					$svg_default_compte=crob_getParam('svg_default_compte');

						echo "<table>\n";
						echo "<tr>\n";
						echo "<td>Serveur&nbsp;:</td>\n";
						echo "<td><input type='text' name='src_srv' id='src_srv' value='".$svg_default_srv."' onchange=\"document.getElementById('type_src_part_smb').checked=true;\" /></td>\n";
						echo "</tr>\n";

						echo "<tr>\n";
						echo "<td>Partage&nbsp;:</td>\n";
						echo "<td><input type='text' name='src_partage' id='src_partage' value='$svg_default_partage' onchange=\"document.getElementById('type_src_part_smb').checked=true;\" /></td>\n";
						echo "</tr>\n";

						echo "<tr>\n";
						echo "<td style='vertical-align:top;'>Sous-dossier&nbsp;:</td>\n";
						echo "<td>\n";
						echo "<input type='text' name='src_sous_dossier' id='src_sous_dossier' value='$svg_default_dossier' onchange=\"document.getElementById('type_src_part_smb').checked=true;\" />\n";
						echo "<br />\n";
						echo "Si un sous-dossier &lt;ADRESSE_MAC&gt; du dossier proposé ici existe, la sauvegarde y sera d'abord recherchée, avant de se rabattre sur le sous-dossier proposé lui-même si un tel dossier n'existe pas.\n";
						echo "</td>\n";
						echo "</tr>\n";

						echo "<tr>\n";
						echo "<td>Compte&nbsp;:</td>\n";
						echo "<td><input type='text' name='src_compte' id='src_compte' value='$svg_default_compte' onchange=\"document.getElementById('type_src_part_smb').checked=true;\" /></td>\n";
						echo "</tr>\n";

						echo "<tr>\n";
						echo "<td style='vertical-align:top'>Mot de passe&nbsp;:</td>\n";
						echo "<td><input type='text' name='src_mdp' id='src_mdp' value='' onchange=\"document.getElementById('type_src_part_smb').checked=true;\" autocomplete=\"off\" /><br /><b>Attention&nbsp;:</b> Le mot de passe circule en clair.<br />Evitez d'utiliser un compte comme admin ou adminse3.</td>\n";
						echo "</tr>\n";
						echo "</table>\n";
					echo "</td></tr>\n";

					/*
					// Le type de sauvegarde est indentifié par le script d'après le nom de l'image
					echo "<tr><td style='vertical-align:top'>Type de sauvegarde&nbsp;: </td>\n";
					echo "<td>\n";
					echo "<input type='radio' name='type_svg' id='type_svg_partimage' value='partimage' checked /><label for='type_svg_partimage'> partimage</label><br />\n";
					echo "<input type='radio' name='type_svg' id='type_svg_ntfsclone' value='ntfsclone' /><label for='type_svg_ntfsclone'> ntfsclone</label><br />\n";
					echo "<input type='radio' name='type_svg' id='type_svg_fsarchiver' value='fsarchiver' /><label for='type_svg_fsarchiver'> fsarchiver</label><br />\n";
					echo "</td></tr>\n";
					*/
				}
				else {
					echo "<tr><td>Partition de stockage: </td><td><input type='text' name='src_part' value='auto' />\n";
					echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Proposer hda5, sda5,... selon les cas, ou laissez \'auto\' si la première partition Linux (<i>ou à défaut W$ après la partition système</i>) est bien la partition de stockage.')")."\"><img name=\"action_image3\"  src=\"../elements/images/help-info.gif\"></u>\n";
					echo "</td></tr>\n";
				}


				if(($temoin_sysresccd=="y")&&(crob_getParam('srcd_scripts_vers')>='20110910')) {
					echo "<tr id='tr_authorized_keys'>\n";
					echo "<td>Url authorized_keys&nbsp;: </td>\n";
					echo "<td><input type='checkbox' name='prendre_en_compte_url_authorized_keys' value='y' /> \n";
					echo "<input type='text' name='url_authorized_keys' value='".crob_getParam('url_authorized_keys')."' size='40' />\n";
					echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Un fichier authorized_keys peut &ecirc;tre mis en place pour permettre un acc&egrave;s SSH au poste restaur&eacute;.')")."\">\n";
					echo "<img name=\"action_image3\"  src=\"../elements/images/help-info.gif\"></u>\n";
					echo "</td>\n";
					echo "</tr>\n";
				}

				echo "<tr><td valign='top'>Rebooter en fin de restauration: </td>\n";
				echo "<td>\n";
				echo "<input type='radio' name='auto_reboot' value='y' checked />\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr><td valign='top'>Eteindre en fin de restauration: </td>\n";
				echo "<td>\n";
				echo "<input type='radio' name='auto_reboot' value='halt' />\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr><td valign='top'>Ne pas rebooter ni éteindre la machine<br />en fin de restauration: </td>\n";
				echo "<td>\n";
				echo "<input type='radio' name='auto_reboot' value='n' />\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr><td valign='top'>\n";
				echo "Délai avant reboot/arrêt:</td>\n";
				echo "<td>\n";
				echo "<input type='text' name='delais_reboot' value='90' size='3' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Le délai doit être supérieur à 60 secondes pour permettre la récupération du rapport de restauration.')")."\"><img name=\"action_image4\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr><td valign='top'>Pour la ou les machines sélectionnées: </td>\n";
				echo "<td>\n";
					echo "<table border='0'>\n";
					echo "<tr><td valign='top'><input type='checkbox' id='wake' name='wake' value='y' checked /> </td><td><label for='wake'>Démarrer les machines par Wake-On-Lan/etherwake<br />si elles sont éteintes.</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_wait1' name='shutdown_reboot' value='wait1' /> </td><td><label for='shutdown_reboot_wait1'>Attendre le reboot des machines<br />même si aucune session n'est ouverte,</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_wait2' name='shutdown_reboot' value='wait2' checked /> </td><td><label for='shutdown_reboot_wait2'>Redémarrer les machines sans session ouverte<br />et attendre le reboot pour les machines<br />qui ont des sessions ouvertes,</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_reboot' name='shutdown_reboot' value='reboot' /> </td><td><label for='shutdown_reboot_reboot'>Redémarrer les machines<br />même si une session est ouverte (<i>pô cool</i>).</label></td></tr>\n";
					echo "</table>\n";
				echo "</td></tr>\n";

				echo "</table>\n";

				echo "<input type=\"hidden\" name=\"validation_parametres\" value=\"y\" />\n";

				echo "<p id='bouton_submit' style='text-align:center; display:none;'><input type=\"button\" name=\"bouton_validation_parametres2\" value=\"Valider\" onclick=\"check_smb_et_valide_formulaire('Un ou des champs ne sont pas remplis. Etes-vous s&ucirc;r de vouloir poursuivre ?');\" /></p>\n";

				echo "<noscript>";
				echo "<p align='center'><input type=\"submit\" name=\"bouton_validation_parametres\" value=\"Valider\" /></p>\n";
				echo "</noscript>";

				echo "</form>\n";

echo "<script type='text/javascript'>
// Si javascript est actif, on de-cache le bouton_submit:
if(document.getElementById('bouton_submit')) {document.getElementById('bouton_submit').style.display='';}

function affiche_sections_distrib() {
	if(document.getElementById('distrib_sysresccd').checked==true) {
		distrib='sysresccd';
	}
	else {
		distrib='slitaz';
	}
	//alert(distrib);

	if(distrib=='slitaz') {
		document.getElementById('div_sysresccd_kernel').style.display='none';
		document.getElementById('tr_authorized_keys').style.display='none';
		if(document.getElementById('tr_src_part_smb')) {document.getElementById('tr_src_part_smb').style.display='none';}
	}
	else {
		document.getElementById('div_sysresccd_kernel').style.display='block';
		document.getElementById('tr_authorized_keys').style.display='';
		if(document.getElementById('tr_src_part_smb')) {document.getElementById('tr_src_part_smb').style.display='';}
	}
}

affiche_sections_distrib();

function check_smb_et_valide_formulaire(themessage) {
	if(document.getElementById('type_src_part_smb')) {
		if(document.getElementById('type_src_part_smb').checked==true) {
			// On vérifie si les champs sont non vides
			src_srv=''
			src_partage=''
			src_sous_dossier=''
			src_compte=''
			src_mdp=''
			if(document.getElementById('src_srv')) {src_srv=document.getElementById('src_srv').value;}
			if(document.getElementById('src_partage')) {src_partage=document.getElementById('src_partage').value;}
			if(document.getElementById('src_sous_dossier')) {src_sous_dossier=document.getElementById('src_sous_dossier').value;}
			if(document.getElementById('src_compte')) {src_compte=document.getElementById('src_compte').value;}
			if(document.getElementById('src_mdp')) {src_mdp=document.getElementById('src_mdp').value;}

			if((src_srv!='')&&(src_partage!='')&&(src_sous_dossier!='')&&(src_compte!='')&&(src_mdp!='')) {
				document.form1.submit();
			}
			else {
				var is_confirmed = confirm(themessage);
				if(is_confirmed){
					document.form1.submit();
				}
			}
		}
		else {
			document.form1.submit();
		}
	}
	else {
		document.form1.submit();
	}
}
</script>\n";


				//======================================================
				$temoin_svg_existantes=0;
				$chaine_tab="<p>Liste des sauvegardes existantes:</p>\n";
				$chaine_tab.="<table class='crob'>\n";
				$chaine_tab.="<tr>\n";
				$chaine_tab.="<th>Id</th>\n";
				$chaine_tab.="<th>Nom</th>\n";
				$chaine_tab.="<th>Partition</th>\n";
				$chaine_tab.="<th>Sauvegarde</th>\n";
				$chaine_tab.="<th>Date</th>\n";
				$chaine_tab.="<th>Descriptif</th>\n";
				$chaine_tab.="</tr>\n";
				for($i=0;$i<count($id_machine);$i++){
					$sql="SELECT * FROM se3_tftp_sauvegardes WHERE id='".$id_machine[$i]."';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					$sql="SELECT * FROM se3_tftp_sauvegardes WHERE id='".$id_machine[$i]."' ORDER BY date DESC;";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)>0) {
						while($lig=mysql_fetch_object($res)) {
							$chaine_tab.="<tr>\n";
							$chaine_tab.="<td>$lig->id</td>\n";
							$chaine_tab.="<td>$lig->name</td>\n";
							$chaine_tab.="<td>$lig->partition</td>\n";
							$chaine_tab.="<td>$lig->image</td>\n";
							$chaine_tab.="<td>".mysql_date_to_fr_date($lig->date)."</td>\n";
							$chaine_tab.="<td style='text-align:left'><pre>$lig->descriptif</pre></td>\n";
							$chaine_tab.="</tr>\n";

							$temoin_svg_existantes++;
						}
					}
				}
				$chaine_tab.="</table>\n";
				$chaine_tab.="<p><br /></p>\n";
				if($temoin_svg_existantes>0) {
					echo $chaine_tab;
				}
				//======================================================

				echo "<p><i>NOTES:</i></p>\n";
				echo "<ul>\n";
				echo "<li>Ce choix nécessite une partition de sauvegarde sur la machine.</li>\n";
				echo "<li>Si le nom de l'image est laissé vide, c'est la sauvegarde la plus récente tagguée 'SUCCES' qui est utilisée.<br />C'est-à-dire qu'il doit exister un fichier NOM_SAUVEGARDE.SUCCES.txt<br />Les sauvegardes sont recherchées dans un dossier /oscar/ à la racine de la partition (<i>si le nom de l'image est laissé vide</i>).</li>\n";
				echo "<li>Il faudra stocker dans une table les informations sur les sauvegardes effectuées/trouvées pour pouvoir ne proposer ici que des choix valides.</li>\n";
				echo "<li><b>Attention:</b > Le délai avant reboot ajouté au temps de l'opération lancée doit dépasser la périodicité du script controle_actions_tftp.sh en crontab.<br />
				Ce délai doit aussi permettre de récupérer en http://IP_CLIENT/~hacker/Public/*.txt des informations sur le succès ou l'échec de l'opération.<br />
				Une tâche cron se charge d'effectuer le 'wget' sur les infos, puis le remplissage d'une table MySQL.<br />
				La tâche cron est lancée toutes les 60s.</li>\n";
				echo "<li>Pour que la restauration puisse être entièrement provoquée depuis le serveur, il faut que les postes clients soient configurés pour booter en PXE (<i>ou au moins s'éveiller (wol) en bootant sur le réseau</i>).<br />Dans le cas contraire, vous devrez passer sur les postes et presser F12 pour choisir de booter en PXE.</li>\n";
				echo "</ul>\n";


			}
			else {
				echo "<h2>Validation des paramètres de la restauration</h2>\n";

				$opt_url_authorized_keys="";
				if((isset($_POST['prendre_en_compte_url_authorized_keys']))&&(isset($_POST['url_authorized_keys']))&&($_POST['url_authorized_keys']!='')&&(preg_replace('|[A-Za-z0-9/:_\.\-]|','',$_POST['url_authorized_keys'])=='')) {
					$opt_url_authorized_keys="url_authorized_keys=".$_POST['url_authorized_keys'];
					crob_setParam('url_authorized_keys',$_POST['url_authorized_keys'],'Url fichier authorized_keys pour acces ssh aux clients TFTP');
				}

				echo "<p>Rappel des paramètres:</p>\n";

				$temoin_sysresccd=check_sysresccd_files();

				if($temoin_sysresccd=="y") {
					echo "<table class='crob'>\n";
					echo "<tr>\n";
					echo "<th style='text-align:left;'>Distribution linux à utiliser: </th>\n";
					echo "<td>\n";
					echo $distrib;
					if($distrib=='sysresccd') {
						echo " (<i>noyau $sysresccd_kernel</i>)";
					}
					echo "<input type=\"hidden\" name=\"distrib\" value=\"$distrib\" />\n";
					echo "</td>\n";
					echo "</tr>\n";
				}
				else {
					echo "<input type=\"hidden\" name=\"distrib\" value=\"slitaz\" />\n";
					echo "<table class='crob'>\n";
				}

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Nom de l'image: </th>\n";
				echo "<td>\n";
				if($nom_image=="") {echo "Détecté automatiquement lors de la restauration.";} else {echo $nom_image;}
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Partition à restaurer: </th>\n";
				echo "<td>\n";
				if($dest_part=="auto") {echo "Détectée automatiquement lors de la restauration.";} else {echo $dest_part;}
				echo "</td>\n";
				echo "</tr>\n";

				if($type_src_part=='partition') {
					echo "<tr>\n";
					echo "<th style='text-align:left;'>Partition de stockage de la sauvegarde: </th>\n";
					echo "<td>\n";
					if($src_part=="auto") {echo "Détectée automatiquement lors de la restauration.";} else {echo $src_part;}
					echo "</td>\n";
					echo "</tr>\n";
				}
				elseif($type_src_part=='smb') {
					if($src_srv!='') {crob_setParam('svg_default_srv',$_POST['src_srv'],'Serveur samba par défaut de destination des sauvegardes (TFTP)');}
					if($src_partage!='') {crob_setParam('svg_default_partage',$_POST['src_partage'],'Partage samba par défaut de destination des sauvegardes (TFTP)');}
					if($src_sous_dossier!='') {crob_setParam('svg_default_dossier',$_POST['src_sous_dossier'],'Sous-dossier par défaut de destination des sauvegardes (TFTP)');}
					if($src_compte!='') {crob_setParam('svg_default_compte',$_POST['src_compte'],'Compte par défaut pour le montage de la destination des sauvegardes (TFTP)');}

					echo "<tr>\n";
					echo "<th style='text-align:left; vertical-align:top;'>Sauvegarde dans un partage Window$/Samba: </th>\n";
					echo "<td>\n";
						echo "<table>\n";
						echo "<tr>\n";
						echo "<td>Serveur&nbsp;:</td>\n";
						echo "<td>$src_srv</td>\n";
						echo "</tr>\n";

						echo "<tr>\n";
						echo "<td>Partage&nbsp;:</td>\n";
						echo "<td>$src_partage</td>\n";
						echo "</tr>\n";

						echo "<tr>\n";
						echo "<td>Sous-dossier&nbsp;:</td>\n";
						echo "<td>$src_sous_dossier</td>\n";
						echo "</tr>\n";

						echo "<tr>\n";
						echo "<td>Compte&nbsp;:</td>\n";
						echo "<td>$src_compte</td>\n";
						echo "</tr>\n";

						echo "<tr>\n";
						echo "<td>Mot de passe&nbsp;:</td>\n";
						echo "<td>XXXXXXXX</td>\n";
						echo "</tr>\n";
						echo "</table>\n";

					echo "</td>\n";
					echo "</tr>\n";
				}
				else {
					echo "</table>\n";
					echo "<p style='color:red'>ANOMALIE&nbsp;: Le type de la destination de sauvegarde est inconnu.</p>\n";
					include ("pdp.inc.php");
					die();
				}

				/*
				echo "<tr>\n";
				echo "<th style='text-align:left;'>Type de sauvegarde: </th>\n";
				echo "<td>$type_svg</td>\n";
				echo "</tr>\n";
				*/

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Rebooter en fin de restauration: </th>\n";
				echo "<td>\n";
				echo $auto_reboot;
				echo "</td>\n";
				echo "</tr>\n";

				//if($auto_reboot=='y') {
				if(($auto_reboot=='y')||($auto_reboot=='halt')) {
					echo "<tr>\n";
					echo "<th style='text-align:left;'>Délai avant reboot: </th>\n";
					echo "<td>\n";
					echo "$delais_reboot s";
					echo "</td>\n";
					echo "</tr>\n";
				}

				echo "</table>\n";


				echo "<p>Génération du fichier dans /tftpboot/pxelinux.cfg/ pour la restauration.<br />\n";

				// BOUCLE SUR LA LISTE DES $id_machine[$i]

				// Numéro de l'opération de sauvegarde:
				$num_op=get_free_se3_action_tftp_num_op();
				for($i=0;$i<count($id_machine);$i++) {
					$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine[$i]."';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)==0) {
						echo "<span style='color:red;'>La machine d'identifiant $id_machine[$i] n'existe pas dans 'se3_dhcp'.</span><br />\n";
					}
					else {
						$temoin_erreur="n";

						$lig=mysql_fetch_object($res);
						$mac_machine=$lig->mac;
						$nom_machine=$lig->name;
						$ip_machine=$lig->ip;

						if($restriction_parcs=="y") {
							$temoin_erreur='y';
							for($loop=0; $loop<count($tab_delegated_parcs);$loop++) {
								// La machine est-elle dans un des parcs délégués?
								if(is_machine_in_parc($nom_machine,$tab_delegated_parcs[$loop])) {$temoin_erreur='n';break;}
							}
						}

						if($temoin_erreur=="y") {
							echo "<p style='color:red'>La machine $nom_machine ne vous est pas déléguée</p>\n";
						}
						else {
							echo "Génération pour $nom_machine: ";
	
							$corrige_mac=strtolower(strtr($mac_machine,":","-"));
	
							$chemin="/usr/share/se3/scripts";
	
							if($distrib=='slitaz') {
								$ajout_kernel="";
							}
							else {
								$ajout_kernel="|kernel=$sysresccd_kernel";
							}
	
							if($distrib=='slitaz') {
								//$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'restaure' '$corrige_mac' '$ip_machine' '$nom_machine' '$nom_image' '$src_part' '$dest_part' '$auto_reboot' '$delais_reboot'", $retour);
								$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'restaure' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine nom_image=$nom_image src_part=$src_part dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot'", $retour);

								$info_src_part=$src_part;
							}
							else {
								//$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_restaure' '$corrige_mac' '$ip_machine' '$nom_machine' '$nom_image' '$src_part' '$dest_part' '$auto_reboot' '$delais_reboot'", $retour);
								//$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_restaure' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine nom_image=$nom_image src_part=$src_part dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot kernel=$sysresccd_kernel $opt_url_authorized_keys'", $retour);

								if($type_src_part=='smb') {
									//$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_restaure' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine nom_image=$nom_image src_part=smb:$src_compte:$src_mdp@$src_srv:$src_partage:$src_sous_dossier dest_part=$dest_part type_svg=$type_svg auto_reboot=$auto_reboot delais_reboot=$delais_reboot kernel=$sysresccd_kernel $opt_url_authorized_keys'", $retour);
									$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_restaure' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine nom_image=$nom_image src_part=smb:$src_compte:$src_mdp@$src_srv:$src_partage:$src_sous_dossier dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot kernel=$sysresccd_kernel $opt_url_authorized_keys'", $retour);

									$info_src_part="smb:$src_compte:XXXXXXXX@$src_srv:$src_partage:$src_sous_dossier";
								}
								else {
									//$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_restaure' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine nom_image=$nom_image src_part=$src_part dest_part=$dest_part type_svg=$type_svg auto_reboot=$auto_reboot delais_reboot=$delais_reboot kernel=$sysresccd_kernel $opt_url_authorized_keys'", $retour);
									$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_restaure' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine nom_image=$nom_image src_part=$src_part dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot kernel=$sysresccd_kernel $opt_url_authorized_keys'", $retour);

									$info_src_part=$src_part;
								}
							}
	
							if(count($retour)>0){
								echo "<span style='color:red;'>ECHEC de la génération du fichier</span><br />\n";
								for($j=0;$j<count($retour);$j++){
									echo "$retour[$j]<br />\n";
								}
								$temoin_erreur="y";
							}
							else {
								$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine[$i]';";
								$suppr=mysql_query($sql);
	
								$timestamp=time();
								$sql="INSERT INTO se3_tftp_action SET id='$id_machine[$i]',
																		mac='$mac_machine',
																		name='$nom_machine',
																		date='$timestamp',
																		type='restauration',
																		num_op='$num_op',
																		infos='nom_image=$nom_image|src_part=$info_src_part|dest_part=$dest_part|auto_reboot=$auto_reboot|delais_reboot=${delais_reboot}$ajout_kernel';";
								$insert=mysql_query($sql);
								if(!$insert) {
									echo "<span style='color:red;'>ECHEC de l'enregistrement dans 'se3_tftp_action'</span><br />\n";
									$temoin_erreur="y";
								}
	
								// Génération du lanceur de récupération:
								//$dossier="/var/se3/tmp/tftp/$id_machine[$i]";
								$dossier="/etc/se3/www-tools/tftp/$id_machine[$i]";
								if(!file_exists($dossier)) { mkdir($dossier,0700);}
								$lanceur_recup="$dossier/lanceur_recup_rapport_action_tftp.sh";
								$fich=fopen($lanceur_recup,"w+");
								$timestamp_limit=time()+4*3600;
								//fwrite($fich,"/usr/share/se3/scripts/recup_rapport.php '$id_machine[$i]' '$ip_machine' 'restauration' '$timestamp_limit'");
								if($distrib=='slitaz') {
									$mode_restauration="restauration";
								}
								else {
									$mode_restauration="restauration_sysresccd";
								}
								fwrite($fich,"sudo /usr/share/se3/scripts/recup_rapport.php '$id_machine[$i]' '$ip_machine' '$mode_restauration' '$timestamp_limit'");
								fclose($fich);
								chmod($lanceur_recup,0750);
	
								// Ménage dans les tâches précédentes
								@exec("sudo /usr/share/se3/scripts/se3_tftp_menage_atq.sh $id_machine[$i]",$retour);
	
								// Planification de la tâche
								//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
								@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
								if($retour) {
									echo "<span style='color:red;'>ECHEC de la planification de la tâche.</span><br />\n";
									for($j=0;$j<count($retour);$j++){echo "$retour[$j]<br />\n";}
									$temoin_erreur="y";
								}
	
								if($temoin_erreur=="n") {
									echo "<span style='color:green;'>OK</span>\n";
									// Application de l'action choisie:
									echo " <span id='wake_shutdown_or_reboot_$i'></span>";
	
									echo "<script type='text/javascript'>
										// <![CDATA[
										new Ajax.Updater($('wake_shutdown_or_reboot_$i'),'ajax_lib.php?ip=$ip_machine&nom=$nom_machine&mode=wake_shutdown_or_reboot&wake=$wake&shutdown_reboot=$shutdown_reboot',{method: 'get'});
										//]]>
									</script>\n";
	
	
									echo "<br />\n";
								}
							}
						}
					}
				}

				// +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
				// POUVOIR TAGUER DANS LA TABLE se3_dhcp LES MACHINES QUI PEUVENT BOOTER EN PXE
				// Ajouter un champ?
				// +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-


				// On n'affiche le fichier que pour le dernier (à titre d'info):
				if(isset($corrige_mac)) {
					$fich=fopen("/tftpboot/pxelinux.cfg/01-$corrige_mac","r");
					if($fich) {
						echo "<p>Pour information, voici le contenu du fichier généré:<br />\n";
						echo "<pre style='color:green;'>";
						while(!feof($fich)) {
							$ligne=fgets($fich,4096);
							echo htmlentities($ligne);
						}
						echo "</pre>\n";
						fclose($fich);
					}
					else {
						echo "<p style='color:red;'>Il n'a pas été possible d'ouvrir le fichier /tftpboot/pxelinux.cfg/01-$corrige_mac</p>\n";
					}
				}
			}
		}
		echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";
	}
}
else {
	print (gettext("Vous n'avez pas les droits nécessaires pour ouvrir cette page..."));
}

// Footer
include ("pdp.inc.php");
?>
