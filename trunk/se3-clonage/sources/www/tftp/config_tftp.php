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
		echo "Lancement du t&#233;l&#233;chargement de System Rescue CD....";
		system("/usr/bin/sudo /usr/share/se3/scripts/se3_get_sysresccd.sh mode=html 2>&1");

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
	echo "<input type='text' name='tftp_delais_boot_pxe' value='$tftp_delais_boot_pxe' />\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td>Lors du boot SliTaz, passer les param&#232;tres suivants en cmdline<br />\n";
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
        //echo "<table class='crob'>\n";
        echo "<table class='crob' width=\"100%\">\n";
        echo "<tr>\n";
        echo "<th>Mise en place de System rescue CD</th>\n";
//        echo "<th></th>";
        echo "</tr>\n";

        echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
        echo "<input type='hidden' name='action' value='download' ";
        echo "<tr><td>T&#233;l&#233;charger system rescue cd (~274Mo) afin de l'utiliser � la place de slistaz / udpcast.<br> Avantage : en g&#233;n&#233;ral system rescue cd fonctionne sur davantage de mat&#233;riels recents.<br>\n";
        echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Lancer le T&#233;l&#233;chargement\" /></p>\n";
//        echo "<td>";
        echo "</td>\n";

        echo "</tr>\n";


	echo "</table>\n";

	$version_srcd_en_place=crob_getParam('srcd_version');
	$version_autorun2_en_place=crob_getParam('srcd_autorun2_vers');
	$version_scripts_en_place=crob_getParam('srcd_scripts_vers');
	if($version_srcd_en_place!='') {
		echo "<div align='center'>\n";
		echo "<div id='div_versions'><p>Version de SystemRescueCD en place&nbsp;:</p>
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
			new Ajax.Updater($('div_versions'),'ajax_lib.php?mode=check_versions_sysresccd',{method: 'get'});
		}
		//]]>
	</script>\n";
		echo "<p><a href='#' onclick='check_versions_sysresccd();return false;'>Tester la pr�sence de mises � jour</a></p>\n";
		echo "</div>\n";
	}


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
</script>\n";

}
else {
	print (gettext("Vous n'avez pas les droits n&#233;cessaires pour ouvrir cette page..."));
}

// Footer
include ("pdp.inc.php");

?>
