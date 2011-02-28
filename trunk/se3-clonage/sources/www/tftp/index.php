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
	// Cr�ation de la table d�s que possible:
	creation_tftp_tables();

	echo "<h1>".gettext("Action TFTP")."</h1>\n";

	echo "<p>Voulez-vous: </p>\n";
	echo "<ul>\n";
	echo "<li><a href='consultation_tftp.php'>Consulter les actions programm�es</a></li>\n";
	echo "<li><a href='action_svg_tftp.php'>Programmer une sauvegarde</a></li>\n";
	echo "<li><a href='action_rest_tftp.php'>Programmer une restauration</a></li>\n";
	echo "<li><a href='action_clone_tftp.php'>Programmer un clonage</a></li>\n";
	echo "<li><a href='action_rapport_tftp.php'>Programmer une remont�e de rapport de configuration/partitionnement/sauvegardes</a></li>\n";
	echo "<li><a href='action_memtest_tftp.php'>Programmer un test de m�moire vive</a></li>\n";
	echo "</ul>\n";

		echo "<pre>A FAIRE:
- Exploiter les informations r�cup�r�es lors de sauvegardes (noms d'images,...) pour les propositions dans les formulaires (A FAIRE).
- Ajouter la g�n�ration de rapport SliTaz -&gt; rapport.sh
  (n�cessite que la r�cup soit au point)
- Explorer la piste alternative:
  Si un SysRescCD est install�, on peut g�n�rer un CFG avec:
label nofb
   kernel rescuecd
   append root=/dev/sda5 boot=/dev/sda vga=normal setkmap=fr work=sauvewin.sh
Il faudrait cependant adapter le sauvewin.sh pour pouvoir prendre les choix en param�tres.
Ou porter le script sauve_part.sh mis au point pour SlisTaz vers SysRescCD.
- Pouvoir taguer dans se3db.se3_dhcp les machines pouvant d�marrer en PXE.
- Pouvoir supprimer une t�che de la base... et supprimer les fichiers associ�s
- La r�cup�ration des rapports de sauvegarde/restauration court sur une dur�e de 4H (dur�e en dur dans recup_rapport.php) apr�s quoi elle est abandonn�e.
Si la sauvegarde/restauration n'est pas lanc�e effectivement assez vite (si le d�marrage PXE est retard� pour un raison X ou Y, la r�cup risque de ne pas avoir lieu.)
Il faudrait revoir cela.</pre>\n";

	echo "<p><i>NOTES</i>:</p>\n";
	echo "<ul>\n";

	echo "<li><p>Pour fonctionner int�gralement, le dispositif n�cessite que les machines d�marrent par d�faut en PXE.<br />Quand aucune t�che n'est programm�e, c'est le /tftpboot/pxelinux.cfg/default qui est propos� aux machines.<br />Apr�s 60s, en l'absence d'un autre choix, (<i>ou appui sur ENTREE</i>), le boot est lanc� sur le p�riph�rique suivant (<i>disque dur, ou CD selon le param�trage du BIOS</i>).<br />Quand une action est programm�e, un fichier /tftpboot/pxelinux.cfg/01-ADRESSE_MAC est propos� au client et permet de d�marrer par d�faut, apr�s 6s, sur le choix programm� via l'interface.</p></li>\n";

	echo "<li><p>Le boot par d�faut sur PXE est potentiellement dangereux: Si quelqu'un dans l'�tablissement est capable de monter son propre serveur TFTP+DHCP et s'il r�ussit � r�pondre aux demandes DHCP avant le serveur TFTP+DHCP du SE3, il peut faire d�marrer les clients sur l'image de boot qu'il aura pr�par�e... et �ventuellement lancer un formatage du disque dur des clients sur lesquels il aura provoqu� le d�marrage.</p></li>\n";

	echo "</ul>\n";
}
else
{
	print (gettext("Vous n'avez pas les droits n�cessaires pour ouvrir cette page..."));
}

// Footer
include ("pdp.inc.php");

?>
