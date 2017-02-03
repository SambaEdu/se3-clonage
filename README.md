# se3-clonage

Duplication du trunk du svn. Ce dépot a vocation à devenir celui du module se3-clonage version wheezy

# évolutions futures

Ce module devrait être remplacé par une solution plus modulaire basée sur iPXE : paquets se3-ipxe, se3-clients-*

# test iPXE

l'infrastructure est en place pour pouvoir booter en iPXE. Il suffit de taper "ipxe" au boot prompt sur le client suite au démarrage pxe.

Pour le moement il n'existe pas de paquet automatisant l'installation d'iPXE et des différents systèmes, l'installation doit se faire à la main

## mise en place iPXE

* télécharger 'ipxe.lkrn' et le copier dans '/tftpboot/'
* créer le dossier '/var/www/se3/ipxe' et créer un fichier minimal 'boot.php' sur ce modèle : 

  <?php
  include "ldap.inc.php";
  include "ihm.inc.php";
  require("lib_action_tftp.php");

  $mac=$_GET['mac'];

  echo "#!ipxe
  # fichier pour $mac
  set boot-url http://$ipse3
  kernel ${boot-url}/winpe/wimboot
  initrd ${boot-url}/winpe/boot/bcd BCD
  initrd ${boot-url}/winpe/boot/boot.sdi boot.sdi
  initrd ${boot-url}/winpe/sources/boot.wim boot.wim
  boot
  "; 

  ?>

* créer l'arborescence de boot wim dans '/var/www/winpe'

Il s'agit de la configuration minimale, la page 'boot.php' récupère l'adresse mac et peut donc servir des fichier ipxe personnalisés.
