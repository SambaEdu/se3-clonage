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
$name=$_GET['name'];
$oldname=$_GET['oldname'];

// Controler les valeurs transmises
// num_op: doit etre un entier et l'action doit exister dans la table se3_tftp_action... euh, non, ce doit etre supprime une fois les fichiers /tftpboot/pxelinux.cfg/01-AD_MAC supprimes
// debut et fin doivent etre des entiers
// mac: doit n'avoir que des 0-9a-f et tiret ou : et correspondre a une machine dans se3_dhcp
// succes: vaut y ou n
// umode: vaut snd (emetteur) rcv (recepteur) ou pre (pre-clonage) ou post (unattended, jonction au domaine)

creation_tftp_tables();

//date='',
$duree=$fin-$debut;
$corrige_mac=strtolower(strtr($mac,"-",":"));
$corrige_mac2=strtolower(strtr($mac,":","-"));
$sql="SELECT * FROM se3_dhcp WHERE mac='$corrige_mac';";
$res=mysql_query($sql);
if(mysql_num_rows($res)>0) {
    $lig_dhcp=mysql_fetch_object($res);

    $sql="INSERT INTO se3_tftp_rapports SET id='$lig_dhcp->id', name='$lig_dhcp->name', mac='$corrige_mac',";
    if ($umode=='pre') {
        $sql.="tache='preparation',";
    }
    elseif ($umode=='post') {
        $sql.="tache='jonction',";
    }
    else {
        $sql.="tache='clonage',";
    }
    if($succes=='y') {
        $sql.="statut='SUCCES',";
    }
    else {
        $sql.="statut='ECHEC : $succes',";
    }
    $sql.="descriptif='Operation $num_op\n";
    if($umode=='rcv') {
        $sql.="Recepteur\n";
    }
    elseif($umode=='post')  {
        $sql.="jonction\n";
    }
    elseif($umode=='pre') {
        $sql.="preparation\n";
    }
    elseif($umode=='snd') {
        $sql.="Emetteur\n";
    }
    else {    
        $sql.="$umode\n";
    }
    $sql.="Debut: $debut\nFin: $fin\nDuree: $duree';";
    $res=mysql_query($sql);
    if ($umode=='pre') {
        // on attend 500 s que le fichier pxe soit pret pour rendre la main
        echo "On attend /tftpboot/pxelinux.cfg/01-$corrige_mac2 <br>\n";
        $incr=0;
        while (!file_exists("/tftpboot/pxelinux.cfg/01-$corrige_mac2")) {
            sleep(10);
            echo ".";
            if ($incr++==10) { 
                echo "Probleme : pas de fichier PXE";
                break;
            }
        }
    }
//    elseif ($umode=='post') {
    // on fait les changements de noms, de parcs...
//        renomme_machine($name,$oldname);
//    }         
    echo "<br>Remontee effectuee.<br>";
}
else {
    echo "Echec de la remontee.\n";
    echo "L'adresse MAC $corrige_mac est inconnue dans la table 'se3_dhcp'.";
}

// Footer
include ("pdp.inc.php");
?>
