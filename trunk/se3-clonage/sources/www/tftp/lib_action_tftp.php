<?php
/*
* $Id$
===========================================
   Projet SE3
   Dispositif SE3+TFTP+Sauvegarde/Restauration/Clonage
   Stephane Boireau
   Distribué selon les termes de la licence GPL
=============================================
*/

function creation_tftp_tables () {
	$retour=true;
	$sql="CREATE TABLE IF NOT EXISTS se3_tftp_action (
id INT(11),
mac VARCHAR(255),
name VARCHAR(255),
date INT(11),
type VARCHAR(255),
num_op INT(11),
infos VARCHAR(255)
);";
	$creation_table=mysql_query($sql);
	if(!$creation_table) {
		echo "<span style='color:red'>Erreur lors de la création de la table d'après la requête: </span><br /><pre style='color:green; border:1px solid red;'>$sql</pre>\n";
		$retour=false;
	}

	$sql="CREATE TABLE IF NOT EXISTS se3_tftp_rapports (
`id` INT( 11 ) NOT NULL ,
`name` VARCHAR( 255 ) NOT NULL ,
`mac` VARCHAR( 255 ) NOT NULL ,
`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`tache` VARCHAR( 255 ) NOT NULL ,
`statut` VARCHAR( 255 ) NOT NULL ,
`descriptif` TEXT NOT NULL,
identifiant int(11) NOT NULL auto_increment,
PRIMARY KEY  (identifiant)
);";
	$creation_table=mysql_query($sql);
	if(!$creation_table) {
		echo "<span style='color:red'>Erreur lors de la création de la table d'après la requête: </span><br /><pre style='color:green; border:1px solid red;'>$sql</pre>\n";
		$retour=false;
	}

	$sql="CREATE TABLE IF NOT EXISTS se3_tftp_sauvegardes (
`id` INT( 11 ) NOT NULL ,
`name` VARCHAR( 255 ) NOT NULL ,
`mac` VARCHAR( 255 ) NOT NULL ,
`partition` VARCHAR( 255 ) NOT NULL ,
`image` VARCHAR( 255 ) NOT NULL ,
`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`descriptif` TEXT NOT NULL,
`df` TEXT NOT NULL,
`partitionnement` TEXT NOT NULL,
identifiant int(11) NOT NULL auto_increment,
PRIMARY KEY  (identifiant)
);";
	$creation_table=mysql_query($sql);
	if(!$creation_table) {
		echo "<span style='color:red'>Erreur lors de la création de la table d'après la requête: </span><br /><pre style='color:green; border:1px solid red;'>$sql</pre>\n";
		$retour=false;
	}

	$sql="CREATE TABLE IF NOT EXISTS se3_tftp_infos (
`id` INT( 11 ) NOT NULL ,
`name` VARCHAR( 255 ) NOT NULL ,
`mac` VARCHAR( 255 ) NOT NULL ,
`nom` VARCHAR( 255 ) NOT NULL ,
`valeur` VARCHAR( 255 ) NOT NULL ,
identifiant int(11) NOT NULL auto_increment,
PRIMARY KEY  (identifiant)
);";
	$creation_table=mysql_query($sql);
	if(!$creation_table) {
		echo "<span style='color:red'>Erreur lors de la création de la table d'après la requête: </span><br /><pre style='color:green; border:1px solid red;'>$sql</pre>\n";
		$retour=false;
	}

	return $retour;
}

//====================================================
function decoupe_infos($texte) {
	$motif=array('disk','compr','port','udpcparam','src_part','dest_part','nom_image','auto_reboot','delais_reboot','enableDiskmodule','diskmodule','netmodule');
	$tab_txt=explode("|",$texte);
	$tab_retour=array();
	for($i=0;$i<count($tab_txt);$i++) {
		// Reconnaitre une des chaines disk=, port=,...
		for($j=0;$j<count($motif);$j++) {
			if(preg_match("/^$motif[$j]=/", $tab_txt[$i])) {
				if($motif[$j]=='udpcparam') {
					$udpcparam=explode(",",preg_replace("/--/",",",preg_replace("/^udpcparam=/", "", $tab_txt[$i])));
					for($k=0;$k<count($udpcparam);$k++) {
						if(preg_match("/^max-wait=/", $udpcparam[$k])) {
							$tab_retour['max-wait']=preg_replace("/^max-wait=/", "", $udpcparam[$k]);
						}
						elseif(preg_match("/^min-receivers=/", $udpcparam[$k])) {
							$tab_retour['min-receivers']=preg_replace("/^min-receivers=/", "", $udpcparam[$k]);
						}
						elseif(preg_match("/^start-timeout=/", $udpcparam[$k])) {
							$tab_retour['start-timeout']=preg_replace("/^start-timeout=/", "", $udpcparam[$k]);
						}
					}
				}
				else {
					$tab_retour[$motif[$j]]=preg_replace("/^$motif[$j]=/", "", $tab_txt[$i]);
				}
				break;
			}
		}
	}
	return $tab_retour;
}

//====================================================
function get_free_se3_action_tftp_num_op () {
	$sql="SELECT MAX(num_op) AS num_op_max FROM se3_tftp_action;";
	$res=mysql_query($sql);
	if($res) {
		if(mysql_num_rows($res)==0){
			return 1;
		}
		else {
			$lig_tmp=mysql_fetch_object($res);
			$tmp_num=$lig_tmp->num_op_max+1;
			return $tmp_num;
		}
	}
	else {
		return false;
	}
}
//====================================================
function search_machines2 ($filter,$branch) {
	/*
	Function: search_machines2
	Il s'agit d'une modification de search_machines() destinée à récupérer aussi l'adresse MAC

	Recherche de machines dans l'ou $branch

	Parameters:
		$filter - Un filtre de recherche permettant l'extraction de l'annuaire des machines
	$branch - L'ou correspondant à l'ou contenant les machines

	Return:
	Retourne un tableau avec les machines
	*/

	global $ldap_server, $ldap_port, $dn;
	global $error;

	// Initialisation
	$computers=array();

	// LDAP attributs
	if ("$branch"=="computers") {
			$ldap_computer_attr = array (
				"cn",
				"ipHostNumber",   // ip Host
				"macAddress",   // Adresse MAC
				"l",                        // Status de la machine
				"description"        // Description de la machine
			);
	}
	else {
			$ldap_computer_attr = array (
				"cn"
			);
	}

	$ds = @ldap_connect ( $ldap_server, $ldap_port );
	if ( $ds ) {
			$r = @ldap_bind ( $ds ); // Bind anonyme
			if ($r) {
				$result = @ldap_list ( $ds, $dn[$branch], $filter, $ldap_computer_attr );
				if ($result) {
					$info = @ldap_get_entries ( $ds, $result );
					if ( $info["count"]) {
						for ($loop=0; $loop < $info["count"]; $loop++) {
								$computers[$loop]["cn"] = $info[$loop]["cn"][0];
								if ("$branch"=="computers") {
									$computers[$loop]["ipHostNumber"] = $info[$loop]["iphostnumber"][0];
									$computers[$loop]["macAddress"] = $info[$loop]["macaddress"][0];
									if(isset($info[$loop]["l"][0])) {$computers[$loop]["l"] = $info[$loop]["l"][0];}
									if(isset($info[$loop]["description"][0])) {$computers[$loop]["description"] = utf8_decode($info[$loop]["description"][0]);}
								}
						}
					}
					@ldap_free_result ( $result );
				}
			}
			@ldap_close($ds);
	}

	return $computers;
}
//====================================================
function timestamp_to_mysql_date($timestamp) {
	return strftime("%Y-%m-%d %H:%M:%S",$timestamp);
}
//====================================================
function mysql_date_to_fr_date($date) {
	$tab1=explode(" ",$date);
	$tab11=explode("-",$tab1[0]);
	$tab21=explode(":",$tab1[1]);
	//return sprintf("%02d",$tab11[2])."/".sprintf("%02d",$tab11[1])."/".$tab11[0]." à ".sprintf("%02d",$tab21[0])."H".sprintf("%02d",$tab21[1])."M".sprintf("%02d",$tab21[2])."S";
	return sprintf("%02d",$tab11[2])."/".sprintf("%02d",$tab11[1])."/".$tab11[0]." à ".sprintf("%02d",$tab21[0]).":".sprintf("%02d",$tab21[1]).":".sprintf("%02d",$tab21[2]);
	//return sprintf("%02d",$tab11[2])."/".sprintf("%02d",$tab11[1])."/".$tab11[0]." à ".sprintf("%02d",$tab21[0])."H";
}
//====================================================
function affiche_pxe_cfg($texte) {
		echo "<div id='fich_cfg_off'>\n";
		echo "<p><a href='#' onClick=\"fich_cfg(true);return false;\">Visualiser</a> le fichier de configuration en /tftpboot/pxelinux.cfg/</p>\n";
		echo "</div>\n";

		echo "<div id='fich_cfg_on'>\n";
		echo "<p>Voici le fichier en /tftpboot/pxelinux.cfg/ (<i><a href='#' onClick=\"fich_cfg(false);return false;\">Masquer</a></i>):</p>\n";
		echo $texte;
		echo "</div>\n";


		echo "<script type='text/javascript'>
	function fich_cfg(mode) {
		if(mode==true) {
			document.getElementById('fich_cfg_off').style.display='none';
			document.getElementById('fich_cfg_on').style.display='';
		}
		else {
			document.getElementById('fich_cfg_off').style.display='';
			document.getElementById('fich_cfg_on').style.display='none';
		}
	}

	fich_cfg(false);
</script>\n";
}
//====================================================
function visu_tache($mac_machine,$mode=NULL) {
	// On lit le fichier dans /tftpboot/pxelinux.cfg/

	// On passe sinon un $mode=light
	if(!isset($mode)) {
		echo "<p>Voici les paramètres de l'action programmée: <br />";
	}

	$corrige_mac=strtolower(strtr($mac_machine,":","-"));

	$type_action="";

	$fich=fopen("/tftpboot/pxelinux.cfg/01-$corrige_mac","r");
	if($fich) {
		$infos="";
		$chaine_fichier="<pre style='color:green; border: 1px solid black;'>";
		while(!feof($fich)) {
			$ligne=fgets($fich,4096);
			if(strstr($ligne, "# Date de generation du fichier: ")) {
				$date_prog_action=preg_replace("/# Date de generation du fichier: /", "", $ligne);

			}
			//if(strstr($ligne, "default tazsvg")) {
			if(strstr($ligne, "label tazsvg")) {
				$type_action="Sauvegarde";
			}
			//elseif(strstr($ligne, "default tazrst")) {
			elseif(strstr($ligne, "label tazrst")) {
				$type_action="Restauration";
			}
			//elseif(strstr($ligne, "default u1auto")) {
			elseif(strstr($ligne, "label u1auto")) {
				$type_action="Emetteur UdpCast";
			}
			//elseif(strstr($ligne, "default u2auto")) {
			elseif(strstr($ligne, "label u2auto")) {
				$type_action="Récepteur UdpCast";
			}
			elseif(strstr($ligne, "label install")) {
				$type_action="Installation XP unattend";
			}

			if($type_action=="Sauvegarde") {
				//   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr vga=normal sound=no src_part=$src_part dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=/root/bin/sauve_part.sh

				if(strstr($ligne, "append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr vga=normal sound=no src_part=")) {
					//echo $ligne."<br />";
					unset($tab);
					$tab=explode(" ",$ligne);
					for($i=0;$i<count($tab);$i++){
						if(preg_match("/^src_part=/", $tab[$i])) {
							$src_part=preg_replace("/^src_part=/", "", $tab[$i]);
							//echo "\$src_part=$src_part<br />";
							$infos.="<tr><th>Partition sauvegardée</th><td>$src_part</td></tr>";
						}
						elseif(preg_match("/^dest_part=/", $tab[$i])) {
							$dest_part=preg_replace("/^dest_part=/", "", $tab[$i]);
							//echo "\$dest_part=$dest_part<br />";
							$infos.="<tr><th>Partition de stockage<br />de la sauvegarde</th><td>$dest_part</td></tr>";
						}
						elseif(preg_match("/^nom_image=/", $tab[$i])) {
							$nom_image=preg_replace("/^nom_image=/", "", $tab[$i]);
							//echo "\$nom_image=$nom_image<br />";
							$infos.="<tr><th>Nom de l'image</th><td>$nom_image</td></tr>";
						}
						elseif(preg_match("/^auto_reboot=/", $tab[$i])) {
							$auto_reboot=preg_replace("/^auto_reboot=/", "", $tab[$i]);
							//echo "\$auto_reboot=$auto_reboot<br />";
							$infos.="<tr><th>Auto-reboot</th><td>$auto_reboot</td></tr>";
						}
						elseif(preg_match("/^delais_reboot=/", $tab[$i])) {
							$delais_reboot=preg_replace("/^delais_reboot=/", "", $tab[$i]);
							//echo "\$delais_reboot=$delais_reboot<br />";
							$infos.="<tr><th>Délai avant reboot</th><td>$delais_reboot</td></tr>";
						}
					}
				}
			}
			elseif($type_action=="Restauration") {
				if(strstr($ligne, "append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr vga=normal sound=no src_part=")) {
					//echo $ligne."<br />";
					unset($tab);
					$tab=explode(" ",$ligne);
					for($i=0;$i<count($tab);$i++){
						if(preg_match("/^src_part=/", $tab[$i])) {
							$src_part=preg_replace("/^src_part=/", "", $tab[$i]);
							$infos.="<tr><th>Partition de stockage</th><td>$src_part</td></tr>";
						}
						elseif(preg_match("/^dest_part=/", $tab[$i])) {
							$dest_part=preg_replace("/^dest_part=/", "", $tab[$i]);
							$infos.="<tr><th>Partition restaurée</th><td>$dest_part</td></tr>";
						}
						elseif(preg_match("/^nom_image=/", $tab[$i])) {
							$nom_image=preg_replace("/^nom_image=/", "", $tab[$i]);
							//echo "\$nom_image=$nom_image<br />";
							$infos.="<tr><th>Nom de l'image</th><td>$nom_image</td></tr>";
						}
						elseif(preg_match("/^auto_reboot=/", $tab[$i])) {
							$auto_reboot=preg_replace("/^auto_reboot=/", "", $tab[$i]);
							$infos.="<tr><th>Auto-reboot</th><td>$auto_reboot</td></tr>";
						}
						elseif(preg_match("/^delais_reboot=/", $tab[$i])) {
							$delais_reboot=preg_replace("/^delais_reboot=/", "", $tab[$i]);
							$infos.="<tr><th>Délai avant reboot</th><td>$delais_reboot</td></tr>";
						}
					}
				}
			}
			elseif($type_action=="Emetteur UdpCast") {
				//append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=snd disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule udpcparam=$udpcparam
				if(strstr($ligne, "append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=")) {
					unset($tab);
					$tab=explode(" ",$ligne);
					for($i=0;$i<count($tab);$i++){
						if(preg_match("/^compr=/", $tab[$i])) {
							$compr=preg_replace("/^compr=/", "", $tab[$i]);
							$infos.="<tr><th>Compression</th><td>$compr</td></tr>";
						}
						elseif(preg_match("/^port=/", $tab[$i])) {
							$port=preg_replace("/^port=/", "", $tab[$i]);
							$infos.="<tr><th>Port</th><td>$port</td></tr>";
						}
						elseif(preg_match("/^disk=/", $tab[$i])) {
							$disk=preg_replace("/^disk=/", "", $tab[$i]);
							$infos.="<tr><th>Disque ou partition émis(e)</th><td>$disk</td></tr>";
						}
						elseif(preg_match("/^enableDiskmodule=/", $tab[$i])) {
							$enableDiskmodule=preg_replace("/^enableDiskmodule=/", "", $tab[$i]);
							$infos.="<tr><th>Chargement d'un module disque</th><td>$enableDiskmodule</td></tr>";
						}
						elseif(preg_match("/^diskmodule=/", $tab[$i])) {
							$diskmodule=preg_replace("/^diskmodule=/", "", $tab[$i]);
							$infos.="<tr><th>Module/pilote disque</th><td>$diskmodule</td></tr>";
						}
						elseif(preg_match("/^netmodule=/", $tab[$i])) {
							$netmodule=preg_replace("/^netmodule=/", "", $tab[$i]);
							$infos.="<tr><th>Pilote réseau</th><td>$netmodule</td></tr>";
						}
						elseif(preg_match("/^udpcparam=/", $tab[$i])) {
							//$udpcparam="--max-wait=".$max_wait."--min-receivers=".$min_receivers;
							$udpcparam=explode(",",preg_replace("/--/",",",preg_replace("/^udpcparam=/", "", $tab[$i])));
							for($j=0;$j<count($udpcparam);$j++) {
								if(preg_match("/^max-wait=/", $udpcparam[$j])) {
									$max_wait=preg_replace("/^max-wait=/", "", $udpcparam[$j]);
									$infos.="<tr><th>Délai maximum avant de lancer le clonage<br />même si un client fait défaut</th><td>$max_wait</td></tr>";
								}
								elseif(preg_match("/^min-receivers=/", $udpcparam[$j])) {
									$min_receivers=preg_replace("/^min-receivers=/", "", $udpcparam[$j]);
									$infos.="<tr><th>Nombre de clients à attendre</th><td>$min_receivers</td></tr>";
								}
							}
						}
						elseif(preg_match("/^auto_reboot=/", $tab[$i])) {
							$auto_reboot=preg_replace("/^auto_reboot=/", "", $tab[$i]);
							$infos.="<tr><th>Auto-reboot</th><td>$auto_reboot</td></tr>";
						}
					}
				}
			}
			elseif($type_action=="Récepteur UdpCast") {
				//append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule udpcparam=$udpcparam
				if(strstr($ligne, "append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=")) {
					unset($tab);
					$tab=explode(" ",$ligne);
					for($i=0;$i<count($tab);$i++){
						if(preg_match("/^compr=/", $tab[$i])) {
							$compr=preg_replace("/^compr=/", "", $tab[$i]);
							$infos.="<tr><th>Compression</th><td>$compr</td></tr>";
						}
						elseif(preg_match("/^port=/", $tab[$i])) {
							$port=preg_replace("/^port=/", "", $tab[$i]);
							$infos.="<tr><th>Port</th><td>$port</td></tr>";
						}
						elseif(preg_match("/^disk=/", $tab[$i])) {
							$disk=preg_replace("/^disk=/", "", $tab[$i]);
							$infos.="<tr><th>Disque ou partition écrasé(e)</th><td>$disk</td></tr>";
						}
						elseif(preg_match("/^enableDiskmodule=/", $tab[$i])) {
							$enableDiskmodule=preg_replace("/^enableDiskmodule=/", "", $tab[$i]);
							$infos.="<tr><th>Chargement d'un module disque</th><td>$enableDiskmodule</td></tr>";
						}
						elseif(preg_match("/^diskmodule=/", $tab[$i])) {
							$diskmodule=preg_replace("/^diskmodule=/", "", $tab[$i]);
							$infos.="<tr><th>Module/pilote disque</th><td>$diskmodule</td></tr>";
						}
						elseif(preg_match("/^netmodule=/", $tab[$i])) {
							$netmodule=preg_replace("/^netmodule=/", "", $tab[$i]);
							$infos.="<tr><th>Pilote réseau</th><td>$netmodule</td></tr>";
						}
						elseif(preg_match("/^udpcparam=/", $tab[$i])) {
							//$udpcparam="--start-timeout=".$start_timeout;
							$udpcparam=preg_replace("/^udpcparam=--start-timeout=/", "", $tab[$i]);
							$infos.="<tr><th>Délai avant abandon<br />si le clonage ne démarre pas</th><td>$udpcparam</td></tr>";
						}
						elseif(preg_match("/^auto_reboot=/", $tab[$i])) {
							$auto_reboot=preg_replace("/^auto_reboot=/", "", $tab[$i]);
							$infos.="<tr><th>Auto-reboot</th><td>$auto_reboot</td></tr>";
						}
					}
				}
			}
			elseif($type_action=="Installation XP unattend") {
				$infos="<tr><td>Installation d'un systeme Windows XP via unattended</td></tr>\n";
			}
			$chaine_fichier.=htmlentities($ligne);
		}
		$chaine_fichier.="</pre>\n";
		fclose($fich);

		// On passe sinon un $mode=light
		if(!isset($mode)) {
			echo "<h3>$type_action</h3>";
			echo "<table border=1>";
			echo $infos;
			echo "</table>";

			affiche_pxe_cfg($chaine_fichier);
		}
		else {
			$tmp_chaine="<h3>$type_action</h3>";
			$tmp_chaine.="<table border='1'>";
			$tmp_chaine.=$infos;
			$tmp_chaine.="</table>";
			//$tmp_chaine.="Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla Blablabla ";
			return $tmp_chaine;
		}
	}
	else {
		echo "<p>Il n'a pas été possible d'ouvrir le fichier /tftpboot/pxelinux.cfg/01-$corrige_mac</p>\n";
	}
}
// Fin de visu_tache()
//====================================================
function crob_getParam($name) {
	$sql="SELECT value FROM params WHERE name='".addslashes($name)."';";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)>0) {
		$lig=mysql_fetch_object($res);
		return $lig->value;
	}
	else {
		return "";
	}
}
//====================================================
function crob_setParam($name,$value,$descr) {
	$sql="DELETE FROM params WHERE name='".addslashes($name)."';";
	$del=mysql_query($sql);

	$sql="INSERT INTO params SET name='$name', descr='$descr', cat='7', value='".addslashes($value)."';";
	$insert=mysql_query($sql);
	if($insert) {return true;} else  {return false;}
}
//====================================================
function check_sysresccd_files() {
	$tab_fichiers_sysresccd=array("/var/www/sysresccd/sysrcd.dat","/var/www/sysresccd/sysrcd.md5","/var/www/sysresccd/autorun2","/var/www/sysresccd/scripts.tar.gz", "/tftpboot/rescuecd", "/tftpboot/initram.igz");
	$temoin_sysresccd="n";
	$cpt_sysresccd=0;
	foreach($tab_fichiers_sysresccd as $key => $value) {
		if(file_exists("$value")) {
			//echo "<p>Le fichier /var/www/sysresccd/$value est present.</p>";
			$cpt_sysresccd++;
		}
	}
	if($cpt_sysresccd==count($tab_fichiers_sysresccd)) {
		//echo "<p>Tout est en place</p>";
		$temoin_sysresccd="y";
	}
	return $temoin_sysresccd;
}
//====================================================
?>
