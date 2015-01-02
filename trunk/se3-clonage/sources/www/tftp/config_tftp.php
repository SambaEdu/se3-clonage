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
$_SESSION["pageaide"]="Le_module_Clonage_des_stations#Configurer_le_module_TFTP";

// On active les rapports d'erreurs:
//error_reporting(E_ALL);

// CSS pour mes tableaux:
echo "<link type='text/css' rel='stylesheet' href='tftp.css' />\n";

if (is_admin("system_is_admin",$login)=="Y")
{
	//debug_var();

        $msg="";
	if(isset($_POST['config_tftp'])){
		//echo "PLOP";
		//$msg="";
		$tftp_aff_menu_pxe=isset($_POST['tftp_aff_menu_pxe']) ? $_POST['tftp_aff_menu_pxe'] : NULL;
		$tftp_pass_menu_pxe=isset($_POST['tftp_pass_menu_pxe']) ? $_POST['tftp_pass_menu_pxe'] : NULL;
		$tftp_slitaz_cmdline=isset($_POST['tftp_slitaz_cmdline']) ? $_POST['tftp_slitaz_cmdline'] : NULL;
		$tftp_delais_boot_pxe=isset($_POST['tftp_delais_boot_pxe']) ? $_POST['tftp_delais_boot_pxe'] : 8;

		if(!preg_match("/^[0-9]*$/",$tftp_delais_boot_pxe)) {
			$tftp_delais_boot_pxe=8;
		}

		$msg="";
		$nb_reg=0;
		if(isset($tftp_aff_menu_pxe)) {
			if($tftp_aff_menu_pxe=='y') {
				$resultat1=crob_setParam('tftp_aff_menu_pxe','y','affichage menu tftp');
				$resultat1bis=crob_setParam('tftp_delais_boot_pxe',"$tftp_delais_boot_pxe",'delais boot pxe');
				if($resultat1) {$nb_reg++;}
				//echo "/usr/bin/sudo /usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh 'menu'<br />";
				$resultat2=exec("/usr/bin/sudo /usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh 'menu' '$tftp_delais_boot_pxe'", $retour);
				foreach($retour as $key => $value) {echo "\$retour[$key]=$value<br />";}
			}
			else {
				$resultat1=crob_setParam('tftp_aff_menu_pxe','n','affichage menu tftp');
				if($resultat1) {$nb_reg++;}
				//echo "/usr/bin/sudo /usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh 'standard'<br />";
				$resultat=exec("/usr/bin/sudo /usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh 'standard'", $retour);
				foreach($retour as $key => $value) {echo "\$retour[$key]=$value<br />";}
			}
		}

		//if(isset($tftp_pass_menu_pxe)) {
		if((isset($tftp_pass_menu_pxe))&&(isset($tftp_aff_menu_pxe))&&($tftp_aff_menu_pxe=='y')) {
			$resultat1=crob_setParam('tftp_pass_menu_pxe',"$tftp_pass_menu_pxe",'mot de passe boot pxe');
			if($resultat1) {$nb_reg++;}
			//echo "/usr/bin/sudo /usr/share/se3/scripts/set_password_menu_tftp.sh '$tftp_pass_menu_pxe'<br />";
			$resultat=exec("/usr/bin/sudo /usr/share/se3/scripts/set_password_menu_tftp.sh '$tftp_pass_menu_pxe'", $retour);
			foreach($retour as $key => $value) {echo "\$retour[$key]=$value<br />";}
		}

		if(isset($tftp_slitaz_cmdline)) {
			// Le pipe | est utilise dans la commande sed de /usr/share/se3/scripts/se3_pxe_slitaz_cmdline.sh 
			$tftp_slitaz_cmdline=preg_replace("/|/","",$tftp_slitaz_cmdline);

			$resultat1=crob_setParam('tftp_slitaz_cmdline',"$tftp_slitaz_cmdline",'option de la ligne de commande');
			if($resultat1) {$nb_reg++;}
			//echo "/usr/bin/sudo /usr/share/se3/scripts/se3_pxe_slitaz_cmdline.sh '$tftp_slitaz_cmdline'<br />";
			$resultat=exec("/usr/bin/sudo /usr/share/se3/scripts/se3_pxe_slitaz_cmdline.sh '$tftp_slitaz_cmdline'", $retour);
			foreach($retour as $key => $value) {echo "\$retour[$key]=$value<br />";}
		}

		if($nb_reg>0) {
			$msg="Enregistrement effectu&#233;: ".strftime("%d/%m/%Y - %H:%M:%S").".";
		}
	}

	creation_tftp_tables();

	echo "<h1>".gettext("Configuration TFTP")."</h1>\n";
	if(isset($_POST['action'])){
		if($_POST['action']=='download_sysresccd') {
			echo "Lancement du t&#233;l&#233;chargement de System Rescue CD...";
			system("/usr/bin/sudo /usr/share/se3/scripts/se3_get_sysresccd.sh mode=html 2>&1");
		}
		elseif($_POST['action']=='download_slitaz') {
			echo "Lancement du t&#233;l&#233;chargement de SliTaz...";
			system("/usr/bin/sudo /usr/share/se3/scripts/se3_get_slitaz.sh mode=html 2>&1");
		}
		elseif($_POST['action']=='download_udpcast') {
			echo "Lancement du t&#233;l&#233;chargement de Udpcast...";
			system("/usr/bin/sudo /usr/share/se3/scripts/se3_get_udpcast.sh mode=html 2>&1");
		}
		elseif($_POST['action']=='download_pxe_client_linux') {
			echo "Lancement du t&#233;l&#233;chargement du dispositif d'installation client Linux...";

			if(isset($_POST['choix_interface_client_linux'])) {
				$valeur=(isset($_POST['proposer_no_preseed'])) ? "yes" : "no";

				echo "<p>";
				$resultat2=crob_setParam('CliLinNoPreseed',"$valeur","Proposer l installation de client Linux libre sans preseed.");
				if($resultat2) {
					echo "<span style='color:green'>Enregistrement de la valeur '$valeur' pour 'CliLinNoPreseed' effectué.</span><br />";
				}
				else {
					echo "<span style='color:red'>Erreur lors de l'enregistrement de la valeur '$valeur' pour 'CliLinNoPreseed'.</span><br />";
				}

				$valeur=(isset($_POST['proposer_xfce64'])) ? "yes" : "no";

				$resultat2=crob_setParam('CliLinXfce64',"$valeur","Proposer l installation de client Linux avec interface Xfce64.");
				if($resultat2) {
					echo "<span style='color:green'>Enregistrement de la valeur '$valeur' pour 'CliLinXfce64' effectué.</span><br />";
				}
				else {
					echo "<span style='color:red'>Erreur lors de l'enregistrement de la valeur '$valeur' pour 'CliLinXfce64'.</span><br />";
				}

				$valeur=(isset($_POST['proposer_lxde'])) ? "yes" : "no";

				$resultat2=crob_setParam('CliLinLXDE',"$valeur","Proposer l installation de client Linux avec interface LXDE.");
				if($resultat2) {
					echo "<span style='color:green'>Enregistrement de la valeur '$valeur' pour 'CliLinLXDE' effectué.</span><br />";
				}
				else {
					echo "<span style='color:red'>Erreur lors de l'enregistrement de la valeur '$valeur' pour 'CliLinLXDE'.</span><br />";
				}

				$valeur=(isset($_POST['proposer_gnome'])) ? "yes" : "no";
				$resultat2=crob_setParam('CliLinGNOME',"$valeur","Proposer l installation de client Linux avec interface GNOME.");
				if($resultat2) {
					echo "<span style='color:green'>Enregistrement de la valeur '$valeur' pour 'CliLinGNOME' effectué.</span><br />";
				}
				else {
					echo "<span style='color:red'>Erreur lors de l'enregistrement de la valeur '$valeur' pour 'CliLinGNOME'.</span><br />";
				}
			}

			$suppr_dispositif_precedent=isset($_POST['suppr_dispositif_precedent']) ? " suppr_dispositif_precedent" : "";
			system("/usr/bin/sudo /usr/share/se3/scripts/se3_get_install_client_linux.sh mode=html $suppr_dispositif_precedent 2>&1");
		}
		elseif($_POST['action']=='miroir_apt_client_linux') {
			$MiroirAptCliLin=isset($_POST['MiroirAptCliLin']) ? "yes" : "no";
			$resultat1=crob_setParam('MiroirAptCliLin',$MiroirAptCliLin,'Utiliser un miroir apt maison pour les installations client linux.');
			if($resultat1) {
				echo "<span style='color:green'>Enregistrement de la valeur ".$MiroirAptCliLin." pour 'MiroirAptCliLin' effectué.</span><br />";
			}
			else {
				echo "<span style='color:red'>Erreur lors de l'enregistrement de la valeur ".$MiroirAptCliLin." pour 'MiroirAptCliLin'.</span><br />";
			}

			$resultat2=crob_setParam('MiroirAptCliLinIP',$_POST['MiroirAptCliLinIP'],'IP du miroir apt pour les installations client linux.');
			if($resultat2) {
				echo "<span style='color:green'>Enregistrement de la valeur ".$_POST['MiroirAptCliLinIP']." pour 'MiroirAptCliLinIP' effectué.</span><br />";
			}
			else {
				echo "<span style='color:red'>Erreur lors de l'enregistrement de la valeur ".$_POST['MiroirAptCliLinIP']." pour 'MiroirAptCliLinIP'.</span><br />";
			}

			$resultat3=crob_setParam('MiroirAptCliLinChem',$_POST['MiroirAptCliLinChem'],'Chemin du miroir apt pour les installations client linux.');
			if($resultat3) {
				echo "<span style='color:green'>Enregistrement de la valeur ".$_POST['MiroirAptCliLinChem']." pour 'MiroirAptCliLinChem' effectué.</span><br />";
			}
			else {
				echo "<span style='color:red'>Erreur lors de l'enregistrement de la valeur ".$_POST['MiroirAptCliLinChem']." pour 'MiroirAptCliLinChem'.</span><br />";
			}
		}
		else {
			echo "<span style='color:red'>Choix de telechargement invalide.</span><br />";
		}
		echo "<a href=".$_SERVER['PHP_SELF'].">Retour </a>";
		exit;
	}
	if($msg!="") {echo "<div style='text-align:center; color:red'>$msg</div>\n";}

	$se3ip=crob_getParam('se3ip');

	echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
	echo "<table class='crob' width=\"100%\">\n";
	echo "<tr>\n";
	echo "<th>Nom</th>\n";
	echo "<th>Valeur</th>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>Utiliser le menu graphique&nbsp;:</td>\n";
	echo "<td>\n";
	$tftp_aff_menu_pxe=crob_getParam('tftp_aff_menu_pxe');
	echo "<input type='radio' name='tftp_aff_menu_pxe' id='tftp_aff_menu_pxe_y' value='y' onchange='maj_affichage_options()' ";
	if($tftp_aff_menu_pxe=='y') {echo "checked ";}
	echo "/><label for='tftp_aff_menu_pxe_y'> Oui</label><br />\n";
	echo "<input type='radio' name='tftp_aff_menu_pxe' id='tftp_aff_menu_pxe_n' value='n' onchange='maj_affichage_options()' ";
	if($tftp_aff_menu_pxe!='y') {echo "checked ";}
	echo "/><label for='tftp_aff_menu_pxe_n'> Non</label>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_tftp_pass_menu_pxe'>\n";
	echo "<td>Prot&#233;ger les choix maintenance <b>dans le menu</b> par un mot de passe&nbsp;:<br />\n";
	echo "Laisser vide pour 'Pas de mot de passe'<br />\n";
	//echo "<b>ATTENTION&nbsp;:</b> Actuellement le mot de passe est en clair dans /tftpboot/pxelinux.cfg/default. Il est donc imp&#233;ratif d'utiliser un mot de passe different de admin ou adminse3</i></td>\n";
	echo "<i>Le mot de passe sera crypt&#233 dans /tftpboot/pxelinux.cfg/default et ne circulera pas en clair sur le r&#233seau.</i>\n";
	echo "</td>\n";
	echo "<td valign='top'>\n";
	$tftp_pass_menu_pxe=crob_getParam('tftp_pass_menu_pxe');
	echo "<input type='text' name='tftp_pass_menu_pxe' value='$tftp_pass_menu_pxe' ";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr id='tr_tftp_delais_boot'>\n";
	echo "<td>Temps d'affichage du menu<br />(<i>D&#233;lais avant de booter le choix par d&#233;faut</i>)&nbsp;:</td>\n";
	echo "<td valign='top'>\n";
	$tftp_delais_boot_pxe=crob_getParam('tftp_delais_boot_pxe');
	if($tftp_delais_boot_pxe=="") {$tftp_delais_boot_pxe=8;}
	echo "<input type='text' name='tftp_delais_boot_pxe' id='tftp_delais_boot_pxe' value='$tftp_delais_boot_pxe' onkeydown=\"clavier_up_down_increment('tftp_delais_boot_pxe',event,1,100);\" autocomplete=\"off\" />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>Lors du boot SliTaz ou SysRescCD, passer les param&#232;tres suivants en cmdline<br />\n";
	echo "<i>Exemples&nbsp;:</b> cle_ssh=http://$se3ip:909/tftp/paquet_cles_pub_ssh.tar.gz<br />Il faudra cr&#233;er l'archive paquet_cles_pub_ssh.tar.gz dans /var/www/se3/tftp/ avec les cl&#233;s ssh publiques que vous souhaitez.</td>\n";
	echo "<td valign='top'>\n";
	$tftp_slitaz_cmdline=crob_getParam('tftp_slitaz_cmdline');
	echo "<input type='text' name='tftp_slitaz_cmdline' value='$tftp_slitaz_cmdline' />\n";
	echo "<input type=\"hidden\" name=\"config_tftp\" value=\"y\" />\n";
	echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider les choix\" /></p>\n";
	echo "</form>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<br /><br />";

	//========================================================================

	echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
	//echo "<fieldset>\n";

	echo "<table class='crob' width=\"100%\">\n";
	echo "<tr>\n";
	echo "<th>Mise en place de System rescue CD</th>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>\n";
	$version_srcd_en_place=crob_getParam('srcd_version');
	$version_autorun2_en_place=crob_getParam('srcd_autorun2_vers');
	$version_scripts_en_place=crob_getParam('srcd_scripts_vers');
	if($version_srcd_en_place!='') {
		echo "<div align='center'>\n";
		echo "<div id='div_versions_sysresccd'><p>Version de SystemRescueCD en place&nbsp;:</p>
<table class='crob'>
<tr>
	<th>&nbsp;</th>
	<th>Sur votre SE3</th>
</tr>
<tr>
	<th>SystemRescueCD</th>
	<td>$version_srcd_en_place</td>
</tr>
<tr>
	<th>Autorun</th>
	<td>$version_autorun2_en_place</td>
</tr>
<tr>
	<th>Scripts</th>
	<td>$version_scripts_en_place</td>
</tr>
</table></div>\n";

		echo "<script type='text/javascript'>
		// <![CDATA[
		function check_versions_sysresccd() {
			new Ajax.Updater($('div_versions_sysresccd'),'ajax_lib.php?mode=check_versions_sysresccd',{method: 'get'});
		}
		//]]>
	</script>\n";
		echo "<p><a href='#' onclick='check_versions_sysresccd();return false;'>Tester la présence de mises à jour</a></p>\n";
		echo "</div>\n";
	}
	else {
		echo "<p style='text-align:center; color:red'>SystemRescueCD est absent ou la version en place n'est pas enregistree/versionnee dans la base.</p>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr><td>";
	echo "<input type='hidden' name='action' value='download_sysresccd' />";
	echo "T&#233;l&#233;charger system rescue cd (~274Mo) afin de l'utiliser à la place de slistaz / udpcast.<br> Avantage : en g&#233;n&#233;ral system rescue cd fonctionne sur davantage de mat&#233;riels recents.<br>\n";
	echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Lancer le T&#233;l&#233;chargement\" /></p>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";

	//echo "</fieldset>\n";
	echo "</form>\n";

	//========================================================================

	echo "<br /><br />";

	//========================================================================

	echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
	//echo "<fieldset>\n";

	echo "<table class='crob' width=\"100%\">\n";
	echo "<tr>\n";
	echo "<th>Mise en place de Udpcast</th>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>\n";
	$udpcast_noyo_version=crob_getParam('udpcast_noyo_version');
	if(!file_exists('/tftpboot/vmlu26')) {$udpcast_noyo_version.=" <span style='color:red'>Absent???</span>";}
	$udpcast_initrd_version=crob_getParam('udpcast_initrd_version');
	if(!file_exists('/tftpboot/udprd')) {$udpcast_initrd_version.=" <span style='color:red'>Absent???</span>";}
	$udpcast_noyo_old_version=crob_getParam('udpcast_noyo_old_version');
	if(!file_exists('/tftpboot/vmlu26.old')) {$udpcast_noyo_old_version.=" <span style='color:red'>Absent???</span>";}
	$udpcast_initrd_old_version=crob_getParam('udpcast_initrd_old_version');
	if(!file_exists('/tftpboot/udprd.old')) {$udpcast_initrd_old_version.=" <span style='color:red'>Absent???</span>";}

	if($udpcast_noyo_version!='') {
		echo "<div align='center'>\n";
		echo "<div id='div_versions_udpcast'><p>Version de Udpcast en place&nbsp;:</p>
<table class='crob'>
<tr>
	<th>&nbsp;</th>
	<th>Sur votre SE3</th>
</tr>
<tr>
	<th>Noyau</th>
	<td>$udpcast_noyo_version</td>
</tr>
<tr>
	<th>Initrd</th>
	<td>$udpcast_initrd_version</td>
</tr>
<tr>
	<th>Ancien noyau</th>
	<td>$udpcast_noyo_old_version</td>
</tr>
<tr>
	<th>Ancien initrd</th>
	<td>$udpcast_initrd_old_version</td>
</tr>
</table></div>\n";

		echo "<script type='text/javascript'>
		// <![CDATA[
		function check_versions_udpcast() {
			new Ajax.Updater($('div_versions_udpcast'),'ajax_lib.php?mode=check_versions_udpcast',{method: 'get'});
		}
		//]]>
	</script>\n";
		echo "<p><a href='#' onclick='check_versions_udpcast();return false;'>Tester la présence de mises à jour</a></p>\n";
		echo "</div>\n";
	}
	else {
		echo "<p style='text-align:center; color:red'>Udpcast est absent ou la version en place n'est pas enregistree/versionnee dans la base.</p>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr><td>";
	echo "<input type='hidden' name='action' value='download_udpcast' />";
	echo "T&#233;l&#233;charger udpcast.<br>\n";
	echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Lancer le T&#233;l&#233;chargement\" /></p>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";

	//echo "</fieldset>\n";
	echo "</form>\n";

	//========================================================================

	echo "<br /><br />";

	//========================================================================

	echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
	//echo "<fieldset>\n";

	echo "<table class='crob' width=\"100%\">\n";
	echo "<tr>\n";
	echo "<th>Mise en place de SliTaz</th>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>\n";
	$slitaz_noyo_version=crob_getParam('slitaz_noyo_version');
	if(!file_exists('/tftpboot/bzImage')) {$slitaz_noyo_version.=" <span style='color:red'>Absent???</span>";}
	$slitaz_roofs_version=crob_getParam('slitaz_roofs_version');
	if(!file_exists('/tftpboot/rootfs.gz')) {$slitaz_roofs_version.=" <span style='color:red'>Absent???</span>";}

	if($slitaz_noyo_version!='') {
		echo "<div align='center'>\n";
		echo "<div id='div_versions_slitaz'><p>Version de SliTaz en place&nbsp;:</p>
<table class='crob'>
<tr>
	<th>&nbsp;</th>
	<th>Sur votre SE3</th>
</tr>
<tr>
	<th>Noyau</th>
	<td>$slitaz_noyo_version</td>
</tr>
<tr>
	<th>Rootfs</th>
	<td>$slitaz_roofs_version</td>
</tr>
</table></div>\n";

		echo "<script type='text/javascript'>
		// <![CDATA[
		function check_versions_slitaz() {
			new Ajax.Updater($('div_versions_slitaz'),'ajax_lib.php?mode=check_versions_slitaz',{method: 'get'});
		}
		//]]>
	</script>\n";
		echo "<p><a href='#' onclick='check_versions_slitaz();return false;'>Tester la présence de mises à jour</a></p>\n";
		echo "</div>\n";
	}
	else {
		echo "<p style='text-align:center; color:red'>SliTaz est absent ou la version en place n'est pas enregistree/versionnee dans la base.</p>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr><td>";
	echo "<input type='hidden' name='action' value='download_slitaz' />";
	echo "T&#233;l&#233;charger SliTaz.<br>\n";
	echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Lancer le T&#233;l&#233;chargement\" /></p>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";

	//echo "</fieldset>\n";
	echo "</form>\n";

	//========================================================================

	echo "<br /><br />";

	//========================================================================

	echo "<table class='crob' width=\"100%\">\n";
	echo "<tr>\n";
	echo "<th>Mise en place du dispositif d'installation de clients Linux</th>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>\n";

	$dossier_ressource_dispositif_pxe_client_linux="/tftpboot/client_linux";

	$VarchPxeClientLin_en_place=crob_getParam('VarchPxeClientLin');
	if(!file_exists($dossier_ressource_dispositif_pxe_client_linux.'/install_client_linux_archive-tftp.tar.gz')) {$VarchPxeClientLin_en_place.=" <span style='color:red'>Absent???</span>";}

	$VscriptPxeClientLin_en_place=crob_getParam('VscriptPxeClientLin');
	if(!file_exists($dossier_ressource_dispositif_pxe_client_linux.'/install_client_linux_mise_en_place.sh')) {$VscriptPxeClientLin_en_place.=" <span style='color:red'>Absent???</span>";}

	if($VarchPxeClientLin_en_place!='') {
		echo "<div align='center'>\n";
		echo "<div id='div_versions_pxe_client_linux'><p>Version du dispositif client Linux&nbsp;:</p>
<table class='crob'>
<tr>
	<th>&nbsp;</th>
	<th>Sur votre SE3</th>
</tr>
<tr>
	<th>Archive</th>
	<td>$VarchPxeClientLin_en_place</td>
</tr>
<tr>
	<th>Script</th>
	<td>$VscriptPxeClientLin_en_place</td>
</tr>
</table></div>\n";

		echo "<script type='text/javascript'>
		// <![CDATA[
		function check_versions_pxe_client_linux() {
			new Ajax.Updater($('div_versions_pxe_client_linux'),'ajax_lib.php?mode=check_versions_pxe_client_linux',{method: 'get'});
		}
		//]]>
	</script>\n";
		echo "<p><a href='#' onclick='check_versions_pxe_client_linux();return false;'>Tester la présence de mises à jour</a></p>\n";
		echo "</div>\n";
	}
	else {
		echo "<p style='text-align:center; color:red'>Le dispositif d'installation PXE de client Linux est absent ou la version en place n'est pas enregistree/versionnee dans la base.</p>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	$checked_CliLinNoPreseed="";
	$CliLinNoPreseed=crob_getParam('CliLinNoPreseed');
	if($CliLinNoPreseed=="yes") {$checked_CliLinNoPreseed=" checked";}

	$checked_CliLinXfce64="";
	$CliLinXfce64=crob_getParam('CliLinXfce64');
	if($CliLinXfce64=="yes") {$checked_CliLinXfce64=" checked";}

	$checked_CliLinLXDE="";
	$CliLinLXDE=crob_getParam('CliLinLXDE');
	if($CliLinLXDE=="yes") {$checked_CliLinLXDE=" checked";}

	$checked_CliLinGNOME="";
	$CliLinGNOME=crob_getParam('CliLinGNOME');
	if($CliLinGNOME=="yes") {$checked_CliLinGNOME=" checked";}

	echo "<tr>
	<td>
		<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">
			<p>Le dispositif propose par defaut l'installation de clients Linux i386 avec l'interface Xfce.<br />
			Vous pouvez choisir d'autres interfaces, mais pour vous simplifier la gestion evitez d'installer trop d'interfaces differentes.</p>

			<p>Proposer aussi les interfaces suivantes&nbsp;:<br />
			<input type='checkbox' name='proposer_xfce64' id='proposer_xfce64' value='yes'$checked_CliLinXfce64 /><label for='proposer_xfce64'>Xfce avec choix 64bit</label><br />
			<input type='checkbox' name='proposer_lxde' id='proposer_lxde' value='yes'$checked_CliLinLXDE /><label for='proposer_lxde'>LXDE (i386 et 64)</label><br />
			<input type='checkbox' name='proposer_gnome' id='proposer_gnome' value='yes'$checked_CliLinGNOME /><label for='proposer_gnome'>GNOME (i386 et 64)</label></p>
			<p><input type='checkbox' name='proposer_no_preseed' id='proposer_no_preseed' value='yes'$checked_CliLinNoPreseed /><label for='proposer_no_preseed'>Proposer l'installation manuelle sans Preseed (i386 et 64).</label></p>

			<input type='hidden' name='choix_interface_client_linux' value='yes' />
			<input type='hidden' name='suppr_dispositif_precedent' value='yes' />
			<input type='hidden' name='action' value='download_pxe_client_linux' />
			<p><input type=\"submit\" value=\"Valider\" /></p>
		</form>
	</td>
</tr>
<tr>
	<td>
		<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">
			<input type='checkbox' name='suppr_dispositif_precedent' id='suppr_dispositif_precedent' value='yes' /><label for='suppr_dispositif_precedent'>Supprimer le dispositif actuellement en place et relancer le téléchargement.</label><br />
			<input type='hidden' name='action' value='download_pxe_client_linux' />
			<p><input type=\"submit\" value=\"Valider\" /></p>
		</form>
	</td>
</tr>
<tr>
	<td>
		<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">
			<input type='hidden' name='action' value='download_pxe_client_linux' />
			T&#233;l&#233;charger le dispositif.<br>
			<p align='center'><input type=\"submit\" name=\"submit\" value=\"Lancer le T&#233;l&#233;chargement\" /></p>
		</form>
	</td>
</tr>
</table>\n";

	//========================================================================

	echo "<br /><br />";

	//========================================================================

	$MiroirAptCliLin=crob_getParam('MiroirAptCliLin');
	$MiroirAptCliLinIP=crob_getParam('MiroirAptCliLinIP');
	$MiroirAptCliLinChem=crob_getParam('MiroirAptCliLinChem');

	echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
	echo "<div align='center'>\n";

	echo "<table class='crob' width=\"100%\">\n";
	echo "<tr>\n";
	echo "<th>Miroir APT pour l'installation des clients Linux</th>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>\n";
	echo "
	<p>Un miroir APT permet de conserver les paquets téléchargés lors de l'installation d'un client Linux.<br />
	Les paquets ainsi conservés peuvent ensuite être fournis plus rapidement lors de l'installation d'autres clients sans trop peser sur votre connexion internet.</p>

	<p>Par défaut, un tel miroir est créé sur le serveur SE3 lui-même dans /var/se3/apt-cacher-ng<br />
	Si cela vous convient, laissez vides les champs ci-dessous.<br />
	Si vous disposez ailleurs d'un autre miroir, vous pouvez le paramétrer ci-dessous&nbsp;</p>

	<table class='crob' align='center'>
	<tr>
		<th><label for='MiroirAptCliLin'>Utiliser un <span style='color:red'>autre</span> miroir APT que le SE3</label></th>
		<td><input type='checkbox' name='MiroirAptCliLin' id='MiroirAptCliLin' value='yes' ".(($MiroirAptCliLin=="yes") ? "checked" : "")."/></td>
	</tr>
	<tr>
		<th><label for='MiroirAptCliLinIP'>IP du miroir APT</label></th>
		<td><input type='text' name='MiroirAptCliLinIP' id='MiroirAptCliLinIP' value='$MiroirAptCliLinIP' /></td>
	</tr>
	<tr>
		<th><label for='MiroirAptCliLinChem'>Chemin du miroir APT</label></th>
		<td><input type='text' name='MiroirAptCliLinChem' id='MiroirAptCliLinChem' value='$MiroirAptCliLinChem' /></td>
	</tr>
	</table>

	<input type='hidden' name='action' value='miroir_apt_client_linux' />
	<p><input type=\"submit\" name=\"submit\" value=\"Valider\" /></p>

</form>\n";

	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";

	echo "</div>\n";
	echo "</form>\n";

	//========================================================================

	echo "<script type='text/javascript'>
	function maj_affichage_options() {
		if(document.getElementById('tftp_aff_menu_pxe_y').checked==true) {
			document.getElementById('tr_tftp_pass_menu_pxe').style.display='';
			document.getElementById('tr_tftp_delais_boot').style.display='';
		}
		else {
			document.getElementById('tr_tftp_pass_menu_pxe').style.display='none';
			document.getElementById('tr_tftp_delais_boot').style.display='none';
		}

	}

	maj_affichage_options();

	function clavier_up_down_increment(n,e,vmin,vmax){
		//alert(n);
		// Fonction destinée à incrémenter/décrémenter le champ courant entre 0 et 255 (pour des composantes de couleurs)
		// Modifié pour aller de vmin à vmax
		touche= e.keyCode ;
		//alert('touche='+touche);
		if (touche == '40') {
			valeur=document.getElementById(n).value;
			if(valeur>vmin){
				valeur--;
				document.getElementById(n).value=valeur;
			}
		}
		else{
			if (touche == '38') {
				valeur=document.getElementById(n).value;
				if(valeur<vmax){
					valeur++;
					document.getElementById(n).value=valeur;
				}
			}
			else{
				if(touche == '34'){
					valeur=document.getElementById(n).value;
					if(valeur>vmin+10){
						valeur=valeur-10;
					}
					else{
						valeur=vmin;
					}
					document.getElementById(n).value=valeur;
				}
				else{
					if(touche == '33'){
						valeur=document.getElementById(n).value;
						if(valeur<vmax-10){
							//valeur=valeur+10;
							//valeur+=10;
							valeur=eval(valeur)+10;
						}
						else{
							valeur=vmax;
						}
						document.getElementById(n).value=valeur;
					}
				}
			}
		}
	}

</script>\n";

}
else {
	print (gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page..."));
}

// Footer
include ("pdp.inc.php");

?>
