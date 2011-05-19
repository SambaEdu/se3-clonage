<?php
/* $Id$
===========================================
   Projet SE3
   Dispositif SE3+TFTP+Sauvegarde/Restauration/Clonage
   Stephane Boireau
   
   Modification pour unattended : Olivier Lacroix
   
   Distribu� selon les termes de la licence GPL
=============================================
*/

// loading libs and init
//include "entete.inc.php";
include "ldap.inc.php";
include "ihm.inc.php";
//require_once "../dhcp/dhcpd.inc.php";
//include "printers.inc.php";

require("lib_action_tftp.php");

//aide
//$_SESSION["pageaide"]="Le_module_Unattended";

//$num_op=$_GET['num_op'];
$debut=$_GET['debut'];
$finwin=$_GET['finwin'];
$fin=$_GET['fin'];
$succes=$_GET['succes'];
$mac=$_GET['mac'];

echo "<html>
<body>
";

//$umode=$_GET['umode'];

// Controler les valeurs transmises
// mac: doit n'avoir que des 0-9a-f et tiret ou : et correspondre a une machine dans se3_dhcp
// succes: vaut y ou le contenu de l'erreur

creation_tftp_tables();

$corrige_mac=wordwrap($mac, 2, ":", 1);
// normalement inutile car les MAC remontees par unattended sont : 03E4FD648329
// $corrige_mac=preg_replace("/-/",":",$mac);

$sql="SELECT * FROM se3_dhcp WHERE mac='$corrige_mac';";
$res=mysql_query($sql);
if(mysql_num_rows($res)>0) {
    $lig_dhcp=mysql_fetch_object($res);

    $sql="INSERT INTO se3_tftp_rapports SET id='$lig_dhcp->id',
    name='$lig_dhcp->name',
    mac='".$corrige_mac."',
    tache='unattended',";
    if($succes=='y') {
        $sql.="statut='SUCCES',";
    }
    else {
        $sql.="statut='$succes',";
	// on remonte le type d'erreur dans la variable $succes
    }
    $sql.="descriptif='Tache windows unattended\n";
    $sql.="Debut: $debut\nFin de l\'installation de windows: $finwin\nFin de l\'installation des programmes : $fin';";
    $res=mysql_query($sql);

    echo "Remontee effectuee.";
}
else {
    echo "Echec de la remontee.\n";
    echo "L'adresse MAC $corrige_mac est inconnue dans la table 'se3_dhcp'.";
}

// Footer
include ("pdp.inc.php");
?>
