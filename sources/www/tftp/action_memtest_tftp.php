<?php
/* $Id: action_memtest_tftp.php 9151 2016-02-08 01:05:04Z keyser $
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
$_SESSION["pageaide"]="Le_module_Clonage_des_stations#Programmer_un_memtest";

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


	// Création de la table dès que possible:
	creation_tftp_tables();

	// Paramètres SliTaz:
	/*
	$nom_image=isset($_POST['nom_image']) ? $_POST['nom_image'] : (isset($_GET['nom_image']) ? $_GET['nom_image'] : NULL);
	$src_part=isset($_POST['src_part']) ? $_POST['src_part'] : (isset($_GET['src_part']) ? $_GET['src_part'] : NULL);
	$dest_part=isset($_POST['dest_part']) ? $_POST['dest_part'] : (isset($_GET['dest_part']) ? $_GET['dest_part'] : NULL);
	*/
	$auto_reboot=isset($_POST['auto_reboot']) ? $_POST['auto_reboot'] : (isset($_GET['auto_reboot']) ? $_GET['auto_reboot'] : NULL);
	$delais_reboot=isset($_POST['delais_reboot']) ? $_POST['delais_reboot'] : (isset($_GET['delais_reboot']) ? $_GET['delais_reboot'] : NULL);

	// Paramètres concernant l'action immédiate sur les machines choisies:
	$wake=isset($_POST['wake']) ? $_POST['wake'] : (isset($_GET['wake']) ? $_GET['wake'] : "n");
	$shutdown_reboot=isset($_POST['shutdown_reboot']) ? $_POST['shutdown_reboot'] : (isset($_GET['shutdown_reboot']) ? $_GET['shutdown_reboot'] : NULL);


	echo "<h1>".gettext("Action rapport TFTP")."</h1>\n";

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
		echo "<p>Cette page doit vous permettre de programmer un test de mémoire vive sur les machines choisies.<br />Le dispositif ne permet hélas pas de récupérer un rapport sur l'état des barrettes.<br />Il faut passer devant les stations pour constater l'état et taper sur ECHAP pour quitter ensuite.</p>\n";

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

				//echo "<th>Sauvegarde</th>\n";
				echo "<th>Rapport<br />\n";
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
							// mp_curr[0]["macAddress"] correspond à une adresse mac recherchée dans l'annuaire LDAP.
							// Si les machines ont été changées et que l'on a ré-attribué le nom, il faut penser à nettoyer l'entrée dans l'annuaire:
							// source /usr/share/se3/sbin/variables_admin_ldap.sh
							// ldapdelete -x -D $ROOTDN -w $PASSDN cn=NOM_MACHINE,ou=Computers,$BASEDN
							// Et se reconnecter une fois sur la machine pour que le connexion.pl renseigne une nouvelle entrée cn=NOM_MACHINE
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
				echo "<h2>Paramétrage de l'action memtest</h2>\n";

				$nombre_machines=count($id_machine);
				if($nombre_machines==0){
					echo "<p>ERREUR: Il faut choisir au moins une machine.</p>\n";

					echo "<p><a href='#' onclick='history.go(-1);'>Retour au choix des machines sur lesquelles lancer le test de mémoire vive</a>.</p>\n";

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
				echo "<p>Machine$s concernée$s: $chaine</p>\n";


				// Date pour le nom de l'image à générer:
				$aujourdhui = getdate();
				$mois_se3 = sprintf("%02d",$aujourdhui['mon']);
				$jour_se3 = sprintf("%02d",$aujourdhui['mday']);
				$annee_se3 = $aujourdhui['year'];
				$heure_se3 = sprintf("%02d",$aujourdhui['hours']);
				$minute_se3 = sprintf("%02d",$aujourdhui['minutes']);
				$seconde_se3 = sprintf("%02d",$aujourdhui['seconds']);

				$date_se3=$annee_se3.$mois_se3.$jour_se3;

				echo "<p>Choisissez les paramètres pour le lancement du test de mémoire vive&nbsp;: <br />\n";

				echo "<table border='0'>\n";
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

				echo "<p align='center'><input type=\"submit\" name=\"validation_parametres\" value=\"Valider\" /></p>\n";
				echo "</form>\n";

			}
			else {
				echo "<h2>Validation des paramètres du test de mémoire vive</h2>\n";

				echo "<p>Génération du fichier dans /tftpboot/pxelinux.cfg/ pour la remontée de rapports.<br />\n";

				// BOUCLE SUR LA LISTE DES $id_machine[$i]

				// Numéro de l'opération de test de mémoire vive:
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
							$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'memtest' '$corrige_mac' '$ip_machine' '$nom_machine'", $retour);
	
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
	
								$timestamp=time();
								$sql="INSERT INTO se3_tftp_action SET id='$id_machine[$i]',
																		mac='$mac_machine',
																		name='$nom_machine',
																		date='$timestamp',
																		type='memtest',
																		num_op='$num_op',
																		infos='';";
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
								// On donne 4H pour que la récup soit effectuée:
								$timestamp_limit=time()+4*3600;
								//fwrite($fich,"/usr/share/se3/scripts/recup_rapport.php '$id_machine[$i]' '$ip_machine' 'rapport' '$timestamp_limit'");
								fwrite($fich,"sudo /usr/share/se3/scripts/recup_rapport.php '$id_machine[$i]' '$ip_machine' 'rapport' '$timestamp_limit'");
								fclose($fich);
								chmod($lanceur_recup,0750);
								*/
	
								// Ménage dans les tâches précédentes
								@exec("sudo /usr/share/se3/scripts/se3_tftp_menage_atq.sh $id_machine[$i]",$retour);
	
								/*
								// Planification de la tâche
								//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
								@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
								//passthru("at -f $lanceur_recup now + 1 minute",$retour);
								if($retour) {
									echo "<span style='color:red;'>ECHEC de la planification de la tâche.</span><br />\n";
									for($j=0;$j<count($retour);$j++){echo "$retour[$j]<br />\n";}
									//echo "$retour<br />\n";
									$temoin_erreur="y";
								}
								*/
	
								/*
								// Avec ça on arrive à récupérer l'info:
								//	-warning: commands will be executed using /bin/sh -
								//	-job 1572 at 2008-03-01 15:13 -
								// Mais une fois le at repoussé, ce n'est plus www-se3, mais root qui en est proprio...
								if(file_exists("$dossier/at.txt")) {
									$fp=fopen("$dossier/at.txt","r");
									while(!feof($fp)) {
										$ligne=fgets($fp,4096);
										echo "<p>-".$ligne."-</p>";
									}
									fclose($fp);
								}
								*/
	
								/*
								$fp=popen("at -f $lanceur_recup now + 1 minute","r");
								while(!feof($fp)) {
									$ligne=fgets($fp,4096);
									echo "<p>-".$ligne."-</p>";
								}
								fclose($fp);
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
						echo "<p>Pour information, voici le contenu du fichier généré:<br />\n";
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
}
else {
	print (gettext("Vous n'avez pas les droits nécessaires pour ouvrir cette page..."));
}

// Footer
include ("pdp.inc.php");
?>
