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
include "printers.inc.php";

require("lib_action_tftp.php");

//aide
$_SESSION["pageaide"]="Le_module_Clonage_des_stations";

// On active les rapports d'erreurs:
//error_reporting(E_ALL);

// CSS pour mes tableaux:
echo "<link type='text/css' rel='stylesheet' href='tftp.css' />\n";

if (is_admin("system_is_admin",$login)=="Y")
{
	// Création de la table dès que possible:
	creation_tftp_tables();

	echo "<h1>".gettext("Action TFTP")."</h1>\n";

	echo "<p>Voulez-vous: </p>\n";
	echo "<ul>\n";
	echo "<li><a href='consultation_tftp.php'>Consulter les actions programmées</a></li>\n";
	echo "<li><a href='action_svg_tftp.php'>Programmer une sauvegarde</a></li>\n";
	echo "<li><a href='action_rest_tftp.php'>Programmer une restauration</a></li>\n";
	echo "<li><a href='action_clone_tftp.php'>Programmer un clonage</a></li>\n";
	echo "<li><a href='action_rapport_tftp.php'>Programmer une remontée de rapport de configuration/partitionnement/sauvegardes</a></li>\n";
	echo "<li><a href='action_memtest_tftp.php'>Programmer un test de mémoire vive</a></li>\n";
	echo "</ul>\n";

		echo "<pre>A FAIRE:
- Exploiter les informations récupérées lors de sauvegardes (noms d'images,...) pour les propositions dans les formulaires (A FAIRE).
- Ajouter la génération de rapport SliTaz -&gt; rapport.sh
  (nécessite que la récup soit au point)
- Explorer la piste alternative:
  Si un SysRescCD est installé, on peut générer un CFG avec:
label nofb
   kernel rescuecd
   append root=/dev/sda5 boot=/dev/sda vga=normal setkmap=fr work=sauvewin.sh
Il faudrait cependant adapter le sauvewin.sh pour pouvoir prendre les choix en paramètres.
Ou porter le script sauve_part.sh mis au point pour SlisTaz vers SysRescCD.
- Pouvoir taguer dans se3db.se3_dhcp les machines pouvant démarrer en PXE.
- Pouvoir supprimer une tâche de la base... et supprimer les fichiers associés
- La récupération des rapports de sauvegarde/restauration court sur une durée de 4H (durée en dur dans recup_rapport.php) après quoi elle est abandonnée.
Si la sauvegarde/restauration n'est pas lancée effectivement assez vite (si le démarrage PXE est retardé pour un raison X ou Y, la récup risque de ne pas avoir lieu.)
Il faudrait revoir cela.</pre>\n";

	echo "<p><i>NOTES</i>:</p>\n";
	echo "<ul>\n";

	echo "<li><p>Pour fonctionner intégralement, le dispositif nécessite que les machines démarrent par défaut en PXE.<br />Quand aucune tâche n'est programmée, c'est le /tftpboot/pxelinux.cfg/default qui est proposé aux machines.<br />Après 60s, en l'absence d'un autre choix, (<i>ou appui sur ENTREE</i>), le boot est lancé sur le périphérique suivant (<i>disque dur, ou CD selon le paramétrage du BIOS</i>).<br />Quand une action est programmée, un fichier /tftpboot/pxelinux.cfg/01-ADRESSE_MAC est proposé au client et permet de démarrer par défaut, après 6s, sur le choix programmé via l'interface.</p></li>\n";

	echo "<li><p>Le boot par défaut sur PXE est potentiellement dangereux: Si quelqu'un dans l'établissement est capable de monter son propre serveur TFTP+DHCP et s'il réussit à répondre aux demandes DHCP avant le serveur TFTP+DHCP du SE3, il peut faire démarrer les clients sur l'image de boot qu'il aura préparée... et éventuellement lancer un formatage du disque dur des clients sur lesquels il aura provoqué le démarrage.</p></li>\n";

	echo "</ul>\n";
}
else
{
	print (gettext("Vous n'avez pas les droits nécessaires pour ouvrir cette page..."));
}

// Footer
include ("pdp.inc.php");

?>
