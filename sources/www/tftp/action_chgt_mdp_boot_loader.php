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
$_SESSION["pageaide"]="Le_module_Clonage_des_stations#Changer_mdp_boot_loader";

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

	/*
	// Création de la table dès que possible:
	$sql="CREATE TABLE IF NOT EXISTS se3_tftp_action (
			id INT(11),
			mac VARCHAR(255),
			name VARCHAR(255),
			date INT(11),
			type VARCHAR(255),
			num_op INT(11)
			);";
	$creation_table=mysql_query($sql);
	*/
	creation_tftp_tables();

	// Paramètres SliTaz:
	/*
	$nom_image=isset($_POST['nom_image']) ? $_POST['nom_image'] : (isset($_GET['nom_image']) ? $_GET['nom_image'] : NULL);
	$src_part=isset($_POST['src_part']) ? $_POST['src_part'] : (isset($_GET['src_part']) ? $_GET['src_part'] : NULL);
	$dest_part=isset($_POST['dest_part']) ? $_POST['dest_part'] : (isset($_GET['dest_part']) ? $_GET['dest_part'] : NULL);
	*/
	$auto_reboot=isset($_POST['auto_reboot']) ? $_POST['auto_reboot'] : (isset($_GET['auto_reboot']) ? $_GET['auto_reboot'] : NULL);
	$delais_reboot=isset($_POST['delais_reboot']) ? $_POST['delais_reboot'] : (isset($_GET['delais_reboot']) ? $_GET['delais_reboot'] : NULL);

	$changer_mdp_linux=isset($_POST['changer_mdp_linux']) ? $_POST['changer_mdp_linux'] : (isset($_GET['changer_mdp_linux']) ? $_GET['changer_mdp_linux'] : "n");
	$changer_mdp_sauve=isset($_POST['changer_mdp_sauve']) ? $_POST['changer_mdp_sauve'] : (isset($_GET['changer_mdp_sauve']) ? $_GET['changer_mdp_sauve'] : "n");
	$changer_mdp_restaure=isset($_POST['changer_mdp_restaure']) ? $_POST['changer_mdp_restaure'] : (isset($_GET['changer_mdp_restaure']) ? $_GET['changer_mdp_restaure'] : "n");

	$mdp_linux=isset($_POST['mdp_linux']) ? $_POST['mdp_linux'] : (isset($_GET['mdp_linux']) ? $_GET['mdp_linux'] : "");
	$mdp_sauve=isset($_POST['mdp_sauve']) ? $_POST['mdp_sauve'] : (isset($_GET['mdp_sauve']) ? $_GET['mdp_sauve'] : "");
	$mdp_restaure=isset($_POST['mdp_restaure']) ? $_POST['mdp_restaure'] : (isset($_GET['mdp_restaure']) ? $_GET['mdp_restaure'] : "");

	// Paramètres concernant l'action immédiate sur les machines choisies:
	$wake=isset($_POST['wake']) ? $_POST['wake'] : (isset($_GET['wake']) ? $_GET['wake'] : "n");
	$shutdown_reboot=isset($_POST['shutdown_reboot']) ? $_POST['shutdown_reboot'] : (isset($_GET['shutdown_reboot']) ? $_GET['shutdown_reboot'] : NULL);


	echo "<h1>".gettext("Action changement de mot de passe Boot Loader")."</h1>\n";

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

	//echo "is_machine_in_parc('xpbof', 'parc_xp')=".is_machine_in_parc('xpbof', 'parc_xp')."<br />";

	//========================================
	$temoin_sysresccd=check_sysresccd_files();

	if($temoin_sysresccd!="y") {
		echo "<p style='color:red'>Le dispositif nécessite l'utilisation de SysRescCD.<br />Voir <a href='config_tftp.php'>Configuration TFTP</a></p>\n";
		include ("pdp.inc.php");
		die();
	}

	$srcd_scripts_vers=crob_getParam('srcd_scripts_vers');
	if(($srcd_scripts_vers=='')||($srcd_scripts_vers<20111003)) {
		echo "<p style='color:red'>Le dispositif nécessite des scripts SysRescCD en version supérieure ou égale à 20111003.<br />Voir <a href='config_tftp.php'>Configuration TFTP</a></p>\n";
		include ("pdp.inc.php");
		die();
	}
	//========================================

	if(!isset($parc)) {

		echo "<p>Choisissez un ou des parcs&nbsp;:</p>\n";

		$list_parcs=search_machines("objectclass=groupOfNames","parcs");
		if (count($list_parcs)==0) {
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
		if(!isset($_POST['parametrage_action'])) {

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

				//echo "<th>Sauvegarde</th>\n";
				echo "<th>Changer le mot de passe<br />\n";
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
				echo "<h2>Paramétrage du changement de mot de passe</h2>\n";

				$nombre_machines=count($id_machine);
				if($nombre_machines==0){
					echo "<p>ERREUR&nbsp;: Il faut choisir au moins une machine.</p>\n";

					echo "<p><a href='#' onclick='history.go(-1);'>Retour au choix des machines pour lesquelles changer le mot de passe du Boot Loader</a>.</p>\n";

					echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";
					include ("pdp.inc.php");
					exit();
				}

				echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
				echo "<input type=\"hidden\" name=\"parametrage_action\" value=\"1\" />\n";
				// Liste des parcs:
				for($i=0;$i<count($parc);$i++){
					echo "<input type=\"hidden\" name=\"parc[]\" value=\"$parc[$i]\" />\n";
				}

				// Liste des machines sur lesquelles lancer la sauvegarde:
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
				echo "<p>Machine$s concernée$s&nbsp;: $chaine</p>\n";


				// Date pour le nom de l'image à générer:
				$aujourdhui = getdate();
				$mois_se3 = sprintf("%02d",$aujourdhui['mon']);
				$jour_se3 = sprintf("%02d",$aujourdhui['mday']);
				$annee_se3 = $aujourdhui['year'];
				$heure_se3 = sprintf("%02d",$aujourdhui['hours']);
				$minute_se3 = sprintf("%02d",$aujourdhui['minutes']);
				$seconde_se3 = sprintf("%02d",$aujourdhui['seconds']);

				$date_se3=$annee_se3.$mois_se3.$jour_se3;

				echo "<p>Choisissez les paramètres de changement de mot de passe Boot Loader&nbsp;: <br />\n";

				$temoin_sysresccd=check_sysresccd_files();

				if($temoin_sysresccd!="y") {
					echo "<p style='color:red'>Le dispositif nécessite l'utilisation de SysRescCD.<br />Voir <a href='config_tftp.php'>Configuration TFTP</a></p>\n";
					include ("pdp.inc.php");
					die();
				}

				$srcd_scripts_vers=crob_getParam('srcd_scripts_vers');
				if(($srcd_scripts_vers=='')||($srcd_scripts_vers<20111003)) {
					echo "<p style='color:red'>Le dispositif nécessite des scripts SysRescCD en version supérieure ou égale à 20111003.<br />Voir <a href='config_tftp.php'>Configuration TFTP</a></p>\n";
					include ("pdp.inc.php");
					die();
				}

				if($temoin_sysresccd=="y") {
					// Il faut aussi le noyau et l'initram.igz dans /tftpboot, 
					//echo "<input type='radio' name='distrib' id='distrib_slitaz' value='slitaz' onchange='affiche_sections_distrib()' /><label for='distrib_slitaz'>Utiliser la distribution SliTaz</label><br />\n";
					echo "<input type='hidden' name='distrib' id='distrib_slitaz' value='slitaz' />\n";
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
				echo "<tr>\n";
				echo "<td>\n";
				echo "<input type='checkbox' name='changer_mdp_linux' id='changer_mdp_linux' value='y' />\n";
				//echo "</td>\n";
				//echo "<td>\n";
				echo "<label for='changer_mdp_linux'> Changer le mot de passe Linux&nbsp;: </label></td><td><input type='text' name='mdp_linux' id='mdp_linux' value='' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Si vous laissez vide, le mot de passe Linux sera vidé.')")."\"><img name=\"action_image1\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo " <a href='javascript:copier_pass()'><img src='../elements/images/magic.png' width='22' height='24' alt='Recopier le mot de passe pour Sauve et Restaure' title='Recopier le mot de passe pour Sauve et Restaure' /></a>\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<td>\n";
				echo "<input type='checkbox' name='changer_mdp_sauve' id='changer_mdp_sauve' value='y' />\n";
				//echo "</td>\n";
				//echo "<td>\n";
				echo "<label for='changer_mdp_sauve'> Changer le mot de passe Sauve&nbsp;: </label></td><td><input type='text' name='mdp_sauve' id='mdp_sauve' value='' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Si vous laissez vide, le mot de passe Sauve sera vidé.')")."\"><img name=\"action_image1\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<td>\n";
				echo "<input type='checkbox' name='changer_mdp_restaure' id='changer_mdp_restaure' value='y' />\n";
				//echo "</td>\n";
				//echo "<td>\n";
				echo "<label for='changer_mdp_restaure'> Changer le mot de passe Restaure&nbsp;: </label></td><td><input type='text' name='mdp_restaure' id='mdp_restaure' value='' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Si vous laissez vide, le mot de passe Restaure sera vidé.')")."\"><img name=\"action_image1\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td>\n";
				echo "</tr>\n";
				/*
				echo "<tr><td>Partition à sauvegarder: </td><td><input type='text' name='src_part' value='auto' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Proposer hda1, sda1,... selon les cas, ou laissez \'auto\' si la première partition du disque est bien la partition système à sauvegarder.')")."\"><img name=\"action_image2\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td></tr>\n";

				echo "<tr><td>Partition de stockage&nbsp;: </td><td><input type='text' name='dest_part' value='auto' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Proposer hda5, sda5,... selon les cas, ou laissez \'auto\' si la première partition Linux (<i>ou à défaut W$ après la partition système</i>) est bien la partition de stockage.')")."\"><img name=\"action_image3\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td></tr>\n";
				*/

				if(($temoin_sysresccd=="y")&&(crob_getParam('srcd_scripts_vers')>='20110910')) {
					echo "<tr id='tr_authorized_keys'>\n";
					echo "<td>Url authorized_keys&nbsp;: </td>\n";
					echo "<td><input type='checkbox' name='prendre_en_compte_url_authorized_keys' value='y' /> \n";
					echo "<input type='text' name='url_authorized_keys' value='".crob_getParam('url_authorized_keys')."' size='40' />\n";
					echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Un fichier authorized_keys peut &ecirc;tre mis en place pour permettre un acc&egrave;s SSH au poste sauvegard&eacute;.')")."\">\n";
					echo "<img name=\"action_image3\"  src=\"../elements/images/help-info.gif\"></u>\n";
					echo "</td>\n";
					echo "</tr>\n";
				}

				echo "<tr><td valign='top'>Rebooter en fin de sauvegarde&nbsp;: </td>\n";
				echo "<td>\n";
				echo "<input type='checkbox' name='auto_reboot' value='y' checked />\n";
				echo "</td>\n";
				echo "</tr>\n";
				/*
				echo "<tr><td valign='top'>Eteindre en fin de sauvegarde&nbsp;: </td>\n";
				echo "<td>\n";
				echo "<input type='radio' name='auto_reboot' value='halt' />\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr><td valign='top'>Ne pas rebooter ni éteindre la machine<br />en fin de sauvegarde&nbsp;: </td>\n";
				echo "<td>\n";
				echo "<input type='radio' name='auto_reboot' value='n' />\n";
				echo "</td>\n";
				echo "</tr>\n";
				*/

				echo "<tr><td valign='top'>\n";
				echo "Délai avant reboot/arrêt:</td>\n";
				echo "<td>\n";
				echo "<input type='text' name='delais_reboot' value='90' size='3' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Le délai doit être supérieur à 60 secondes pour permettre la récupération du rapport de sauvegarde.')")."\"><img name=\"action_image4\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr><td valign='top'>Pour la ou les machines sélectionnées&nbsp;: </td>\n";
				echo "<td>\n";
					echo "<table border='0'>\n";
					echo "<tr><td valign='top'><input type='checkbox' id='wake' name='wake' value='y' checked /> </td><td><label for='wake'>Démarrer les machines par Wake-On-Lan/etherwake<br />si elles sont éteintes.</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_wait1' name='shutdown_reboot' value='wait1' /> </td><td><label for='shutdown_reboot_wait1'>Attendre le reboot des machines<br />même si aucune session n'est ouverte,</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_wait2' name='shutdown_reboot' value='wait2' checked /> </td><td><label for='shutdown_reboot_wait2'>Redémarrer les machines sans session ouverte<br />et attendre le reboot pour les machines<br />qui ont des sessions ouvertes,</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_reboot' name='shutdown_reboot' value='reboot' /> </td><td><label for='shutdown_reboot_reboot'>Redémarrer les machines<br />même si une session est ouverte (<i>pô cool</i>).</label></td></tr>\n";
					echo "</table>\n";
				echo "</td></tr>\n";

				echo "</table>\n";

				echo "<p align='center'><input type=\"submit\" name=\"validation_parametres\" value=\"Valider\" /></p>\n";
				echo "</form>\n";


echo "<script type='text/javascript'>
function copier_pass() {
	if((document.getElementById('mdp_linux'))&&(document.getElementById('mdp_sauve'))) {
		document.getElementById('mdp_sauve').value=document.getElementById('mdp_linux').value;
	}
	if((document.getElementById('mdp_linux'))&&(document.getElementById('mdp_restaure'))) {
		document.getElementById('mdp_restaure').value=document.getElementById('mdp_linux').value;
	}
	if(document.getElementById('changer_mdp_linux')) {
		document.getElementById('changer_mdp_linux').checked=true;
	}
	if(document.getElementById('changer_mdp_sauve')) {
		document.getElementById('changer_mdp_sauve').checked=true;
	}
	if(document.getElementById('changer_mdp_restaure')) {
		document.getElementById('changer_mdp_restaure').checked=true;
	}
}

function affiche_sections_distrib() {
	if(document.getElementById('distrib_sysresccd').checked==true) {
		distrib='sysresccd';
	}
	else {
		distrib='slitaz';
	}
	
	if(distrib=='slitaz') {
		document.getElementById('div_sysresccd_kernel').style.display='none';
		document.getElementById('tr_authorized_keys').style.display='none';
	}
	else {
		document.getElementById('div_sysresccd_kernel').style.display='block';
		document.getElementById('tr_authorized_keys').style.display='';
	}
}

affiche_sections_distrib();
</script>\n";
/*
				//======================================================
				$temoin_svg_existantes=0;
				$chaine_tab="<p>Liste des sauvegardes existantes&nbsp;:</p>\n";
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
*/
				echo "<p><i>NOTES&nbsp;:</i></p>\n";
				echo "<ul>\n";
				echo "<li>Ce choix nécessite que SysRescCD soit installé sur une partition de la machine.</li>\n";
/*
				echo "<li><b>Attention&nbsp;:</b > Le délai avant reboot ajouté au temps de l'opération lancée doit dépasser la périodicité du script controle_actions_tftp.sh en crontab.<br />
				Ce délai doit aussi permettre de récupérer en http://IP_CLIENT/~hacker/Public/*.txt des informations sur le succès ou l'échec de l'opération.<br />
				Une tâche cron se charge d'effectuer le 'wget' sur les infos, puis le remplissage d'une table MySQL.<br />
				La tâche cron est lancée toutes les 60s.</li>\n";
				echo "<li>Si le nom de sauvegarde fourni correspond à un nom de sauvegarde existante, la sauvegarde précédente est supprimée.</li>\n";
				echo "<li>Pour que la sauvegarde puisse être entièrement provoquée depuis le serveur, il faut que les postes clients soient configurés pour booter en PXE (<i>ou au moins s'éveiller (wol) en bootant sur le réseau</i>).<br />Dans le cas contraire, vous devrez passer sur les postes et presser F12 pour choisir de booter en PXE.</li>\n";
*/
				echo "</ul>\n";
			}
			else {
				echo "<h2>Validation des paramètres de changement de mot de passe Boot Loader</h2>\n";
				//debug_var();

				$opt_url_authorized_keys="";
				if((isset($_POST['prendre_en_compte_url_authorized_keys']))&&(isset($_POST['url_authorized_keys']))&&($_POST['url_authorized_keys']!='')&&(preg_replace('|[A-Za-z0-9/:_\.\-]|','',$_POST['url_authorized_keys'])=='')) {
					$opt_url_authorized_keys="url_authorized_keys=".$_POST['url_authorized_keys'];
					crob_setParam('url_authorized_keys',$_POST['url_authorized_keys'],'Url fichier authorized_keys pour acces ssh aux clients TFTP');
				}

				echo "<p>Rappel des paramètres&nbsp;:</p>\n";

				$temoin_sysresccd=check_sysresccd_files();

				if($temoin_sysresccd=="y") {
					echo "<table class='crob'>\n";
					echo "<tr>\n";
					echo "<th style='text-align:left;'>Distribution linux à utiliser&nbsp;: </th>\n";
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
				echo "<th style='text-align:left;'>Changement du mot de passe Linux&nbsp;: </th>\n";
				echo "<td>\n";
				if($changer_mdp_linux=="y") {
					echo "<span style='color:green;'>Oui</span> avec le nouveau mot de passe Linux '$mdp_linux'.";
				}
				else {
					echo "<span style='color:red;'>Non</span>.";
				}
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Changement du mot de passe Sauve&nbsp;: </th>\n";
				echo "<td>\n";
				if($changer_mdp_sauve=="y") {
					echo "<span style='color:green;'>Oui</span> avec le nouveau mot de passe Sauve '$mdp_linux'.";
				}
				else {
					echo "<span style='color:red;'>Non</span>.";
				}
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Changement du mot de passe Restaure&nbsp;: </th>\n";
				echo "<td>\n";
				if($changer_mdp_restaure=="y") {
					echo "<span style='color:green;'>Oui</span> avec le nouveau mot de passe Restaure '$mdp_restaure'.";
				}
				else {
					echo "<span style='color:red;'>Non</span>.";
				}
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Rebooter en fin de changement de mot de passe&nbsp;: </th>\n";
				echo "<td>\n";
				if($auto_reboot=="y") {
					echo "<span style='color:green;'>Oui</span>.";
				}
				else {
					echo "<span style='color:red;'>Non</span>.";
				}
				echo "</td>\n";
				echo "</tr>\n";

				//if($auto_reboot=='y') {
				if(($auto_reboot=='y')||($auto_reboot=='halt')) {
					echo "<tr>\n";
					echo "<th style='text-align:left;'>Délai avant reboot&nbsp;: </th>\n";
					echo "<td>\n";
					echo "$delais_reboot s";
					echo "</td>\n";
					echo "</tr>\n";
				}
				echo "</table>\n";


				echo "<p>Génération du fichier dans /tftpboot/pxelinux.cfg/ pour le changement de mot de passe.<br />\n";

				// BOUCLE SUR LA LISTE DES $id_machine[$i]

				// Numéro de l'opération de sauvegarde:
				$num_op=get_free_se3_action_tftp_num_op();
				for($i=0;$i<count($id_machine);$i++) {
					$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine[$i]."';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)==0) {
						echo "<span style='color:red;'>La machine d'identifiant $id_machine[$i] n'existe pas dans 'se3_dhcp'.</span><br />\n";
						$traiter_machine_courante='n';
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
							echo "Génération pour $nom_machine&nbsp;: ";
	
							$corrige_mac=strtolower(strtr($mac_machine,":","-"));
	
							$chemin="/usr/share/se3/scripts";

							if($distrib=='slitaz') {
								$ajout_kernel="";
							}
							else {
								$ajout_kernel="|kernel=$sysresccd_kernel";
							}

							$infos_pxe="";
							if($changer_mdp_linux=='y') {$infos_pxe.="mdp_linux=$mdp_linux ";}
							if($changer_mdp_sauve=='y') {$infos_pxe.="mdp_sauve=$mdp_sauve ";}
							if($changer_mdp_restaure=='y') {$infos_pxe.="mdp_restaure=$mdp_restaure ";}

							if((isset($delais_reboot))&&($delais_reboot!='')) {
								$infos_pxe.=" delais_reboot=$delais_reboot";
							}

							if($distrib=='slitaz') {
								$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'chg_mdp_bootloader' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine $infos_pxe auto_reboot=$auto_reboot'", $retour);
								echo "/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'chg_mdp_bootloader' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine $infos_pxe auto_reboot=$auto_reboot'<br />";
							}
							else {
								$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'chg_mdp_bootloader_sysresccd' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine $infos_pxe auto_reboot=$auto_reboot kernel=$sysresccd_kernel $opt_url_authorized_keys'", $retour);
								echo "/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'chg_mdp_bootloader_sysresccd' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine $infos_pxe auto_reboot=$auto_reboot kernel=$sysresccd_kernel $opt_url_authorized_keys'<br />";
							}
	
							if(count($retour)>0){
								//echo "<p>";
								//echo "<span style='color:red;'>Il semble que la génération du fichier ait échoué...</span><br />\n";
								echo "<span style='color:red;'>ECHEC de la génération du fichier</span><br />\n";
								for($j=0;$j<count($retour);$j++){
									echo "$retour[$j]<br />\n";
								}
								$temoin_erreur="y";
								//echo "</p>\n";
							}
							else {
								$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine[$i]';";
								$suppr=mysql_query($sql);
	
								$infos_sql="";
								if($changer_mdp_linux=='y') {$infos_sql.="mdp_linux=$mdp_linux|";}
								if($changer_mdp_sauve=='y') {$infos_sql.="mdp_sauve=$mdp_sauve|";}
								if($changer_mdp_restaure=='y') {$infos_sql.="mdp_restaure=$mdp_restaure|";}
								$timestamp=time();
								$sql="INSERT INTO se3_tftp_action SET id='$id_machine[$i]',
																		mac='$mac_machine',
																		name='$nom_machine',
																		date='$timestamp',
																		type='chg_mdp_bootloader',
																		num_op='$num_op',
																		infos='".$infos_sql."auto_reboot=$auto_reboot|${ajout_kernel}';";
								$insert=mysql_query($sql);
								if(!$insert) {
									echo "<span style='color:red;'>ECHEC de l'enregistrement dans 'se3_tftp_action'</span><br />\n";
									$temoin_erreur="y";
								}

/*
								// Génération du lanceur de récupération:
								//$dossier="/var/se3/tmp/tftp/$id_machine[$i]";
								$dossier="/etc/se3/www-tools/tftp/$id_machine[$i]";
								if(!file_exists($dossier)) { mkdir($dossier,0700);}
								$lanceur_recup="$dossier/lanceur_recup_rapport_action_tftp.sh";
								$fich=fopen($lanceur_recup,"w+");
								$timestamp_limit=time()+4*3600;
								//fwrite($fich,"/usr/share/se3/scripts/recup_rapport.php '$id_machine[$i]' '$ip_machine' 'sauvegarde' '$timestamp_limit'");
								if($distrib=='slitaz') {
									$mode_sauvegarde="chg_mdp_bootloader";
								}
								else {
									$mode_sauvegarde="chg_mdp_bootloader_sysresccd";
								}
								fwrite($fich,"sudo /usr/share/se3/scripts/recup_rapport.php '$id_machine[$i]' '$ip_machine' '$mode_sauvegarde' '$timestamp_limit'");
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
*/
								if($temoin_erreur=="n") {
									//echo "<span style='color:green;'>OK</span><br />\n";
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
					//$fich=fopen("/tftpboot/pxelinux.cfg/01-$lig1->mac","r");
					$fich=fopen("/tftpboot/pxelinux.cfg/01-$corrige_mac","r");
					if($fich) {
						echo "<p>Pour information, voici le contenu du fichier généré&nbsp;:<br />\n";
						echo "<pre style='border:1px solid black; color:green;'>";
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

	echo "<p><em>NOTES&nbsp;:</em></p>
<ul>
<li><p>Cette page est destinée à effectuer le changement de mot de passe d'un LILO ou GRUB d'une distribution SysRescCD installée sur des postes.</p></li>
<li><p>Dans le cas d'un LILO installé, avec des mots de passe supprimés par précaution du lilo.conf, il est indispensable de changer tous les mots de passe (<em>Linux, Sauve et Restaure</em>), sinon c'est XXXXXX qui est pris comme nouveau mot de passe pour ces choix non faits.</p></li>
<li><p><b>Attention&nbsp;:</b> Les mots de passe circulent <b>en clair</b> sur le réseau le temps du boot des stations.<br />Evitez de choisir des mots de passe utilisés ailleurs pour protéger des données plus sensibles.</p></li>
</ul>\n";

}
else {
	print (gettext("Vous n'avez pas les droits nécessaires pour ouvrir cette page..."));
}

// Footer
include ("pdp.inc.php");
?>
