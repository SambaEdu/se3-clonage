#!/usr/bin/php
<?php
/*
* $Id$
*/

	if($argc < 5 || in_array($argv[1], array('--help', '-help', '-h', '-?'))){
		echo "Script de récupération de rapport de sauvegarde/restauration/...\n";
		echo "USAGE: Passer en paramètres:\n";
		echo "       - l'identifiant de la machine (champ 'id' de 'se3_dhcp')\n";
		echo "       - l'adresse IP de la machine (champ 'ip' de 'se3_dhcp')\n";
		echo "       - la nature de l'action lancée (sauvegarde, restauration,...)\n";
		echo "       - le timestamp de la date limite de la tentative de récupération.\n";
		exit();
	}

	$id_machine=$argv[1];
	$ip=$argv[2];
	$nature=$argv[3];
	$limite=$argv[4];

	/*
	require ("config.inc.php");
	require_once ("functions.inc.php");
	require_once ("lang.inc.php");

	include "ldap.inc.php";
	include "ihm.inc.php";
	require_once "dhcpd.inc.php";

	require("lib_action_tftp.php");
	*/
	$chemin_include="/var/www/se3/includes";

	require ("$chemin_include/config.inc.php");
	require_once ("$chemin_include/functions.inc.php");
	require_once ("$chemin_include/lang.inc.php");

	include "$chemin_include/ldap.inc.php";
	include "$chemin_include/ihm.inc.php";

	//require_once "/var/www/se3/dhcp/dhcpd.inc.php";
	require("/var/www/se3/tftp/lib_action_tftp.php");

	// Dispositif de debug:
	function fich_log_debug($texte) {
		global $id_machine;

		// Passer la variable à "y" pour activer le renseignement du fichier de log
		$temoin_fich_log_debug="n";

		if($temoin_fich_log_debug=="y") {
			$fich=fopen("/tmp/tftp_$id_machine.log","a+");
			fwrite($fich,$texte);
			fclose($fich);
		}
	}

	// On active les rapports d'erreurs:
	error_reporting(E_ALL);

	// Dossier contenant le lanceur de récupération de rapport.
	// www-se3 doit en être proprio ou au moins pouvoir y écrire.
	//$dossier="/var/se3/tmp/tftp/$id_machine";
	$dossier="/etc/se3/www-tools/tftp/$id_machine";
	$lanceur_recup="$dossier/lanceur_recup_rapport_action_tftp.sh";
	$tftp_pxelinux_cfg="/tftpboot/pxelinux.cfg";

	if(!file_exists($dossier)) { mkdir($dossier,0700);}

	// Ici et maintenant...
	$instant=time();
	// La date limite de récupération est-elle atteinte?
	if($instant>=$limite) {
		if(file_exists($lanceur_recup)) {
			unlink($lanceur_recup);
		}
		// ERREUR A TESTER

		// INSERER UN RAPPORT COMME QUOI L'ACTION EST ABANDONNEE...
		$creation_table=creation_tftp_tables();
		if(!$creation_table){
			echo "Erreur lors de la création de la table 'se3_tftp_rapports'\n";
		}
		else{
			//$sql="SELECT * FROM se3_tftp_action WHERE id='$id_machine';";
			$sql="SELECT * FROM se3_dhcp WHERE id='$id_machine';";
			$info_machine=mysql_query($sql);

			if(!$info_machine){
				echo "La machine n°$id_machine n'existe pas dans la table 'se3_dhcp'.\n";

				# Insérer dans une table rapport
				$sql="INSERT INTO se3_tftp_rapports SET id='$id_machine',
														name='',
														mac='',
														date='".timestamp_to_mysql_date($instant)."',
														tache='$nature',
														statut='ECHEC',
														descriptif='Abandon: date limite de récupération atteinte.';";
				//echo $sql;
				$insert=mysql_query($sql);
				if(!$insert){
					echo "ERREUR sur $sql\n";
				}
			}
			else{
				$lig_machine=mysql_fetch_object($info_machine);
				$mac_machine=$lig_machine->mac;
				$nom_machine=$lig_machine->name;

				$corrige_mac=strtolower(strtr($mac_machine,":","-"));

				// Insérer dans une table rapport
				$sql="INSERT INTO se3_tftp_rapports SET id='$id_machine',
														name='$nom_machine',
														mac='$mac_machine',
														date='".timestamp_to_mysql_date($instant)."',
														tache='$nature',
														statut='ECHEC',
														descriptif='Abandon: date limite de récupération atteinte.';";
				//echo $sql;
				$insert=mysql_query($sql);
				if(!$insert){
					echo "ERREUR sur $sql\n";
				}

				if($corrige_mac!="") {
					if(file_exists("$tftp_pxelinux_cfg/01-$corrige_mac")) {
						unlink("$tftp_pxelinux_cfg/01-$corrige_mac");
					}
				}
			}

			// Supprimer l'entrée dans se3_tftp_action
			$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine';";
			$nettoyage=mysql_query($sql);
			if(!$nettoyage){
				echo "ERREUR lors de la suppression de l'action sur\n   $sql\n";
			}
		}
		exit();
	}

	// Le fichier à récupérer diffère d'une action à l'autre:
	switch($nature) {
		case "restauration":
			$url="http://$ip/~hacker/resultat_restauration.txt";
			//wget --tries=1 http://$ip/~hacker/resultat_restauration.txt

			if(@exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'")) {
				if($fl=@file($url)){
					// Terminé... on renseigne une table

					$statut=trim($fl[0]);
					//if("$statut"=="SUCCES") {

					$descriptif="";
					for($i=1;$i<count($fl);$i++){
						$descriptif.=preg_replace("/['\"]/","[__]",trim($fl[$i]))."\n";
					}
					$descriptif=trim($descriptif);

					$creation_table=creation_tftp_tables();
					if(!$creation_table){
						echo "Erreur lors de la création de la table 'se3_tftp_rapports'\n";
					}
					else{
						//$sql="SELECT * FROM se3_tftp_action WHERE id='$id_machine';";
						$sql="SELECT * FROM se3_dhcp WHERE id='$id_machine';";
						$info_machine=mysql_query($sql);

						if(!$info_machine){
							echo "La machine n°$id_machine n'existe pas dans la table 'se3_dhcp'.\n";
						}
						else{
							$lig_machine=mysql_fetch_object($info_machine);
							$mac_machine=$lig_machine->mac;
							$nom_machine=$lig_machine->name;

							$corrige_mac=strtolower(strtr($mac_machine,":","-"));

							# Insérer dans une table rapport
							$sql="INSERT INTO se3_tftp_rapports SET id='$id_machine',
																	name='$nom_machine',
																	mac='$mac_machine',
																	date='".timestamp_to_mysql_date($instant)."',
																	tache='$nature',
																	statut='$statut',
																	descriptif='".addslashes($descriptif)."';";
							//echo $sql;
							$insert=mysql_query($sql);
							if(!$insert){
								echo "ERREUR sur $sql\n";
							}
							# Insérer dans une table sauvegarde si il s'agit d'une sauvegarde
						}
					}
					// Ménage:
					//rm -f resultat_restauration.txt
					// Celui-là est récupéré où? en /tmp?
					if(file_exists($lanceur_recup)) {
						//unlink($lanceur_recup);
						if(!unlink($lanceur_recup)){
							echo "La suppression de $lanceur_recup a échoué.\n";
						}
					}

					if($corrige_mac!="") {
						if(file_exists("$tftp_pxelinux_cfg/01-$corrige_mac")) {
							unlink("$tftp_pxelinux_cfg/01-$corrige_mac");
						}
					}

					// Supprimer l'entrée dans se3_tftp_action
					$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine';";
					$nettoyage=mysql_query($sql);
					if(!$nettoyage){
						echo "ERREUR lors de la suppression de l'action sur\n   $sql\n";
					}
					exit();
				}
				else {
					# On remet à plus tard...
					#at +f 1 minute $0 $*
					//exec("at +f 1 minute $lanceur_recup",$retour);
					//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
					@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
					# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
					# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

					// Tester le $retour
					if(count($retour)>0){
						echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
						for($i=0;$i<count($retour);$i++){
							echo "$retour[$i]\n";
						}
					}
				}
			}
			else {
				# On remet à plus tard...
				#at +f 1 minute $0 $*
				//exec("at +f 1 minute $lanceur_recup",$retour);
				//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
				@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
				# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
				# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

				// Tester le $retour
				if(count($retour)>0){
					echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
					for($i=0;$i<count($retour);$i++){
						echo "$retour[$i]\n";
					}
				}
			}
			break;
		case "sauvegarde":
			$url="http://$ip/~hacker/resultat_sauvegarde.txt";
			//wget --tries=1 http://$ip/~hacker/resultat_restauration.txt

			if(@exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'")) {
				if($fl=@file($url)){
					// Terminé... on renseigne une table

					$statut=trim($fl[0]);
					//if("$statut"=="SUCCES") {

					$descriptif="";
					$partition="";
					$image="";
					for($i=1;$i<count($fl);$i++){
						if(preg_match("/^partition=/",$fl[$i])) {$partition=trim(preg_replace("/^partition=/","",$fl[$i]));}
						if(preg_match("/^image=/",$fl[$i])) {$image=trim(preg_replace("/^image=/","",$fl[$i]));}
						$descriptif.=preg_replace("/['\"]/","[__]",trim($fl[$i]))."\n";
					}
					$descriptif=trim($descriptif);

					$creation_table=creation_tftp_tables();
					if(!$creation_table){
						echo "Erreur lors de la création de la table 'se3_tftp_rapports'\n";
					}
					else{
						//$sql="SELECT * FROM se3_tftp_action WHERE id='$id_machine';";
						$sql="SELECT * FROM se3_dhcp WHERE id='$id_machine';";
						$info_machine=mysql_query($sql);

						if(!$info_machine){
							echo "La machine n°$id_machine n'existe pas dans la table 'se3_dhcp'.\n";
						}
						else{
							$lig_machine=mysql_fetch_object($info_machine);
							$mac_machine=$lig_machine->mac;
							$nom_machine=$lig_machine->name;

							$corrige_mac=strtolower(strtr($mac_machine,":","-"));

							# Insérer dans une table rapport
							$sql="INSERT INTO se3_tftp_rapports SET id='$id_machine',
																	name='$nom_machine',
																	mac='$mac_machine',
																	date='".timestamp_to_mysql_date($instant)."',
																	tache='$nature',
																	statut='$statut',
																	descriptif='".addslashes($descriptif)."';";
							//echo $sql;
							$insert=mysql_query($sql);
							if(!$insert){
								echo "ERREUR sur $sql\n";
							}

							if($statut=="SUCCES"){
								$url="http://$ip/~hacker/partitionnement.out";
								//wget --tries=1 http://$ip/~hacker/resultat_restauration.txt

								$partitionnement="";
								if(@exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'")) {
									if($fl=@file($url)){
										for($i=1;$i<count($fl);$i++){
											$partitionnement.=$fl[$i];
										}
										if(addslashes(preg_replace("/['\"]/","[__]",$partitionnement))!=$partitionnement) {
											echo "$partitionnement\n\n".addslashes(preg_replace("/['\"]/","[__]",$partitionnement))."\n\n";
											$partitionnement="";
										}
									}
								}

								$url="http://$ip/~hacker/df.txt";
								$df="";
								if(@exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'")) {
									if($fl=@file($url)){
										$df=$fl[0];
									}
								}

								# Insérer dans une table sauvegarde si il s'agit d'une sauvegarde
								$creation_table=creation_tftp_tables();
								if(!$creation_table){
									echo "Erreur lors de la création de la table 'se3_tftp_sauvegardes'\n";
								}
								else{
									$sql="INSERT INTO se3_tftp_sauvegardes SET id='$id_machine',
																	name='$nom_machine',
																	mac='$mac_machine',
																	partition='$partition',
																	image='$image',
																	date='".timestamp_to_mysql_date($instant)."',
																	descriptif='".addslashes($descriptif)."',
																	df='$df',
																	partitionnement='".addslashes($partitionnement)."';";
									$insert=mysql_query($sql);
									if(!$insert){
										echo "ERREUR sur $sql\n";
									}
								}


							}
						}
					}
					// Ménage:
					//rm -f resultat_restauration.txt
					// Celui-là est récupéré où? en /tmp?
					if(file_exists($lanceur_recup)) {
						//unlink($lanceur_recup);
						if(!unlink($lanceur_recup)){
							echo "La suppression de $lanceur_recup a échoué.\n";
						}
					}

					if($corrige_mac!="") {
						if(file_exists("$tftp_pxelinux_cfg/01-$corrige_mac")) {
							unlink("$tftp_pxelinux_cfg/01-$corrige_mac");
						}
					}

					// Supprimer l'entrée dans se3_tftp_action
					$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine';";
					$nettoyage=mysql_query($sql);
					if(!$nettoyage){
						echo "ERREUR lors de la suppression de l'action sur\n   $sql\n";
					}
					exit();
				}
				else {
					# On remet à plus tard...
					#at +f 1 minute $0 $*
					//exec("at +f 1 minute $lanceur_recup",$retour);
					//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
					@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
					# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
					# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

					// Tester le $retour
					if(count($retour)>0){
						echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
						for($i=0;$i<count($retour);$i++){
							echo "$retour[$i]\n";
						}
					}
				}
			}
			else {
				# On remet à plus tard...
				#at +f 1 minute $0 $*
				//exec("at +f 1 minute $lanceur_recup",$retour);
				//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
				@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
				# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
				# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

				// Tester le $retour
				if(count($retour)>0){
					echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
					for($i=0;$i<count($retour);$i++){
						echo "$retour[$i]\n";
					}
				}
			}
			break;
		case "rapport":
			//echo "recup_rapport.sh: rapport: A FAIRE";
			/*
			/home/hacker/Public/modules.txt
			/home/hacker/Public/lsmod.txt

			tar -czf disques.tar.gz disk_*.out disk_*.fdisk
			disk_${A}.out
			disk_${A}.fdisk
			# à inscrire dans la table se3_tftp_sauvegardes

			tar -czf sauvegardes.tar.gz sauvegardes_*.txt
			sauvegardes_${B}.txt
			sauvegardes_${B}_details.txt
			# à inscrire dans la table se3_tftp_sauvegardes

			tar -czf df.tar.gz df_*.txt
			*/

			$url="http://$ip/~hacker/lsmod.txt";
			//wget --tries=1 http://$ip/~hacker/lsmod.txt

			if(@exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'")) {
				if($fl=@file($url)){
					// Terminé... on renseigne une table

					fich_log_debug("Le lsmod.txt a été trouvé.\n");

					// Le rapport a été généré.
					// On va tester l'existence des fichiers...
					$tab_fich=array("hda","hdb","hdc","hdd","sda","sdb","sdc","sdd");

					// Variable destinée à contenir les disk_*.out correspondant aux tables de partitions des disques de la machine
					$partitionnement="";
					// CE PARTITIONNEMENT DEVRAIT ETRE DANS LA BOUCLE for($i=0;$i<count($tab_fich);$i++) {
					// SI PLUSIEURS DISQUES DURS SONT PARCOURUS...
					// ... mais les insertions dans les tables devraient aussi en tenir compte...
					// POUR QUE L'EXPLOITATION DANS UNE PAGE WEB SOIT POSSIBLE, IL FAUDRAIT AJOUTER DES CHAMPS SEPARES POUR LE df, LE PARTITIONNEMENT DANS se3_tftp_rapports
					$infos="";
					$sauvegardes=array();
					$cpt=0;
					for($i=0;$i<count($tab_fich);$i++) {
						unset($tab_part);
						$tab_part=array();

						$url="http://$ip/~hacker/disk_".$tab_fich[$i].".out";
						unset($fl2);
						if($fl2=@file($url)){
							fich_log_debug("Le fichier disk_".$tab_fich[$i].".out a été trouvé.\n");

							if($i>0) {
								$partitionnement.="___+*+___";
								//$partitionnement.="___+-+___";
							}
							for($j=0;$j<count($fl2);$j++){
								$partitionnement.=$fl2[$j];
								if(preg_match("|^/dev/".$tab_fich[$i]."|",$fl2[$j])) {
									fich_log_debug("Lecture de la ligne $j:\n   $fl2[$j]\ndu sfdisk.out de /dev/$tab_fich[$i] dans le $url\n");
									unset($tab_tmp);
									$tab_tmp=explode(" ",$fl2[$j]);
									for($m=0;$m<count($tab_tmp);$m++) {
										fich_log_debug("\$tab_tmp[$m]=$tab_tmp[$m]\n");
									}
									//$tab_part[]=preg_match("/^/dev//",$tab_tmp[0]);
									//$tab_part[]=preg_replace("/^/dev//","",$tab_tmp[0]);
									//$tab_part[]=preg_replace("|^/dev/|","",$tab_tmp[0]);
									$tab_part[]=mb_ereg_replace("^/dev/","",$tab_tmp[0]);
									//fich_log_debug("Ajout de la partition: '".preg_replace("/^/dev//","",$tab_tmp[0])."\n");
									//fich_log_debug("Ajout de la partition: '".preg_replace("|^/dev/|","",$tab_tmp[0])."\n");
									fich_log_debug("Ajout de la partition: '".mb_ereg_replace("^/dev/","",$tab_tmp[0])."\n");

									//$url="http://$ip/~hacker/df_".preg_replace("/^/dev//","",$tab_tmp[0]).".txt";
									//$url="http://$ip/~hacker/df_".preg_replace("|^/dev/|","",$tab_tmp[0]).".txt";
									$url="http://$ip/~hacker/df_".mb_ereg_replace("^/dev/","",$tab_tmp[0]).".txt";
									fich_log_debug("Recherche de $url\n");
									unset($fl4);
									if($fl4=@file($url)) {
										//fich_log_debug("df_".preg_replace("/^/dev//","",$tab_tmp[0]).".txt trouvé.\n");
										//fich_log_debug("df_".preg_replace("|^/dev/|","",$tab_tmp[0]).".txt trouvé.\n");
										fich_log_debug("df_".mb_ereg_replace("^/dev/","",$tab_tmp[0]).".txt trouvé.\n");
										for($m=0;$m<count($fl4);$m++){
											$infos.=$fl4[$m];
										}
									}
								}
							}
							if(addslashes(preg_replace("/['\"]/","[__]",$partitionnement))!=$partitionnement) {
								echo "$partitionnement\n\n".addslashes(preg_replace("/['\"]/","[__]",$partitionnement))."\n\n";
								$partitionnement="";
							}
						}

						fich_log_debug("\n\$partitionnement=$partitionnement\n");


						// Traitement des fichiers de sauvegarde trouvés pour renseigner la table se3_tftp_sauvegardes
						for($k=0;$k<count($tab_part);$k++) {
							fich_log_debug("Test de \$tab_part[$k]=".$tab_part[$k]."\n");

							$url="http://$ip/~hacker/sauvegardes_".$tab_part[$k].".txt";
							unset($fl2);
							if($fl2=@file($url)){
								fich_log_debug("sauvegardes_".$tab_part[$k].".txt trouvé.\n");

								$url="http://$ip/~hacker/sauvegardes_".$tab_part[$k]."_details.txt";
								unset($fl3);
								$fl3=@file($url);

								$url="http://$ip/~hacker/df_".$tab_part[$k].".txt";
								unset($fl4);
								$fl4=@file($url);

								for($j=0;$j<count($fl2);$j++){
									$sauvegardes[$cpt]=array();
									// $tab_part[$k] est la partition de stockage, pas la partition sauvegardée
									//$sauvegardes[$cpt]['partition']=$tab_part[$k];
									$sauvegardes[$cpt]['partition']="";
									$sauvegardes[$cpt]['chemin']=trim($fl2[$j]);
									$sauvegardes[$cpt]['details']="";
									$sauvegardes[$cpt]['df']="";

									fich_log_debug("\$sauvegardes[$cpt]['chemin']=".$sauvegardes[$cpt]['chemin']."\n");

									if($fl3) {
										fich_log_debug("sauvegardes_".$tab_part[$k]."_details.txt trouvé.\n");
										$temoin1="n";
										for($m=0;$m<count($fl3);$m++){
											//if(preg_match("/Infos sur /i".$sauvegardes[$cpt]['chemin'],$fl3[$m])) {$temoin1="y";}
											if(preg_match("|Infos sur ".$sauvegardes[$cpt]['chemin']."|i",$fl3[$m])) {$temoin1="y";}
											//if(preg_match("/___+\*+___/",trim($fl3[$m]))) {$temoin1="n";echo "TRUC";}
											//if(preg_match("/^___+/",trim($fl3[$m]))) {$temoin1="n";}
											if(preg_match("/^___\+\*\+___$/",trim($fl3[$m]))) {$temoin1="n";}
											if($temoin1=="y") {
												$sauvegardes[$cpt]['details'].=$fl3[$m];
											}

											if(preg_match("/^partition=/",$fl3[$m])) {
												$sauvegardes[$cpt]['partition']=preg_replace("/^partition=/","",$fl3[$m]);
											}
										}
									}

									if($fl4) {
										fich_log_debug("df_".$tab_part[$k].".txt trouvé.\n");
										for($m=0;$m<count($fl4);$m++){
											$sauvegardes[$cpt]['df'].=$fl4[$m];
										}
									}

									$cpt++;
								}
							}
						}
					}

					//========================================
					$network_driver=array();
					$url="http://$ip/~hacker/network_driver.txt";
					unset($fl5);
					if($fl5=@file($url)){
						for($m=0;$m<count($fl5);$m++){
							$network_driver[]=$fl5[$m];
							fich_log_debug("network_driver[$m]=".$fl5[$m]."\n");
						}
					}

					$storage_driver=array();
					$url="http://$ip/~hacker/storage_driver.txt";
					unset($fl6);
					if($fl6=@file($url)){
						for($m=0;$m<count($fl6);$m++){
							$storage_driver[]=$fl6[$m];
							fich_log_debug("storage_driver[$m]=".$fl6[$m]."\n");
						}
					}
					//========================================

					// Insertion dans les tables

					$creation_table=creation_tftp_tables();
					if(!$creation_table){
						echo "Erreur lors de la création de la table 'se3_tftp_rapports'\n";
					}
					else{
						//$sql="SELECT * FROM se3_tftp_action WHERE id='$id_machine';";
						$sql="SELECT * FROM se3_dhcp WHERE id='$id_machine';";
						$info_machine=mysql_query($sql);

						if(!$info_machine){
							echo "La machine n°$id_machine n'existe pas dans la table 'se3_dhcp'.\n";
						}
						else{
							$lig_machine=mysql_fetch_object($info_machine);
							$mac_machine=$lig_machine->mac;
							$nom_machine=$lig_machine->name;

							$corrige_mac=strtolower(strtr($mac_machine,":","-"));

							$statut="SUCCES";
							$descriptif=$partitionnement;
							//if($descriptif!="") {$descriptif.="\n";}
							if($descriptif!="") {
								$descriptif.="\n";
							}
							$descriptif.="___FIN_PARTITIONNEMENT___";
							$descriptif.="\n";

							$descriptif.=$infos;
							if($infos!="") {
								$descriptif.="\n";
							}
							$descriptif.="___FIN_INFOS_DF___";
							$descriptif.="\n";

							if(count($sauvegardes)>0) {
								if($descriptif!="") {$descriptif.="\n";}
								for($i=0;$i<count($sauvegardes);$i++) {
									$descriptif.=$sauvegardes[$i]['chemin']."\n";
									if($sauvegardes[$i]['details']!="") {
										$descriptif.=$sauvegardes[$i]['details']."\n";
									}
									/*
									// Là, on insère le volume dispo autant de fois qu'il y a de sauvegarde, alors que le df est inséré une fois après les infos sur le partitionnement.
									if($sauvegardes[$i]['df']!="") {
										$descriptif.=$sauvegardes[$i]['df']."\n";
									}
									*/
								}
							}

							# Insérer dans une table rapport
							$sql="INSERT INTO se3_tftp_rapports SET id='$id_machine',
																	name='$nom_machine',
																	mac='$mac_machine',
																	date='".timestamp_to_mysql_date($instant)."',
																	tache='$nature',
																	statut='$statut',
																	descriptif='".addslashes($descriptif)."';";
							//echo $sql;
							$insert=mysql_query($sql);
							if(!$insert){
								echo "ERREUR sur $sql\n";
							}

							//========================================
							$sql="DELETE FROM se3_tftp_infos WHERE id='$id_machine';";
							//echo $sql;
							$delete=mysql_query($sql);
							if(!$delete){
								echo "ERREUR sur $sql\n";
							}
							for($i=0;$i<count($network_driver);$i++) {
								$sql="INSERT INTO se3_tftp_infos SET id='$id_machine',
																name='$nom_machine',
																mac='$mac_machine',
																nom='network_driver',
																valeur='".trim(addslashes($network_driver[$i]))."';";
								$insert=mysql_query($sql);
								if(!$insert){
									echo "ERREUR sur $sql\n";
								}
							}
							for($i=0;$i<count($storage_driver);$i++) {
								$sql="INSERT INTO se3_tftp_infos SET id='$id_machine',
																name='$nom_machine',
																mac='$mac_machine',
																nom='storage_driver',
																valeur='".trim(addslashes($storage_driver[$i]))."';";
								$insert=mysql_query($sql);
								if(!$insert){
									echo "ERREUR sur $sql\n";
								}
							}
							//========================================


							if(count($sauvegardes)>0) {
								$creation_table=creation_tftp_tables();
								if(!$creation_table){
									echo "Erreur lors de la création de la table 'se3_tftp_sauvegardes'\n";
								}
								else{
									for($i=0;$i<count($sauvegardes);$i++) {
										$sql="INSERT INTO se3_tftp_sauvegardes SET id='$id_machine',
																		name='$nom_machine',
																		mac='$mac_machine',
																		partition='".$sauvegardes[$i]['partition']."',
																		image='".$sauvegardes[$i]['chemin']."',
																		date='".timestamp_to_mysql_date($instant)."',
																		descriptif='".addslashes($sauvegardes[$i]['details'])."',
																		df='".$sauvegardes[$i]['df']."',
																		partitionnement='".addslashes($partitionnement)."';";

										$insert=mysql_query($sql);
										if(!$insert){
											echo "ERREUR sur $sql\n";
										}
									}
								}
							}
						}
					}
					// Ménage:
					//rm -f resultat_restauration.txt
					// Celui-là est récupéré où? en /tmp?
					if(file_exists($lanceur_recup)) {
						//unlink($lanceur_recup);
						if(!unlink($lanceur_recup)){
							echo "La suppression de $lanceur_recup a échoué.\n";
						}
					}

					if($corrige_mac!="") {
						if(file_exists("$tftp_pxelinux_cfg/01-$corrige_mac")) {
							unlink("$tftp_pxelinux_cfg/01-$corrige_mac");
						}
					}

					// Supprimer l'entrée dans se3_tftp_action
					$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine';";
					$nettoyage=mysql_query($sql);
					if(!$nettoyage){
						echo "ERREUR lors de la suppression de l'action sur\n   $sql\n";
					}
					exit();
				}
				else {
					# On remet à plus tard...
					#at +f 1 minute $0 $*
					//exec("at +f 1 minute $lanceur_recup",$retour);
					//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
					@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
					# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
					# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

					// Tester le $retour
					if(count($retour)>0){
						echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
						for($i=0;$i<count($retour);$i++){
							echo "$retour[$i]\n";
						}
					}
				}
			}
			else {
				# On remet à plus tard...
				#at +f 1 minute $0 $*
				//exec("at +f 1 minute $lanceur_recup",$retour);
				//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
				@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
				# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
				# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

				// Tester le $retour
				if(count($retour)>0){
					echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
					for($i=0;$i<count($retour);$i++){
						echo "$retour[$i]\n";
					}
				}
			}

			break;
	//======================================================================================
		case "restauration_sysresccd":
			$url="http://$ip/resultat_restauration.txt";
			//wget --tries=1 http://$ip/~hacker/resultat_restauration.txt

			if(@exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'")) {
				if($fl=@file($url)){
					// Terminé... on renseigne une table

					$statut=trim($fl[0]);
					//if("$statut"=="SUCCES") {

					$descriptif="";
					for($i=1;$i<count($fl);$i++){
						$descriptif.=preg_replace("/['\"]/","[__]",trim($fl[$i]))."\n";
					}
					$descriptif=trim($descriptif);

					$creation_table=creation_tftp_tables();
					if(!$creation_table){
						echo "Erreur lors de la création de la table 'se3_tftp_rapports'\n";
					}
					else{
						//$sql="SELECT * FROM se3_tftp_action WHERE id='$id_machine';";
						$sql="SELECT * FROM se3_dhcp WHERE id='$id_machine';";
						$info_machine=mysql_query($sql);

						if(!$info_machine){
							echo "La machine n°$id_machine n'existe pas dans la table 'se3_dhcp'.\n";
						}
						else{
							$lig_machine=mysql_fetch_object($info_machine);
							$mac_machine=$lig_machine->mac;
							$nom_machine=$lig_machine->name;

							$corrige_mac=strtolower(strtr($mac_machine,":","-"));

							# Insérer dans une table rapport
							$sql="INSERT INTO se3_tftp_rapports SET id='$id_machine',
																	name='$nom_machine',
																	mac='$mac_machine',
																	date='".timestamp_to_mysql_date($instant)."',
																	tache='$nature',
																	statut='$statut',
																	descriptif='".addslashes($descriptif)."';";
							//echo $sql;
							$insert=mysql_query($sql);
							if(!$insert){
								echo "ERREUR sur $sql\n";
							}
							# Insérer dans une table sauvegarde si il s'agit d'une sauvegarde
						}
					}
					// Ménage:
					//rm -f resultat_restauration.txt
					// Celui-là est récupéré où? en /tmp?
					if(file_exists($lanceur_recup)) {
						//unlink($lanceur_recup);
						if(!unlink($lanceur_recup)){
							echo "La suppression de $lanceur_recup a échoué.\n";
						}
					}

					if($corrige_mac!="") {
						if(file_exists("$tftp_pxelinux_cfg/01-$corrige_mac")) {
							unlink("$tftp_pxelinux_cfg/01-$corrige_mac");
						}
					}

					// Supprimer l'entrée dans se3_tftp_action
					$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine';";
					$nettoyage=mysql_query($sql);
					if(!$nettoyage){
						echo "ERREUR lors de la suppression de l'action sur\n   $sql\n";
					}
					exit();
				}
				else {
					# On remet à plus tard...
					#at +f 1 minute $0 $*
					//exec("at +f 1 minute $lanceur_recup",$retour);
					//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
					@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
					# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
					# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

					// Tester le $retour
					if(count($retour)>0){
						echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
						for($i=0;$i<count($retour);$i++){
							echo "$retour[$i]\n";
						}
					}
				}
			}
			else {
				# On remet à plus tard...
				#at +f 1 minute $0 $*
				//exec("at +f 1 minute $lanceur_recup",$retour);
				//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
				@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
				# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
				# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

				// Tester le $retour
				if(count($retour)>0){
					echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
					for($i=0;$i<count($retour);$i++){
						echo "$retour[$i]\n";
					}
				}
			}
			break;
		case "sauvegarde_sysresccd":
			$url="http://$ip/resultat_sauvegarde.txt";
			//wget --tries=1 http://$ip/~hacker/resultat_restauration.txt

			if(@exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'")) {
				if($fl=@file($url)){
					// Terminé... on renseigne une table

					$statut=trim($fl[0]);
					//if("$statut"=="SUCCES") {

					$descriptif="";
					$partition="";
					$image="";
					for($i=1;$i<count($fl);$i++){
						if(preg_match("/^partition=/",$fl[$i])) {$partition=trim(preg_replace("/^partition=/","",$fl[$i]));}
						if(preg_match("/^image=/",$fl[$i])) {$image=trim(preg_replace("/^image=/","",$fl[$i]));}
						$descriptif.=preg_replace("/['\"]/","[__]",trim($fl[$i]))."\n";
					}
					$descriptif=trim($descriptif);

					$creation_table=creation_tftp_tables();
					if(!$creation_table){
						echo "Erreur lors de la création de la table 'se3_tftp_rapports'\n";
					}
					else{
						//$sql="SELECT * FROM se3_tftp_action WHERE id='$id_machine';";
						$sql="SELECT * FROM se3_dhcp WHERE id='$id_machine';";
						$info_machine=mysql_query($sql);

						if(!$info_machine){
							echo "La machine n°$id_machine n'existe pas dans la table 'se3_dhcp'.\n";
						}
						else{
							$lig_machine=mysql_fetch_object($info_machine);
							$mac_machine=$lig_machine->mac;
							$nom_machine=$lig_machine->name;

							$corrige_mac=strtolower(strtr($mac_machine,":","-"));

							# Insérer dans une table rapport
							$sql="INSERT INTO se3_tftp_rapports SET id='$id_machine',
																	name='$nom_machine',
																	mac='$mac_machine',
																	date='".timestamp_to_mysql_date($instant)."',
																	tache='$nature',
																	statut='$statut',
																	descriptif='".addslashes($descriptif)."';";
							//echo $sql;
							$insert=mysql_query($sql);
							if(!$insert){
								echo "ERREUR sur $sql\n";
							}

							if($statut=="SUCCES"){
								//$url="http://$ip/~hacker/partitionnement.out";
								$url="http://$ip/partitionnement.out";
								//wget --tries=1 http://$ip/~hacker/resultat_restauration.txt

								$partitionnement="";
								if(@exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'")) {
									if($fl=@file($url)){
										for($i=1;$i<count($fl);$i++){
											$partitionnement.=$fl[$i];
										}
										if(addslashes(preg_replace("/['\"]/","[__]",$partitionnement))!=$partitionnement) {
											echo "$partitionnement\n\n".addslashes(preg_replace("/['\"]/","[__]",$partitionnement))."\n\n";
											$partitionnement="";
										}
									}
								}

								//$url="http://$ip/~hacker/df.txt";
								$url="http://$ip/df.txt";
								$df="";
								if(@exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'")) {
									if($fl=@file($url)){
										$df=$fl[0];
									}
								}

								# Insérer dans une table sauvegarde si il s'agit d'une sauvegarde
								$creation_table=creation_tftp_tables();
								if(!$creation_table){
									echo "Erreur lors de la création de la table 'se3_tftp_sauvegardes'\n";
								}
								else{
									$sql="INSERT INTO se3_tftp_sauvegardes SET id='$id_machine',
																	name='$nom_machine',
																	mac='$mac_machine',
																	partition='$partition',
																	image='$image',
																	date='".timestamp_to_mysql_date($instant)."',
																	descriptif='".addslashes($descriptif)."',
																	df='$df',
																	partitionnement='".addslashes($partitionnement)."';";
									$insert=mysql_query($sql);
									if(!$insert){
										echo "ERREUR sur $sql\n";
									}
								}


							}
						}
					}
					// Ménage:
					//rm -f resultat_restauration.txt
					// Celui-là est récupéré où? en /tmp?
					if(file_exists($lanceur_recup)) {
						//unlink($lanceur_recup);
						if(!unlink($lanceur_recup)){
							echo "La suppression de $lanceur_recup a échoué.\n";
						}
					}

					if($corrige_mac!="") {
						if(file_exists("$tftp_pxelinux_cfg/01-$corrige_mac")) {
							unlink("$tftp_pxelinux_cfg/01-$corrige_mac");
						}
					}

					// Supprimer l'entrée dans se3_tftp_action
					$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine';";
					$nettoyage=mysql_query($sql);
					if(!$nettoyage){
						echo "ERREUR lors de la suppression de l'action sur\n   $sql\n";
					}
					exit();
				}
				else {
					# On remet à plus tard...
					#at +f 1 minute $0 $*
					//exec("at +f 1 minute $lanceur_recup",$retour);
					//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
					@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
					# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
					# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

					// Tester le $retour
					if(count($retour)>0){
						echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
						for($i=0;$i<count($retour);$i++){
							echo "$retour[$i]\n";
						}
					}
				}
			}
			else {
				# On remet à plus tard...
				#at +f 1 minute $0 $*
				//exec("at +f 1 minute $lanceur_recup",$retour);
				//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
				@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
				# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
				# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

				// Tester le $retour
				if(count($retour)>0){
					echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
					for($i=0;$i<count($retour);$i++){
						echo "$retour[$i]\n";
					}
				}
			}
			break;
		case "rapport_sysresccd":
			//echo "recup_rapport.sh: rapport: A FAIRE";
			/*
			/home/hacker/Public/modules.txt
			/home/hacker/Public/lsmod.txt

			tar -czf disques.tar.gz disk_*.out disk_*.fdisk
			disk_${A}.out
			disk_${A}.fdisk
			# à inscrire dans la table se3_tftp_sauvegardes

			tar -czf sauvegardes.tar.gz sauvegardes_*.txt
			sauvegardes_${B}.txt
			sauvegardes_${B}_details.txt
			# à inscrire dans la table se3_tftp_sauvegardes

			tar -czf df.tar.gz df_*.txt
			*/

			$url="http://$ip/lsmod.txt";
			//wget --tries=1 http://$ip/~hacker/lsmod.txt

			if(@exec("ping ".$ip." -c 1 -w 1 | grep received | awk '{print $4}'")) {
				if($fl=@file($url)){
					// Terminé... on renseigne une table

					fich_log_debug("Le lsmod.txt a été trouvé.\n");

					// Le rapport a été généré.
					// On va tester l'existence des fichiers...
					$tab_fich=array("hda","hdb","hdc","hdd","sda","sdb","sdc","sdd");

					// Variable destinée à contenir les disk_*.out correspondant aux tables de partitions des disques de la machine
					$partitionnement="";
					// CE PARTITIONNEMENT DEVRAIT ETRE DANS LA BOUCLE for($i=0;$i<count($tab_fich);$i++) {
					// SI PLUSIEURS DISQUES DURS SONT PARCOURUS...
					// ... mais les insertions dans les tables devraient aussi en tenir compte...
					// POUR QUE L'EXPLOITATION DANS UNE PAGE WEB SOIT POSSIBLE, IL FAUDRAIT AJOUTER DES CHAMPS SEPARES POUR LE df, LE PARTITIONNEMENT DANS se3_tftp_rapports
					$infos="";
					$sauvegardes=array();
					$cpt=0;
					for($i=0;$i<count($tab_fich);$i++) {
						unset($tab_part);
						$tab_part=array();

						//$url="http://$ip/~hacker/disk_".$tab_fich[$i].".out";
						$url="http://$ip/disk_".$tab_fich[$i].".out";
						unset($fl2);
						if($fl2=@file($url)){
							fich_log_debug("Le fichier disk_".$tab_fich[$i].".out a été trouvé.\n");

							if($i>0) {
								$partitionnement.="___+*+___";
								//$partitionnement.="___+-+___";
							}
							for($j=0;$j<count($fl2);$j++){
								$partitionnement.=$fl2[$j];
								//if(preg_match("/^/dev//".$tab_fich[$i],$fl2[$j])) {
								//if(preg_match("|^/dev/|".$tab_fich[$i],$fl2[$j])) {
								if(mb_ereg("^/dev/".$tab_fich[$i],$fl2[$j])) {
									fich_log_debug("Lecture de la ligne $j:\n   $fl2[$j]\ndu sfdisk.out de /dev/$tab_fich[$i] dans le $url\n");
									unset($tab_tmp);
									$tab_tmp=explode(" ",$fl2[$j]);
									for($m=0;$m<count($tab_tmp);$m++) {
										fich_log_debug("\$tab_tmp[$m]=$tab_tmp[$m]\n");
									}
									//$tab_part[]=preg_match("/^/dev//",$tab_tmp[0]);
									//$tab_part[]=preg_replace("|^/dev/|","",$tab_tmp[0]);
									$tab_part[]=mb_ereg_replace("^/dev/","",$tab_tmp[0]);
									//fich_log_debug("Ajout de la partition: '".preg_replace("/^/dev//","",$tab_tmp[0])."\n");
									//fich_log_debug("Ajout de la partition: '".preg_replace("|^/dev/|","",$tab_tmp[0])."\n");
									fich_log_debug("Ajout de la partition: '".mb_ereg_replace("^/dev/","",$tab_tmp[0])."\n");

									//$url="http://$ip/~hacker/df_".preg_replace("/^/dev//","",$tab_tmp[0]).".txt";
									//$url="http://$ip/df_".preg_replace("/^/dev//","",$tab_tmp[0]).".txt";
									//$url="http://$ip/df_".preg_replace("|^/dev/|","",$tab_tmp[0]).".txt";
									$url="http://$ip/df_".mb_ereg_replace("^/dev/","",$tab_tmp[0]).".txt";
									fich_log_debug("Recherche de $url\n");
									unset($fl4);
									if($fl4=@file($url)) {
										//fich_log_debug("df_".preg_replace("/^/dev//","",$tab_tmp[0]).".txt trouvé.\n");
										//fich_log_debug("df_".preg_replace("|^/dev/|","",$tab_tmp[0]).".txt trouvé.\n");
										fich_log_debug("df_".mb_ereg_replace("^/dev/","",$tab_tmp[0]).".txt trouvé.\n");
										for($m=0;$m<count($fl4);$m++){
											$infos.=$fl4[$m];
										}
									}
								}
							}
							if(addslashes(preg_replace("/['\"]/","[__]",$partitionnement))!=$partitionnement) {
								echo "$partitionnement\n\n".addslashes(preg_replace("/['\"]/","[__]",$partitionnement))."\n\n";
								$partitionnement="";
							}
						}

						fich_log_debug("\n\$partitionnement=$partitionnement\n");


						// Traitement des fichiers de sauvegarde trouvés pour renseigner la table se3_tftp_sauvegardes
						for($k=0;$k<count($tab_part);$k++) {
							fich_log_debug("Test de \$tab_part[$k]=".$tab_part[$k]."\n");

							//$url="http://$ip/~hacker/sauvegardes_".$tab_part[$k].".txt";
							$url="http://$ip/sauvegardes_".$tab_part[$k].".txt";
							unset($fl2);
							if($fl2=@file($url)){
								fich_log_debug("sauvegardes_".$tab_part[$k].".txt trouvé.\n");

								//$url="http://$ip/~hacker/sauvegardes_".$tab_part[$k]."_details.txt";
								$url="http://$ip/sauvegardes_".$tab_part[$k]."_details.txt";
								unset($fl3);
								$fl3=@file($url);

								//$url="http://$ip/~hacker/df_".$tab_part[$k].".txt";
								$url="http://$ip/df_".$tab_part[$k].".txt";
								unset($fl4);
								$fl4=@file($url);

								for($j=0;$j<count($fl2);$j++){
									$sauvegardes[$cpt]=array();
									// $tab_part[$k] est la partition de stockage, pas la partition sauvegardée
									//$sauvegardes[$cpt]['partition']=$tab_part[$k];
									$sauvegardes[$cpt]['partition']="";
									$sauvegardes[$cpt]['chemin']=trim($fl2[$j]);
									$sauvegardes[$cpt]['details']="";
									$sauvegardes[$cpt]['df']="";

									fich_log_debug("\$sauvegardes[$cpt]['chemin']=".$sauvegardes[$cpt]['chemin']."\n");

									if($fl3) {
										fich_log_debug("sauvegardes_".$tab_part[$k]."_details.txt trouvé.\n");
										$temoin1="n";
										for($m=0;$m<count($fl3);$m++){
											//if(preg_match("/Infos sur /i".$sauvegardes[$cpt]['chemin'],$fl3[$m])) {$temoin1="y";}
											if(mb_ereg("Infos sur ".$sauvegardes[$cpt]['chemin'],$fl3[$m])) {$temoin1="y";}
											//if(preg_match("/___+\*+___/",trim($fl3[$m]))) {$temoin1="n";echo "TRUC";}
											//if(preg_match("/^___+/",trim($fl3[$m]))) {$temoin1="n";}
											if(preg_match("/^___\+\*\+___$/",trim($fl3[$m]))) {$temoin1="n";}
											if($temoin1=="y") {
												$sauvegardes[$cpt]['details'].=$fl3[$m];
											}

											if(preg_match("/^partition=/",$fl3[$m])) {
												$sauvegardes[$cpt]['partition']=preg_replace("/^partition=/","",$fl3[$m]);
											}
										}
									}

									if($fl4) {
										fich_log_debug("df_".$tab_part[$k].".txt trouvé.\n");
										for($m=0;$m<count($fl4);$m++){
											$sauvegardes[$cpt]['df'].=$fl4[$m];
										}
									}

									$cpt++;
								}
							}
						}
					}


					//========================================
					$network_driver=array();
					$url="http://$ip/~hacker/network_driver.txt";
					unset($fl5);
					if($fl5=@file($url)){
						for($m=0;$m<count($fl5);$m++){
							$network_driver[]=$fl5[$m];
							fich_log_debug("network_driver[$m]=".$fl5[$m]."\n");
						}
					}

					$storage_driver=array();
					$url="http://$ip/~hacker/storage_driver.txt";
					unset($fl6);
					if($fl6=@file($url)){
						for($m=0;$m<count($fl6);$m++){
							$storage_driver[]=$fl6[$m];
							fich_log_debug("storage_driver[$m]=".$fl6[$m]."\n");
						}
					}
					//========================================

					// Insertion dans les tables
					$creation_table=creation_tftp_tables();
					if(!$creation_table){
						echo "Erreur lors de la création de la table 'se3_tftp_rapports'\n";
					}
					else{
						//$sql="SELECT * FROM se3_tftp_action WHERE id='$id_machine';";
						$sql="SELECT * FROM se3_dhcp WHERE id='$id_machine';";
						$info_machine=mysql_query($sql);

						if(!$info_machine){
							echo "La machine n°$id_machine n'existe pas dans la table 'se3_dhcp'.\n";
						}
						else{
							$lig_machine=mysql_fetch_object($info_machine);
							$mac_machine=$lig_machine->mac;
							$nom_machine=$lig_machine->name;

							$corrige_mac=strtolower(strtr($mac_machine,":","-"));

							$statut="SUCCES";
							$descriptif=$partitionnement;
							//if($descriptif!="") {$descriptif.="\n";}
							if($descriptif!="") {
								$descriptif.="\n";
							}
							$descriptif.="___FIN_PARTITIONNEMENT___";
							$descriptif.="\n";

							$descriptif.=$infos;
							if($infos!="") {
								$descriptif.="\n";
							}
							$descriptif.="___FIN_INFOS_DF___";
							$descriptif.="\n";

							if(count($sauvegardes)>0) {
								if($descriptif!="") {$descriptif.="\n";}
								for($i=0;$i<count($sauvegardes);$i++) {
									$descriptif.=$sauvegardes[$i]['chemin']."\n";
									if($sauvegardes[$i]['details']!="") {
										$descriptif.=$sauvegardes[$i]['details']."\n";
									}
									/*
									// Là, on insère le volume dispo autant de fois qu'il y a de sauvegarde, alors que le df est inséré une fois après les infos sur le partitionnement.
									if($sauvegardes[$i]['df']!="") {
										$descriptif.=$sauvegardes[$i]['df']."\n";
									}
									*/
								}
							}

							# Insérer dans une table rapport
							$sql="INSERT INTO se3_tftp_rapports SET id='$id_machine',
																	name='$nom_machine',
																	mac='$mac_machine',
																	date='".timestamp_to_mysql_date($instant)."',
																	tache='$nature',
																	statut='$statut',
																	descriptif='".addslashes($descriptif)."';";
							//echo $sql;
							$insert=mysql_query($sql);
							if(!$insert){
								echo "ERREUR sur $sql\n";
							}

							if(count($sauvegardes)>0) {
								$creation_table=creation_tftp_tables();
								if(!$creation_table){
									echo "Erreur lors de la création de la table 'se3_tftp_sauvegardes'\n";
								}
								else{
									for($i=0;$i<count($sauvegardes);$i++) {
										$sql="INSERT INTO se3_tftp_sauvegardes SET id='$id_machine',
																		name='$nom_machine',
																		mac='$mac_machine',
																		partition='".$sauvegardes[$i]['partition']."',
																		image='".$sauvegardes[$i]['chemin']."',
																		date='".timestamp_to_mysql_date($instant)."',
																		descriptif='".addslashes($sauvegardes[$i]['details'])."',
																		df='".$sauvegardes[$i]['df']."',
																		partitionnement='".addslashes($partitionnement)."';";

										$insert=mysql_query($sql);
										if(!$insert){
											echo "ERREUR sur $sql\n";
										}
									}
								}
							}

							//========================================
							$sql="DELETE FROM se3_tftp_infos WHERE id='$id_machine';";
							//echo $sql;
							$delete=mysql_query($sql);
							if(!$delete){
								echo "ERREUR sur $sql\n";
							}
							for($i=0;$i<count($network_driver);$i++) {
								$sql="INSERT INTO se3_tftp_infos SET id='$id_machine',
																name='$nom_machine',
																mac='$mac_machine',
																nom='network_driver',
																valeur='".trim(addslashes($network_driver[$i]))."';";
								$insert=mysql_query($sql);
								if(!$insert){
									echo "ERREUR sur $sql\n";
								}
							}
							for($i=0;$i<count($storage_driver);$i++) {
								$sql="INSERT INTO se3_tftp_infos SET id='$id_machine',
																name='$nom_machine',
																mac='$mac_machine',
																nom='storage_driver',
																valeur='".trim(addslashes($storage_driver[$i]))."';";
								$insert=mysql_query($sql);
								if(!$insert){
									echo "ERREUR sur $sql\n";
								}
							}
							//========================================

						}
					}
					// Ménage:
					//rm -f resultat_restauration.txt
					// Celui-là est récupéré où? en /tmp?
					if(file_exists($lanceur_recup)) {
						//unlink($lanceur_recup);
						if(!unlink($lanceur_recup)){
							echo "La suppression de $lanceur_recup a échoué.\n";
						}
					}

					if($corrige_mac!="") {
						if(file_exists("$tftp_pxelinux_cfg/01-$corrige_mac")) {
							unlink("$tftp_pxelinux_cfg/01-$corrige_mac");
						}
					}

					// Supprimer l'entrée dans se3_tftp_action
					$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine';";
					$nettoyage=mysql_query($sql);
					if(!$nettoyage){
						echo "ERREUR lors de la suppression de l'action sur\n   $sql\n";
					}
					exit();
				}
				else {
					# On remet à plus tard...
					#at +f 1 minute $0 $*
					//exec("at +f 1 minute $lanceur_recup",$retour);
					//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
					@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
					# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
					# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

					// Tester le $retour
					if(count($retour)>0){
						echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
						for($i=0;$i<count($retour);$i++){
							echo "$retour[$i]\n";
						}
					}
				}
			}
			else {
				# On remet à plus tard...
				#at +f 1 minute $0 $*
				//exec("at +f 1 minute $lanceur_recup",$retour);
				//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
				@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
				# où $lanceur_recup est généré par l'interface Web SE3 et effectue:
				# recup_rapport.sh $id_machine $ip $nature_tache $timestamp_limite_validite_relance

				// Tester le $retour
				if(count($retour)>0){
					echo "La programmation\n   at -f $lanceur_recup now + 1 minute\na échoué...\n";
					for($i=0;$i<count($retour);$i++){
						echo "$retour[$i]\n";
					}
				}
			}

			break;
	}

?>
