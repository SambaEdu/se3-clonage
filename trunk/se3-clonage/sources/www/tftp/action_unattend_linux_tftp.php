<?php
/* $Id$
===========================================
   Projet SE3
   Dispositif SE3+TFTP+Sauvegarde/Restauration/Clonage
   Stephane Boireau
   Modifié par Guillaume Barré
 * modifs keyser 12/2014
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
$_SESSION["pageaide"]="Client_Linux#";

// On active les rapports d'erreurs:
//error_reporting(E_ALL);

// Bibliothèque prototype Ajax pour afficher en décalé l'état des machines:
echo "<script type='text/javascript' src='../includes/prototype.js'></script>\n";

// CSS pour mes tableaux:
echo "<link type='text/css' rel='stylesheet' href='tftp.css' />\n";

if (is_admin("system_is_admin",$login)!="Y") {
	print (gettext("Vous n'avez pas les droits n&eacute;cessaires pour ouvrir cette page..."));
        die();
}
include "tftp.inc.php";
	// Choix de l'OS
	$os=isset($_POST['os']) ? $_POST['os'] : (isset($_GET['os']) ? $_GET['os'] : NULL);
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
	//$auto_reboot=isset($_POST['auto_reboot']) ? $_POST['auto_reboot'] : (isset($_GET['auto_reboot']) ? $_GET['auto_reboot'] : NULL);
	//$delais_reboot=isset($_POST['delais_reboot']) ? $_POST['delais_reboot'] : (isset($_GET['delais_reboot']) ? $_GET['delais_reboot'] : NULL);

	// Paramètres concernant l'action immédiate sur les machines choisies:
	$wake=isset($_POST['wake']) ? $_POST['wake'] : (isset($_GET['wake']) ? $_GET['wake'] : "n");
	$shutdown_reboot=isset($_POST['shutdown_reboot']) ? $_POST['shutdown_reboot'] : (isset($_GET['shutdown_reboot']) ? $_GET['shutdown_reboot'] : NULL);


	echo "<h1>".gettext("Installation d'un client Linux".$os)."</h1>\n";
        
        
        // Verid présence dossier install
        $chemin_install="/home/netlogon/clients-linux/install";
        if(!file_exists($chemin_install)) {
            echo "<span style='color:red'>le dossier ".$chemin_install." est absent.</span><br />\n";
            echo "Effectuez le t&eacute;l&eacute;chargement du dispositif d'installation des postes Linux via la page suivante : <a href='config_tftp.php'>Configurer le module TFTP</a><br />\n";
            die();
	}
        
        
        
        
        
        
        
        // pour le moment on se contentera de Wheezy
        $os="Debian Wheezy";
        
	if(!isset($os)){
		echo "<p>Installation automatique de Debian Wheezy ou Ubuntu Trusty</p>\n";
		echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
		echo "  <ul>
    <li><input type='radio' name='os' id='windows' value='Windows' /><label for='windows'>Installer Windows</label></li>
    <li><input type='radio' name='os' id='wheezy' value='Debian Wheezy' /><label for='wheezy'>Installer Debian Wheezy</label></li>
    <li><input type='radio' name='os' id='lucid' value='Xubuntu Trusty' /><label for='lucid'>Installer Ubuntu Lucid</label></li>
  </ul>\n";
echo "<p align='center'><input type=\"submit\" name=\"validation_os\" value=\"Valider\" /></p>\n";
		echo "</form>\n";

		echo "<p><a href='index.php'>Retour à l'index</a>.</p>\n";
	}
	else{
		if(!isset($parc) && !isset($parc[0])){
                    choix_parc();
                    
                    //include('includes/01_choix_parc.php');
		}
		else {
			if(!isset($_POST['parametrage_action'])){
                            params_action($parc);
			//	include('includes/02_parametrage_action.php');
			}
			else {
				$validation_parametres=isset($_POST['validation_parametres']) ? $_POST['validation_parametres'] : (isset($_GET['validation_parametres']) ? $_GET['validation_parametres'] : NULL);
				if(!isset($validation_parametres)) {
					
					if($os=="Debian Wheezy") {
                                            $content = choix_params_dist($parc,$os,$id_machine,$se3ip,$ntpserv,$xppass);
                                            
                                            //echo "deb choisie";
                                        //include('includes/03_choix_parametres_wheezy.php');
                                        }
					elseif($os=="Xubuntu Trusty")
						choix_params_dist($parc,$os,$id_machine,$se3ip,$ntpserv,$xppass);
					else
						die("Une erreur est survenue, veuillez pr&eacute;ciser le syst&egrave;me d'exploitation voulu.");
				
                                        print "$content\n";
                                }
                                else {
//					
					if($os=="Debian Wheezy")
                                                valid_dist($id_machine);
						//include('includes/04_validation_parametres_wheezy.php');
//					elseif($os=="Xubuntu Trusty")
//						include('includes/04_validation_parametres_lucid.php');
					else
						die("Une erreur est survenue, veuillez pr&eacute;ciser le syst&egrave;me d'exploitation voulu.");
				}
			}
			echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";
		}
	}



// Footer
include ("pdp.inc.php");
?>
