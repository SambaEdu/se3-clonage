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
//include "printers.inc.php";

require("lib_action_tftp.php");

//aide
$_SESSION["pageaide"]="Le_module_Clonage_des_stations";

$num_op=$_GET['num_op'];
$debut=$_GET['debut'];
$fin=$_GET['fin'];
$succes=$_GET['succes'];
$mac=$_GET['mac'];
$umode=$_GET['umode'];

// Controler les valeurs transmises
// num_op: doit etre un entier et l'action doit exister dans la table se3_tftp_action... euh, non, ce doit etre supprime une fois les fichiers /tftpboot/pxelinux.cfg/01-AD_MAC supprimes
// debut et fin doivent etre des entiers
// mac: doit n'avoir que des 0-9a-f et tiret ou : et correspondre a une machine dans se3_dhcp
// succes: vaut y ou n

creation_tftp_tables();


//date='',
$duree=$fin-$debut;

$corrige_mac=preg_replace("/-/",":",$mac);
$sql="SELECT * FROM se3_dhcp WHERE mac='$corrige_mac';";
$res=mysql_query($sql);
if(mysql_num_rows($res)>0) {
    $lig_dhcp=mysql_fetch_object($res);

    $sql="INSERT INTO se3_tftp_rapports SET id='$lig_dhcp->id',
    name='$lig_dhcp->name',
    mac='".$corrige_mac."',
    tache='clonage',";
    if($succes='y') {
        $sql.="statut='SUCCES',";
    }
    elseif($succes='n') {
        $sql.="statut='ECHEC',";
    }
    $sql.="descriptif='Clonage n°$num_op\n";
    if($umode=='rcv') {
        $sql.="Recepteur\n";
    }
    elseif($umode=='snd') {
        $sql.="Emetteur\n";
    }
    $sql.="Debut: $debut\nFin: $fin\nDuree: $duree';";
    $res=mysql_query($sql);

    /*
    //+++++++++++++++++++++++++++++++++++++++++++++
    $fich=fopen("/var/lib/se3/import_comptes/remontee_clonage.txt","a+");
    fwrite($fich,"Numero du clonage: $num_op\n");
    fwrite($fich,"Debut du clonage: $debut\n");
    fwrite($fich,"Fin du clonage: $fin\n");
    fwrite($fich,"Succes: $succes\n");
    fwrite($fich,"MAC: $mac\n");
    fwrite($fich,"==============================\n");
    fclose($fich);
    //+++++++++++++++++++++++++++++++++++++++++++++
    */

    echo "Remontee effectuee.";
}
else {
    echo "Echec de la remontee.\n";
    echo "L'adresse MAC $corrige_mac est inconnue dans la table 'se3_dhcp'.";
}

// Footer
include ("pdp.inc.php");
?>
