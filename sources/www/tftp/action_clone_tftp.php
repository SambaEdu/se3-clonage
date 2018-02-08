<?php
/* $Id: action_clone_tftp.php 9151 2016-02-08 01:05:04Z keyser $
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
include "printers.inc.php";

require("lib_action_tftp.php");

//aide
$_SESSION["pageaide"]="Le_module_Clonage_des_stations#Programmer_un_clonage";

// On active les rapports d'erreurs:
//error_reporting(E_ALL);

// Liste des modules réseau pour Udpcast
// Appliquer le traitement:
// chaine="";while read A;do B=$(echo "$A" | cut -d'"' -f2);chaine="$chaine,'$B'";done<netmodule.txt;echo $chaine
// sur la portion appropriée de la page http://udpcast.linux.lu/cast-o-matic/stage2.cgi (en ayant cliqué sur 'Chech all' dans http://udpcast.linux.lu/cast-o-matic/ et enregistré le résultat sous le nom netmodule.txt)
$tab_netmodule=array('3c59x','8139cp','8139too','82596','8390','amd8111e','atp','b44','cxgb3','de2104x','dgrs','dmfe','e100','eepro100','eexpress','epic100','es3210','forcedeth','hp100','lne390','lp486e','mii','natsemi','ne3210','ne2k-pci','ni52','pcnet32','qla3xxx','sc92031','sis900','smc9194','smc-ultra32','starfire','strip','sundance','sungem','sunhme','tlan','tulip','typhoon','uli526x','via-rhine','winbond-840','acenic','atl1','bnx2','cassini','cxgb','dl2k','e1000','fealnx','hamachi','ixgb','myri10ge','netxen_nic','ns83820','r8169','sb1000','sis190','sk98lin','skge','sky2','s2io','tg3','via-velocity','yellowfin','3c574_cs','3c589_cs','airo','airo_cs','arlan','atmel','atmel_pci','atmel_cs','axnet_cs','fmvj18x_cs','hermes','ipw2100','ipw2200','libertas','netwave_cs','nmclan_cs','orinoco','orinoco_cs','orinoco_nortel','orinoco_pci','orinoco_plx','orinoco_tmd','pcnet_cs','prism54','ray_cs','smc91c92_cs','spectrum_cs','usb8xxx','wl3501_cs','wavelan','wavelan_cs','xirc2ps_cs','xircom_cb','xircom_tulip_cb','3c501','3c503','3c505','3c507','3c509','3c515','ac3200','at1700','cs89x0','de4x5','depca','e2100','eth16i','eepro','ewrk3','hp-plus','hp','lance','ne','ni5010','ni65','seeq8005','smc-ultra','wd','znet');


// Liste des modules disque pour Udpcast
// Appliquer le traitement:
// chaine="";while read A;do B=$(echo "$A" | cut -d'"' -f2);chaine="$chaine,'$B'";done<diskmodule.txt;echo $chaine
// sur la portion appropriée de la page http://udpcast.linux.lu/cast-o-matic/stage2.cgi (en ayant cliqué sur 'Chech all' dans http://udpcast.linux.lu/cast-o-matic/ et enregistré le résultat sous le nom diskmodule.txt)
$tab_diskmodule=array('ahci','ata_piix','sata_inic162x','sata_mv','sata_nv','sata_promise','sata_qstor','sata_sil','sata_sil24','sata_sis','sata_svw','sata_sx4','sata_uli','sata_via','sata_vsc','pata_ali','pata_amd','pata_artop','pata_atiixp','pata_cmd640','pata_cmd64x','pata_cs5520','pata_cs5530','pata_cs5535','pata_cypress','pata_efar','pata_hpt366','pata_hpt37x','pata_hpt3x2n','pata_hpt3x3','pata_isapnp','pata_it8213','pata_it821x','pata_jmicron','pata_legacy','pata_marvell','pata_mpiix','pata_netcell','pata_ns87410','pata_oldpiix','pata_opti','pata_optidma','pata_pcmcia','pata_pdc2027x','pata_pdc202xx_old','pata_platform','pata_qdi','pata_radisys','pata_rz1000','pata_sc1200','pata_serverworks','pata_sil680','pata_sis','pata_sl82c105','pata_triflex','pata_via','pata_winbond','3w-9xxx','53c700','advansys','aha152x','aha152x_cs','aha1542','aha1740','aic7xxx','aic79xx','aic94xx','arcmsr','BusLogic','dc395x','dpt_i2o','dtc','fdomain','fdomain_cs','g_NCR5380','g_NCR5380_mmio','hptiop','in2000','ipr','iscsi_tcp','libiscsi','libsas','lpfc','NCR53c406a','nsp32','nsp_cs','pas16','pdc_adma','psi240i','qla1280','qla2100','qla2200','qla2300','qla2322','qla2xxx','qla4xxx','qla6312','qlogic_cs','qlogicfas','qlogicfas408','qlogicfc','qlogicisp','scsi_transport_iscsi','seagate','sim710','stex','sym53c416','sym53c500_cs','t128','u14-34f','ultrastor','wd7000','aacraid','megaraid_sas');

// Bibliothèque prototype Ajax pour afficher en décalé l'état des machines:
echo "<script type='text/javascript' src='../includes/prototype.js'></script>\n";

/*
echo "<script type='text/javascript'>
Event.observe(window, 'load', function() {
   Event.observe(document, 'keydown', func_KeyDown);
});
</script>\n";
*/

// CSS pour mes tableaux:
echo "<link type='text/css' rel='stylesheet' href='tftp.css' />\n";

if ((is_admin("system_is_admin",$login)=="Y")||(ldap_get_right("parc_can_clone",$login)=="Y"))
{
	// Choix des parcs:
	$parc=isset($_POST['parc']) ? $_POST['parc'] : (isset($_GET['parc']) ? $_GET['parc'] : NULL);
	// Choix des machines:
	$id_emetteur=isset($_POST['id_emetteur']) ? $_POST['id_emetteur'] : (isset($_GET['id_emetteur']) ? $_GET['id_emetteur'] : NULL);
	$id_recepteur=isset($_POST['id_recepteur']) ? $_POST['id_recepteur'] : (isset($_GET['id_recepteur']) ? $_GET['id_recepteur'] : NULL);


	// Création de la table dès que possible:
	creation_tftp_tables();

	// Paramètres pour UdpCast
	$disk=isset($_POST['disk']) ? $_POST['disk'] : (isset($_GET['disk']) ? $_GET['disk'] : NULL);
	$compr=isset($_POST['compr']) ? $_POST['compr'] : (isset($_GET['compr']) ? $_GET['compr'] : NULL);
	$port=isset($_POST['port']) ? $_POST['port'] : (isset($_GET['port']) ? $_GET['port'] : NULL);
	$enableDiskmodule=isset($_POST['enableDiskmodule']) ? $_POST['enableDiskmodule'] : (isset($_GET['enableDiskmodule']) ? $_GET['enableDiskmodule'] : NULL);
	$diskmodule=isset($_POST['diskmodule']) ? $_POST['diskmodule'] : (isset($_GET['diskmodule']) ? $_GET['diskmodule'] : NULL);
	$netmodule=isset($_POST['netmodule']) ? $_POST['netmodule'] : (isset($_GET['netmodule']) ? $_GET['netmodule'] : NULL);
	$min_receivers=isset($_POST['min_receivers']) ? $_POST['min_receivers'] : (isset($_GET['min_receivers']) ? $_GET['min_receivers'] : NULL);

	$max_wait=isset($_POST['max_wait']) ? $_POST['max_wait'] : (isset($_GET['max_wait']) ? $_GET['max_wait'] : NULL);
	$min_wait=isset($_POST['min_wait']) ? $_POST['min_wait'] : (isset($_GET['min_wait']) ? $_GET['min_wait'] : NULL);
	$start_timeout=isset($_POST['start_timeout']) ? $_POST['start_timeout'] : (isset($_GET['start_timeout']) ? $_GET['start_timeout'] : NULL);

	$auto_reboot=isset($_POST['auto_reboot']) ? $_POST['auto_reboot'] : (isset($_GET['auto_reboot']) ? $_GET['auto_reboot'] : NULL);

	// Paramètres concernant l'action immédiate sur les machines choisies:
	$wake=isset($_POST['wake']) ? $_POST['wake'] : (isset($_GET['wake']) ? $_GET['wake'] : "n");
	$shutdown_reboot=isset($_POST['shutdown_reboot']) ? $_POST['shutdown_reboot'] : (isset($_GET['shutdown_reboot']) ? $_GET['shutdown_reboot'] : NULL);


	$pref_distrib_clonage=crob_getParam('pref_distrib_clonage');
	if(($pref_distrib_clonage=='udpcast')||($pref_distrib_clonage=='sysresccd')) {$valeur_par_defaut=$pref_distrib_clonage;}
	else {$valeur_par_defaut="udpcast";}
	$distrib=isset($_POST['distrib']) ? $_POST['distrib'] : $valeur_par_defaut;

	$type_os=isset($_POST['type_os']) ? $_POST['type_os'] : "xp";
	$sysresccd_kernel=isset($_POST['sysresccd_kernel']) ? $_POST['sysresccd_kernel'] : "rescue32";


	$pref_ntfsclone_udpcast=crob_getParam('pref_ntfsclone_udpcast');
	if(($pref_ntfsclone_udpcast=='y')||($pref_ntfsclone_udpcast=='n')) {$valeur_par_defaut=$pref_ntfsclone_udpcast;}
	else {$valeur_par_defaut="n";}
	$ntfsclone_udpcast=isset($_POST['ntfsclone_udpcast']) ? $_POST['ntfsclone_udpcast'] : $valeur_par_defaut;

	$pref_clonage_compression=crob_getParam('pref_clonage_compression');
	if(($pref_clonage_compression=='')||(!in_array($pref_clonage_compression,array('none','gzip','lzop','pbzip2')))) {$pref_clonage_compression='lzop';}

	$pref_clonage_max_wait=crob_getParam('pref_clonage_max_wait');
	if($pref_clonage_max_wait=='') {$pref_clonage_max_wait=15;}
	$pref_clonage_min_wait=crob_getParam('pref_clonage_min_wait');
	if($pref_clonage_min_wait=='') {$pref_clonage_min_wait=10;}
	$pref_clonage_start_timeout=crob_getParam('pref_clonage_start_timeout');
	if($pref_clonage_start_timeout=='') {$pref_clonage_start_timeout=20;}


	echo "<h1>".gettext("Action clonage TFTP")."</h1>\n";

	$restriction_parcs="n";
	if(is_admin("system_is_admin",$login)!="Y") {
		$restriction_parcs="y";
		$tab_delegated_parcs=list_delegated_parcs($login);
		if(count($tab_delegated_parcs)==0) {
			echo "<p>Aucun parc ne vous a été délégué.</p>\n";
			include ("pdp.inc.php");
			die();
		}
	}

	$msg_fichiers_manquants="";
	$temoin_fichiers_requis="y";
	$chemin_tftpboot="/tftpboot";
	$tab_udpcast_file=array("vmlu26", "udprd", "vmlu26.old", "udprd.old");
	for($loop=0;$loop<count($tab_udpcast_file);$loop++) {
		if(!file_exists($chemin_tftpboot."/".$tab_udpcast_file[$loop])) {
			$msg_fichiers_manquants.="<span style='color:red'>".$chemin_tftpboot."/".$tab_udpcast_file[$loop]." est absent.</span><br />\n";
			$msg_fichiers_manquants.="Vous devriez effectuer le telechargement udpcast en <a href='config_tftp.php'>Configurer le module TFTP</a><br />\n";
			$temoin_fichiers_requis="n";
		}
	}

	if($msg_fichiers_manquants!="") {
		$msg_fichiers_manquants.="<p>Vous ne pourrez pas effectuer le clonage avec la mini-distribution Linux UdpCast sans d'abord télécharger les fichiers manquants.</p>";
	}

	$temoin_sysresccd=check_sysresccd_files();
	if($temoin_sysresccd=="y") {
		$msg_fichiers_manquants.="<p>Vous pourrez effectuer le clonage avec la distribution SysRescCD.</p>";
		$temoin_fichiers_requis="y";
	}
	else {
		$msg_fichiers_manquants.="<span style='color:red'>SysRescCD est absente.<br />Cette distribution permet de cloner avec une meilleure reconnaissance matérielle qu'UdpCast seul.<br />Vous devriez en effectuer le téléchargement via <a href='config_tftp.php'>Configurer le module TFTP</a>.</span><br />\n";
	}

	if($msg_fichiers_manquants!="") {
		echo $msg_fichiers_manquants;
	}

	if($temoin_fichiers_requis=="n") {
		echo "<p style='color:red'>ABANDON&nbsp;: Un ou des fichiers requis sont manquants.</p>\n";
		include ("pdp.inc.php");
		die();
	}

	if(!isset($parc)){

		echo "<p>Choisissez un ou des parcs:</p>\n";

		$list_parcs=search_machines("objectclass=groupOfNames","parcs");
		if ( count($list_parcs)==0) {
			echo "<br><br>";
			echo gettext("Il n'existe aucun parc. Vous devez d'abord créer un parc");
			include ("pdp.inc.php");
			exit;
		}
		sort($list_parcs);

		echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
		//echo "<input type=\"hidden\" name=\"action\" value=\"$action\" />\n";

		// Affichage des parcs sur 3/4 colonnes
		$nb_parcs_par_colonne=round(count($list_parcs)/3);
		echo "<table border='0'>\n";
		echo "<tr valign='top'>\n";
		echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
		echo "<td align='left'>\n";
		for ($loop=0; $loop < count($list_parcs); $loop++) {
			if(($loop>0)&&(round($loop/$nb_parcs_par_colonne)==$loop/$nb_parcs_par_colonne)){
				echo "</td>\n";
				echo "<td align='left'>\n";
			}

			if(($restriction_parcs=="n")||(in_array($list_parcs[$loop]["cn"], $tab_delegated_parcs))) {
				echo "<label for='parc_$loop'><input type='checkbox' id='parc_$loop' name='parc[]' value=\"".$list_parcs[$loop]["cn"]."\"";
				if(count($list_parcs)==1) {echo " checked";}
				echo " />".$list_parcs[$loop]["cn"]."</label>\n";
				echo "<br />\n";
			}
		}

		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider\" /></p>\n";

		echo "</form>\n";


		echo "<script type='text/javascript'>
nb_parcs=0;
id_parc='';
for(i=0;i<$loop;i++) {
	if(document.getElementById('parc_'+i)) {
		nb_parcs++;
		id_parc='parc_'+i;
	}
}
if(nb_parcs==1) {
	document.getElementById(id_parc).checked=true;
}
</script>\n";

		//echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";

		echo "<p style='text-indent: -7em; margin-left: 7em;'><b style='color:red'>ATTENTION&nbsp;:</b> Pour le bon fonctionnement du clonage, il est indispensable que les postes (<em>l'emetteur au moins</em>) soient configurés pour booter en priorité sur le réseau (<em>BIOS&nbsp;: boot PXE (network) avant disque dur</em>).<br />Sinon, quand le poste emetteur va redémarrer pour le clonage (*), il risque de redémarrer sous Window$ au lieu de démarrer en PXE.<br /><br />Et si vous lancez manuellement le clonage ensuite, le poste emetteur aura déjà réintégré le domaine et repris son nom initial. Vous clonerez alors toutes les machines sous ce même nom et elles n'intègreront pas le domaine sous leur nom propre.<br /><br />(*) Le poste emetteur reboote plusieurs fois dans l'opération (<em>il doit quitter le domaine (un reboot),<br />prendre le nom temporaire 'clone' (encore un reboot)<br />et ensuite seulement rebooter sur le réseau pour procéder au clonage</em>)</p>\n";
	}
	else {
		if(!isset($id_emetteur)) {
			echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";

			echo "<p>Choisissez l'émetteur.</p>\n";

			$tab_detect_doublons=array();
			$tab_infos_doublons=array();
			for($i=0;$i<count($parc);$i++){

				echo "<h2>Parc $parc[$i]</h2>\n";
				echo "<input type=\"hidden\" name=\"parc[]\" value=\"$parc[$i]\" />\n";

				$mp=gof_members($parc[$i],"parcs",1);
				$nombre_machine=count($mp);
				sort($mp);

				//echo "<table border='1'>\n";
				echo "<table class='crob'>\n";
				echo "<tr>\n";

				echo "<th>Nom</th>\n";
				echo "<th>Etat</th>\n";
				echo "<th>Session</th>\n";
				echo "<th>Config DHCP</th>\n";

				echo "<th>Emetteur</th>\n";
				echo "<th>Actions programmées</th>\n";
				echo "</tr>\n";

				for ($loop=0; $loop < count($mp); $loop++) {
					$mpenc=urlencode($mp[$loop]);

					// Test si on a une imprimante ou une machine
					$resultat=search_imprimantes("printer-name=$mpenc","printers");
					$suisje_printer="non";
					for ($loopp=0; $loopp < count($resultat); $loopp++) {
						if ($mpenc==$resultat[$loopp]['printer-name']) {
							$suisje_printer="yes";
							continue;
						}
					}

					if($suisje_printer=="non") {
						// Réinitialisation:
						$id_machine="";

						$mp_curr=search_machines2("(&(cn=$mpenc)(objectClass=ipHost))","computers");
						if(in_array($mp_curr[0]["macAddress"],$tab_detect_doublons)) {
							$temoin_check='n';
							$tab_infos_doublons[$mp_curr[0]["macAddress"]].=", ".$mpenc;
						}
						else {
							$temoin_check='y';
							$tab_detect_doublons[]=$mp_curr[0]["macAddress"];
							$tab_infos_doublons[$mp_curr[0]["macAddress"]]=$mpenc;
						}

						echo "<tr>\n";
						echo "<td width='20%'>".$mp[$loop]."</td>\n";

						// Etat: allumé ou éteint
						echo "<td width='20%'>";
						if ($mp_curr[0]["ipHostNumber"]) {
							$iphost=$mp_curr[0]["ipHostNumber"];

							echo "<div id='divip$loop'>Patientez</div>\n";
							echo "<script type='text/javascript'>
								// <![CDATA[
								new Ajax.Updater($('divip$loop'),'ajax_lib.php?ip=$iphost&mode=ping_ip',{method: 'get'});
								//]]>
							</script>\n";
						}
						echo "</td>\n";


						// Session: ouverte ou pas... sous quelle identité
						echo "<td width='20%'>\n";
						echo "<div id='divsession$loop'>Patientez</div>\n";
						echo "<script type='text/javascript'>
							// <![CDATA[
							new Ajax.Updater($('divsession$loop'),'ajax_lib.php?nom_machine=".$mp[$loop]."&mode=session',{method: 'get'});
							//]]>
						</script>\n";
						echo "</td>\n";


						// Etat config DHCP:
						// Par la suite il ne faudra pas prendre les IP dans l'annuaire,
						// mais dans la config DHCP parce que ce sont ces IP qui seront attribuées lors du boot PXE
						echo "<td width='20%'>\n";
						//$mp_curr=search_machines("(&(cn=$mpenc)(objectClass=ipHost))","computers");
						if ($mp_curr[0]["macAddress"]) {
							$sql="SELECT * FROM se3_dhcp WHERE mac='".$mp_curr[0]["macAddress"]."';";
							//echo "$sql<br />";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								$lig=mysql_fetch_object($res);
								$id_machine=$lig->id;

								//echo $lig->ip;
								echo "<img src=\"../elements/images/enabled.gif\" border='0' alt=\"$lig->ip\" title=\"$lig->ip\" />";
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse IP attribuée\" title=\"Pas d'adresse IP attribuée\" />";
							}
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse MAC dans l'annuaire???\" title=\"Pas d'adresse MAC dans l'annuaire???\" />";
						}
						echo "</td>\n";


						// Sélection de l'émetteur UDPCAST:
						echo "<td width='20%'>\n";
						/*
						foreach($mp_curr[0] as $champ => $valeur) {
							echo "\$mp_curr[0]['$champ']=$valeur<br />";
						}
						*/
						if($id_machine!=""){
							//echo "<input type='checkbox' name='id_machine[]' value='$id_machine' />\n";
							echo "<input type='radio' name='id_emetteur' value='$id_machine' />\n";
							// On affiche quand même la case à cocher parce qu'il se peut que la case soit désactivée si l'identifiant est absent de la table se3_dhcp
							if($temoin_check=='n') {
								//echo "<img src=\"../elements/images/info.png\" border='0' alt=\"La machine doit être dans plusieurs parcs ou inscrite sous plusieurs noms dans l'annuaire: ".$tab_infos_doublons[$mp_curr[0]["macAddress"]]."\" title=\"La machine doit être dans plusieurs parcs ou inscrite sous plusieurs noms dans l'annuaire: ".$tab_infos_doublons[$mp_curr[0]["macAddress"]]."\" />";
								echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('La machine doit être dans plusieurs parcs ou inscrite sous plusieurs noms dans l\'annuaire: ".$tab_infos_doublons[$mp_curr[0]["macAddress"]]."')")."\"><img name=\"action_image$loop\"  src=\"../elements/images/info.png\"></u>\n";

							}
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Il faut commencer par effectuer la configuration DHCP\" title=\"Il faut commencer par effectuer la configuration DHCP\" />";
						}
						echo "</td>\n";


						// Action programmée
						echo "<td>\n";
						if($id_machine!=""){
							$sql="SELECT * FROM se3_tftp_action WHERE id='".$id_machine."';";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								$lig=mysql_fetch_object($res);
								echo "<a href='visu_action.php?id_machine=$id_machine' target='_blank'>$lig->type programmé(e)</a>";
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'action programmée\" title=\"Pas d'action programmée\" />";
							}
						}
						echo "</td>\n";

						echo "</tr>\n";
					}
					flush();
				}
				echo "</table>\n";
			}
			echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider\" /></p>\n";
			echo "</form>\n";


		}
		elseif(!isset($id_recepteur)){
                        // Ajout keyser test emetteur bien allume
                        
			echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";

			$sql="SELECT * FROM se3_dhcp WHERE id='$id_emetteur';";
			$res=mysql_query($sql);
			$lig=mysql_fetch_object($res);
                        echo "<p>Emetteur: $lig->name (<i>id:$id_emetteur</i>)</p>\n";
                        echo "<input type=\"hidden\" name=\"id_emetteur\" value=\"$id_emetteur\" />\n";
                        exec("/usr/share/se3/sbin/tcpcheck 2 $lig->ip:445 | grep alive",$arrval,$return_value);
			if ($return_value == "1") {
			    echo "<p style='color:red;'>Attention, clonage impossible. La machine $lig->name est injoignable ou prot&#233;g&#233;e par un pare-feu  :  </p>\n ";
			    echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";
			    include ("pdp.inc.php");
			    exit();
                        }


			echo "<p>Choisissez les r&#233;cepteurs.</p>\n";
			$max_eff_parc=0;

			$tab_detect_doublons=array();
			$tab_infos_doublons=array();
			for($i=0;$i<count($parc);$i++){

				echo "<h2>Parc $parc[$i]</h2>\n";
				echo "<input type=\"hidden\" name=\"parc[]\" value=\"$parc[$i]\" />\n";

				$mp=gof_members($parc[$i],"parcs",1);
				$nombre_machine=count($mp);
				sort($mp);

				//echo "\$id_emetteur=$id_emetteur<br />\n";

				//echo "<table border='1'>\n";
				echo "<table class='crob'>\n";
				echo "<tr>\n";

				echo "<th>Nom</th>\n";
				echo "<th>Etat</th>\n";
				echo "<th>Session</th>\n";
				echo "<th>Config DHCP</th>\n";

				//echo "<th>Récepteurs</th>\n";
				echo "<th>Récepteurs<br />\n";
				echo "<a href='#' onclick='check_machine($i,\"check\");return false'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Tout cocher\" title=\"Tout cocher\" /></a>\n";
				echo " / <a href='#' onclick='check_machine($i,\"uncheck\");return false'><img src=\"../elements/images/disabled.gif\" border='0' alt=\"Tout décocher\" title=\"Tout décocher\" /></a>\n";
				echo "</th>\n";

				echo "<th>Actions programmées</th>\n";
				echo "</tr>\n";

				for ($loop=0; $loop < count($mp); $loop++) {
					$mpenc=urlencode($mp[$loop]);

					// Test si on a une imprimante ou une machine
					$resultat=search_imprimantes("printer-name=$mpenc","printers");
					$suisje_printer="non";
					for ($loopp=0; $loopp < count($resultat); $loopp++) {
						if ($mpenc==$resultat[$loopp]['printer-name']) {
							$suisje_printer="yes";
							continue;
						}
					}

					if($suisje_printer=="non") {
						// Réinitialisation:
						$id_machine="";

						$mp_curr=search_machines2("(&(cn=$mpenc)(objectClass=ipHost))","computers");
						if(in_array($mp_curr[0]["macAddress"],$tab_detect_doublons)) {
							$temoin_check='n';
							$tab_infos_doublons[$mp_curr[0]["macAddress"]].=", ".$mpenc;
						}
						else {
							$temoin_check='y';
							$tab_detect_doublons[]=$mp_curr[0]["macAddress"];
							$tab_infos_doublons[$mp_curr[0]["macAddress"]]=$mpenc;
						}
						echo "<tr>\n";
						echo "<td width='20%'>".$mp[$loop]."</td>\n";

						// Etat: allumé ou éteint
						echo "<td width='20%'>";
						//$mp_curr=search_machines2("(&(cn=$mpenc)(objectClass=ipHost))","computers");
						if ($mp_curr[0]["ipHostNumber"]) {
							$iphost=$mp_curr[0]["ipHostNumber"];

							echo "<div id='divip$loop'>Patientez</div>\n";
							echo "<script type='text/javascript'>
								// <![CDATA[
								new Ajax.Updater($('divip$loop'),'ajax_lib.php?ip=$iphost&mode=ping_ip',{method: 'get'});
								//]]>
							</script>\n";
						}
						echo "</td>\n";


						// Session: ouverte ou pas... sous quelle identité
						echo "<td width='20%'>\n";
						echo "<div id='divsession$loop'>Patientez</div>\n";
						echo "<script type='text/javascript'>
							// <![CDATA[
							new Ajax.Updater($('divsession$loop'),'ajax_lib.php?nom_machine=".$mp[$loop]."&mode=session',{method: 'get'});
							//]]>
						</script>\n";
						echo "</td>\n";


						// Etat config DHCP:
						// Par la suite il ne faudra pas prendre les IP dans l'annuaire,
						// mais dans la config DHCP parce que ce sont ces IP qui seront attribuées lors du boot PXE
						echo "<td width='20%'>\n";
						//$mp_curr=search_machines("(&(cn=$mpenc)(objectClass=ipHost))","computers");
						if ($mp_curr[0]["macAddress"]) {
							$sql="SELECT * FROM se3_dhcp WHERE mac='".$mp_curr[0]["macAddress"]."';";
							//echo "$sql<br />\n";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								$lig=mysql_fetch_object($res);
								$id_machine=$lig->id;
								//echo "\$id_machine=$id_machine<br />\n";

								//echo $lig->ip;
								echo "<img src=\"../elements/images/enabled.gif\" border='0' alt=\"$lig->ip\" title=\"$lig->ip\" />";
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse IP attribuée\" title=\"Pas d'adresse IP attribuée\" />";
							}
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse MAC dans l'annuaire???\" title=\"Pas d'adresse MAC dans l'annuaire???\" />";
						}
						echo "</td>\n";


						// Sélection des récepteurs UDPCAST:
						echo "<td width='20%'>\n";
						/*
						foreach($mp_curr[0] as $champ => $valeur) {
							echo "\$mp_curr[0]['$champ']=$valeur<br />";
						}
						*/
						if($id_machine!=""){
							if($id_machine!=$id_emetteur){
								//if($temoin_check=='y') {
									echo "<input type='checkbox' name='id_recepteur[]' id='machine_".$i."_".$loop."' value='$id_machine' />\n";
									//echo "<input type='radio' name='id_emetteur' value='$id_machine' />\n";
								//}
								//else {
								// On affiche quand même la case à cocher parce qu'il se peut que la case soit désactivée si l'identifiant est absent de la table se3_dhcp
								if($temoin_check=='n') {
									//echo "<img src=\"../elements/images/info.png\" border='0' alt=\"La machine doit être dans plusieurs parcs ou inscrite sous plusieurs noms dans l'annuaire: ".$tab_infos_doublons[$mp_curr[0]["macAddress"]]."\" title=\"La machine doit être dans plusieurs parcs ou inscrite sous plusieurs noms dans l'annuaire: ".$tab_infos_doublons[$mp_curr[0]["macAddress"]]."\" />";
									echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('La machine doit être dans plusieurs parcs ou inscrite sous plusieurs noms dans l\'annuaire: ".$tab_infos_doublons[$mp_curr[0]["macAddress"]]."')")."\"><img name=\"action_image$loop\"  src=\"../elements/images/info.png\"></u>\n";
								}
							}
							else{
								echo "<span style='color:green;'>Emetteur</span>\n";
							}
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Il faut commencer par effectuer la configuration DHCP\" title=\"Il faut commencer par effectuer la configuration DHCP\" />";
						}
						echo "</td>\n";


						// Action programmée
						echo "<td>\n";
						if($id_machine!=""){
							$sql="SELECT * FROM se3_tftp_action WHERE id='".$id_machine."';";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								$lig=mysql_fetch_object($res);
								echo "<a href='visu_action.php?id_machine=$id_machine' target='_blank'>$lig->type programmé(e)</a>";
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'action programmée\" title=\"Pas d'action programmée\" />";
							}
						}
						echo "</td>\n";

						echo "</tr>\n";
						flush();
					}
				}
				echo "</table>\n";
				if($max_eff_parc<$loop) {$max_eff_parc=$loop;}
			}

			echo "<script type='text/javascript'>
	function check_machine(num_parc,mode) {
		for(i=0;i<$max_eff_parc;i++){
			if(document.getElementById('machine_'+num_parc+'_'+i)){
				if(mode=='check'){
					document.getElementById('machine_'+num_parc+'_'+i).checked=true;
				}
				else{
					document.getElementById('machine_'+num_parc+'_'+i).checked=false;
				}
			}
		}
	}
</script>\n";

			echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider\" /></p>\n";
			echo "</form>\n";

		}
		else {
			$validation_parametres=isset($_POST['validation_parametres']) ? $_POST['validation_parametres'] : (isset($_GET['validation_parametres']) ? $_GET['validation_parametres'] : NULL);
			if(!isset($validation_parametres)) {
				echo "<h2>Paramétrage du clonage</h2>\n";

				//$nombre_machines=count($id_machine);
				//if($nombre_machines==0){
				if((!isset($id_emetteur))||(count($id_recepteur)==0)) {
					echo "<p>ERREUR: Il faut choisir au moins un émetteur et un récepteur.</p>\n";

					echo "<p><a href='#' onclick='history.go(-1);'>Retour au choix des machines à cloner</a>.</p>\n";

					echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";
					include ("pdp.inc.php");
					exit();
				}



				echo "<form method=\"post\" name=\"form_param\" action=\"".$_SERVER['PHP_SELF']."\">\n";
				// Liste des parcs:
				for($i=0;$i<count($parc);$i++){
					echo "<input type=\"hidden\" name=\"parc[]\" value=\"$parc[$i]\" />\n";
				}

				// Infos sur l'émetteur:
				$sql="SELECT * FROM se3_dhcp WHERE id='$id_emetteur';";
				$res=mysql_query($sql);
				$lig=mysql_fetch_object($res);
				echo "<p>Emetteur: $lig->name (<i>id:$id_emetteur</i>)</p>\n";
				echo "<input type=\"hidden\" name=\"id_emetteur\" value=\"$id_emetteur\" />\n";

				// Liste des machines récepteurs:
				$chaine="";
				$tab_dedoublonnage=array();
				for($i=0;$i<count($id_recepteur);$i++){
					$sql="SELECT * FROM se3_dhcp WHERE id='".$id_recepteur[$i]."';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)>0) {
						$lig=mysql_fetch_object($res);
						if(!in_array($lig->name,$tab_dedoublonnage)) {
							if($i>0) {$chaine.=", ";}
							$chaine.=$lig->name;
							echo "<input type=\"hidden\" name=\"id_recepteur[]\" value=\"$id_recepteur[$i]\" />\n";
							$tab_dedoublonnage[]=$lig->name;
						}
					}
				}
				//if(count($id_recepteur)>1){$s="s";}else{$s="";}
				if(count($tab_dedoublonnage)>1){$s="s";}else{$s="";}
				echo "<p>Machine$s clonée$s: $chaine</p>\n";

				echo "<p>Choisissez les paramètres de clonage: </p>\n";

				$temoin_sysresccd=check_sysresccd_files();

				if($temoin_sysresccd=="y") {
					// Il faut aussi le noyau et l'initram.igz dans /tftpboot, 
					echo "<p>";

					$temoin_fichiers_udpcast="y";
					$chemin_tftpboot="/tftpboot";
					$tab_udpcast_file=array("vmlu26", "udprd", "vmlu26.old", "udprd.old");
					for($loop=0;$loop<count($tab_udpcast_file);$loop++) {
						if(!file_exists($chemin_tftpboot."/".$tab_udpcast_file[$loop])) {
							$temoin_fichiers_udpcast="n";
							break;
						}
					}

					if($temoin_fichiers_udpcast=="y") {
						echo "<input type='radio' name='distrib' id='distrib_udpcast' value='udpcast' onchange='affiche_sections_distrib()' ";
						if($pref_distrib_clonage!="sysresccd") {echo "checked ";}
						echo "/><label for='distrib_udpcast'>Utiliser la distribution UdpCast</label><br />\n";
					}
					else {
						$pref_distrib_clonage="y";
					}

					echo "<input type='radio' name='distrib' id='distrib_sysresccd' value='sysresccd' onchange='affiche_sections_distrib()' ";
					if($pref_distrib_clonage=="sysresccd") {echo "checked ";}
					echo "/><label for='distrib_sysresccd'>Utiliser la distribution SysRescCD</label> (<i>plus long à booter et 300Mo de RAM minimum, mais meilleure détection des pilotes</i>)\n";
					//echo "<br />\n";
					echo "</p>\n";


echo "<div id='div_sysresccd_kernel'>\n";
echo "<table border='0'>\n";
echo "<tr>\n";
echo "<td valign='top'>\n";
echo "Utiliser le noyau&nbsp;: ";
echo "</td>\n";
echo "<td>\n";
echo "<input type='radio' name='sysresccd_kernel' id='sysresccd_kernel_auto' value='auto' checked /><label for='sysresccd_kernel_auto'>auto</label><br />\n";
echo "<input type='radio' name='sysresccd_kernel' id='sysresccd_kernel_rescue32' value='rescue32' /><label for='sysresccd_kernel_rescue32'>rescue32</label><br />\n";
echo "<input type='radio' name='sysresccd_kernel' id='sysresccd_kernel_altker32' value='altker32' /><label for='sysresccd_kernel_altker32'>altker32</label><br />\n";
echo "<input type='radio' name='sysresccd_kernel' id='sysresccd_kernel_rescue64' value='rescue64' /><label for='sysresccd_kernel_rescue64'>rescue64</label><br />\n";
echo "<input type='radio' name='sysresccd_kernel' id='sysresccd_kernel_altker64' value='altker64' /><label for='sysresccd_kernel_altker64'>altker64</label><br />\n";
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "</div>\n";

					echo "<div id='div_ntfsclone'>\n";
					$srcd_scripts_vers=crob_getParam('srcd_scripts_vers');
					if(($srcd_scripts_vers!='')&&($srcd_scripts_vers>=20110406)) {
						echo "<p>";
						echo "<input type='radio' name='ntfsclone_udpcast' id='ntfsclone_udpcast_n' value='n' ";
						if($pref_ntfsclone_udpcast!="y") {echo "checked ";}
						echo "/><label for='ntfsclone_udpcast_n'> Utiliser udp-sender/udp-receiver seuls</label><br />\n";
						echo "<input type='radio' name='ntfsclone_udpcast' id='ntfsclone_udpcast_y' value='y' ";
						if($pref_ntfsclone_udpcast=="y") {echo "checked ";}
						echo "/><label for='ntfsclone_udpcast_y'>Utiliser ntfsclone et udp-sender/udp-receiver</label> (<em style='color:red' title=\"Le caractère expérimental de ce mode doit être tempéré.
De nombreux clonages ont été effectués avec succès et ce beaucoup plus vite grâce à ntfsclone qu'avec udpcast seul.\">experimental</em>)<br />\n";
						echo "</p>\n";
					}
					else {
						echo "<input type='hidden' name='ntfsclone_udpcast' id='ntfsclone_udpcast_n' value='n' />\n";
					}
					echo "</div>\n";

				}
				else {
					echo "<p style='color:red'>SysRescCD est absent (<em>c'est pourtant le choix recommandé</em>).<br />Vous pouvez provoquer le téléchargement dans le menu Serveur TFTP/Configurer le module.<br />A défaut, UdpCast seul sera utilisé.</p>\n";
					echo "<input type=\"hidden\" name=\"distrib\" value=\"udpcast\" />\n";
				}

				echo "<table border='0'>\n";

				echo "<tr><td valign='top'>Type du système à cloner: </td>\n";
				echo "<td>";
				echo "<input type='radio' name='type_os' id='type_os_xp' value='xp' onchange='affiche_message_shutdown_cmd()' checked /><label for='type_os_xp'> Window XP, Seven, ou autre (<i>il y a une intégration du domaine à faire</i>)</label><br />\n";
				echo "<input type='radio' name='type_os' id='type_os_autre' value='autre' onchange='affiche_message_shutdown_cmd()' /><label for='type_os_autre'> Linux ou autre cas (<i>pas d'intégration automatique du domaine</i>)</label>\n";
				echo "</td></tr>\n";

				echo "<tr><td valign='top'>Périphérique à cloner: </td>\n";
				echo "<td>";
                                echo "<input type='text' name='disk' id='disk' value='sda' size='14' /><br>\n";
                                echo "Habituellement: <a href=\"javascript:affecter_valeur_disk('hda1')\" title=\"Cliquez pour prendre cette valeur.\">hda1</a> ou <a href=\"javascript:affecter_valeur_disk('sda1')\" title=\"Cliquez pour prendre cette valeur.\">sda1</a> pour la première partition<br>\n";
                                echo "et <a href=\"javascript:affecter_valeur_disk('hda')\" title=\"Cliquez pour prendre cette valeur.\">hda</a> ou <a href=\"javascript:affecter_valeur_disk('sda')\" title=\"Cliquez pour prendre cette valeur.\">sda</a> pour le disque complet.<br>\n";

                                echo "<p><em>Avec le choix ntfsclone+udpcast&nbsp;:</em><br />";
                                echo "Pour le clonage de seven 64bits choisir <strong><a href=\"javascript:affecter_valeur_disk('seven64')\" title=\"Cliquez pour prendre cette valeur.\">seven64</a></strong> (<em>seules les partitions NTFS sont clon&eacute;es</em>)<br />";
                                echo "Pour cloner Seven (<em>64 ou non</em>) et une partition Linux derri&egrave;re, choisir <strong><a href=\"javascript:affecter_valeur_disk('seven64_linux')\" title=\"Cliquez pour prendre cette valeur.\">seven64_linux</a></strong> <span style='color:red' title=\"Version du paquet scripts.tar.gz de SysRescCD requise 
sup&eacute;rieure &agrave; 20140409.
Version &agrave; controler dans la rubrique
    Serveur TFTP/Configurer le module\">(*)</span><br />";
                                echo "Pour cloner (<em>par exemple</em>) les partitions sda1, sda2 et sda5, choisir <strong><a href=\"javascript:affecter_valeur_disk('sda1_sda2_sda5')\" title=\"Cliquez pour prendre cette valeur.\">sda1_sda2_sda5</a></strong> (<em>taper le nom des partitions sépar&eacute;es d'un tiret bas _</em>).<br />Avec le choix ntfsclone+udpcast, les partitions ntfs sont clon&eacute;es avec ntfsclone+udpcast et les autres partitions sont clon&eacute;es avec udpcast seul.";
                                echo "</td></tr>\n";
                                   
                                echo "<tr><td valign='top'>Compression: </td><td>";
                                echo "<label for='compr_none'><input type='radio' name='compr' id='compr_none' value='none' ";
                                if($pref_clonage_compression=='none') {echo "checked ";}
                                echo "/> Aucune compression (<i> deconseille sauf SSD et gigabit </i>)</label><br />\n";
                                echo "<label for='compr_gzip'><input type='radio' name='compr' id='compr_gzip' value='gzip' ";
                                if($pref_clonage_compression=='gzip') {echo "checked ";}
                                echo "/> Compression GZIP (<i>2 processeurs mini, 100M</i>)</label><br />\n";
                                echo "<label for='compr_pbzip2'><input type='radio' name='compr' id='compr_pbzip2' value='pbzip2' ";
                                if($pref_clonage_compression=='pbzip2') {echo "checked ";}
                                echo "/> Compression BZ2 parallele (<i>4 processeurs mini, ou reseau lent</i>)</label><br />\n";
                                echo "<label for='compr_lzop'><input type='radio' name='compr' id='compr_lzop' value='lzop' ";
                                if($pref_clonage_compression=='lzop') {echo "checked ";}
                                echo "/> Compression LZOP (<i>1 processeur ou gigabit</i>)</label><br />\n";
				echo "</td></tr>\n";

				// A FAIRE: Relever les clonage en attente (possible) ou en cours (pas possible en l'état) pour ne pas proposer le même port...
				$sql="SELECT DISTINCT infos FROM se3_tftp_action WHERE type='udpcast_emetteur';";
				$res_infos=mysql_query($sql);
				if(mysql_num_rows($res_infos)>0) {
					$tab_ports=array();
					while($lig_infos=mysql_fetch_object($res_infos)){
						$tmp_tab=decoupe_infos($lig_infos->infos);
						if(isset($tmp_tab['port'])) {$tab_ports[]=$tmp_tab['port'];}
					}
					//sort($tab_ports);
					for($i=9002;$i<64000;$i+=2) {
						if(!in_array($i,$tab_ports)){$port=$i;break;}
					}
				}
				else {
					$port=9002;
				}
				echo "<tr><td valign='top'>Port: </td><td><input type='text' name='port' value='$port' size='5' />";
				echo "<br />\n";
				//echo "<b>Attention</b>: A l'heure actuelle, aucun test n'est fait sur les clonages programmés concernant le port utilisé.<br />Il ne faut pas qu'un autre clonage se déroule simultanément avec le même port.<br /><i>Remarque</i>: Le port doit être pair.\n";
				echo "<b>Attention</b>: Aucun test n'est réalisé sur les clonages en cours concernant le port utilisé.<br />Seuls sont testés les clonages programmés, mais non encore amorcés.<br />Il ne faut pas qu'un autre clonage se déroule simultanément avec le même port.<br /><i>Remarque</i>: Le port doit être pair.\n";
				echo "</td></tr>\n";

				echo "<tr id='tr_module_disk'><td>Module Disk: </td><td>\n";
				echo "<input type='checkbox' name='enableDiskmodule' value='yes' checked /> \n";
				$tab_pilote=array();
				$chaine_pilote="";
				$sql="SELECT valeur FROM se3_tftp_infos WHERE id='$id_emetteur' AND nom='storage_driver';";
				$test_driver_emetteur=mysql_query($sql);
				if(mysql_num_rows($test_driver_emetteur)>0) {
					$chaine_pilote.="<br />\n";
					$chaine_pilote.="<b>Pilote(s) relevé(s) lors d'un précédent rapport&nbsp;:</b> ";
					$cpt_pilote=0;
					while($lig_pilote=mysql_fetch_object($test_driver_emetteur)) {
						if($cpt_pilote>0) {$chaine_pilote.=", ";}
						$chaine_pilote.=$lig_pilote->valeur;
						$tab_pilote[]=strtolower($lig_pilote->valeur);
						$cpt_pilote++;
					}
				}

				echo "<select name='diskmodule'>\n";
				echo "<option value='AUTO' selected='true'>Auto-detect</option>\n";
				for($i=0;$i<count($tab_diskmodule);$i++){
					echo "<option value='$tab_diskmodule[$i]'";
					if(in_array(strtolower($tab_diskmodule[$i]),$tab_pilote)) {echo " selected='true'";}
					echo ">$tab_diskmodule[$i]</option>\n";
				}
				echo "</select>\n";
				echo $chaine_pilote;
				echo "</td></tr>\n";

				echo "<tr id='tr_netmodule'><td>Pilote réseau: </td><td>\n";
				$tab_pilote=array();
				$chaine_pilote="";
				$sql="SELECT valeur FROM se3_tftp_infos WHERE id='$id_emetteur' AND nom='network_driver';";
				$test_driver_emetteur=mysql_query($sql);
				if(mysql_num_rows($test_driver_emetteur)>0) {
					$chaine_pilote.="<br />\n";
					$chaine_pilote.="<b>Pilote(s) relevé(s) lors d'un précédent rapport&nbsp;:</b> ";
					$cpt_pilote=0;
					while($lig_pilote=mysql_fetch_object($test_driver_emetteur)) {
						if($cpt_pilote>0) {$chaine_pilote.=", ";}
						$chaine_pilote.=$lig_pilote->valeur;
						$tab_pilote[]=strtolower($lig_pilote->valeur);
						$cpt_pilote++;
					}
				}

				echo "<select name='netmodule'>\n";
				echo "<option value='AUTO' selected='true'>Auto-detect</option>\n";
				for($i=0;$i<count($tab_diskmodule);$i++){
					echo "<option value='$tab_netmodule[$i]'";
					if(in_array(strtolower($tab_diskmodule[$i]),$tab_pilote)) {echo " selected='true'";}
					echo ">$tab_netmodule[$i]</option>\n";
				}
				echo "</select>\n";
				echo $chaine_pilote;
				echo "</td></tr>\n";

				echo "<tr><td valign='top'>DHCP: </td><td>\n";
				echo "<input type='checkbox' name='dhcp' value='no' /> Ne pas prendre l'IP via DHCP mais directement depuis le /tftpboot/pxelinux.cfg/01-&lt;MAC&gt;\n";
				echo "</td></tr>\n";

				if(($temoin_sysresccd=="y")&&(crob_getParam('srcd_scripts_vers')>='20110910')) {
					echo "<tr id='tr_authorized_keys'>\n";
					echo "<td>Url authorized_keys&nbsp;: </td>\n";
					echo "<td><input type='checkbox' name='prendre_en_compte_url_authorized_keys' value='y' /> \n";
					echo "<input type='text' name='url_authorized_keys' value='".crob_getParam('url_authorized_keys')."' size='40' />\n";
					echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Un fichier authorized_keys peut &ecirc;tre mis en place pour permettre un acc&egrave;s SSH aux postes clon&eacute;s.')")."\">\n";
					echo "<img name=\"action_image3\"  src=\"../elements/images/help-info.gif\"></u>\n";
					echo "</td>\n";
					echo "</tr>\n";
				}

				echo "<tr><td valign='top'>Rebooter en fin de clonage: </td>\n";
				echo "<td>\n";
				echo "<label for='auto_reboot_always'><input type='radio' name='auto_reboot' id='auto_reboot_always' value='always' checked /> Toujours</label><br />\n";
				echo "<label for='auto_reboot_success'><input type='radio' name='auto_reboot' id='auto_reboot_success' value='success' /> En cas de succès</label><br />\n";
				echo "<label for='auto_reboot_never'><input type='radio' name='auto_reboot' id='auto_reboot_never' value='never' /> Jamais</label>\n";
				echo "</td></tr>\n";

				echo "<tr><td valign='top'>Pour la ou les machines sélectionnées: </td>\n";
				echo "<td>\n";

					echo "<table border='0'>\n";
					echo "<tr><td valign='top'><input type='checkbox' id='wake' name='wake' value='y' checked /> </td><td><label for='wake'>Démarrer les machines par Wake-On-Lan/etherwake si elles sont éteintes.</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_wait1' name='shutdown_reboot' value='wait1'  /> </td><td><label for='shutdown_reboot_wait1'>Attendre le reboot des machines même si aucune session n'est ouverte,</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_wait2' name='shutdown_reboot' value='wait2'  /> </td><td><label for='shutdown_reboot_wait2'>Redémarrer les machines sans session ouverte et attendre le reboot pour les machines qui ont des sessions ouvertes,</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_reboot' name='shutdown_reboot' value='reboot' checked /> </td><td><label for='shutdown_reboot_reboot'>Redémarrer les machines même si une session est ouverte (<i>pô cool</i>).</label></td></tr>\n";
					echo "</table>\n";

				echo "</td></tr>\n";


				echo "<tr><td colspan='2' style='background-color: silver;'>Paramètres spécifiques à l'émetteur</td></tr>\n";

				echo "<tr><td valign='top'>Nombre (<i>min</i>) de clients à attendre: </td>\n";
				echo "<td>\n";
				echo "<input type='text' name='min_receivers' id='min_receivers' value='".count($id_recepteur)."' size='3' onkeydown=\"clavier_up_down_increment('min_receivers',event,1,100);\" autocomplete=\"off\" />\n";
				echo "<br />\n";
				echo "Vous pouvez par exemple annoncer 10 récepteurs minimum alors que vous souhaitez en cloner 12.<br />";
				echo "Dans ce cas, vous acceptez que deux récepteurs manquent dans le clonage, mais pas plus.<br />";
				echo "<br />\n";
				echo "Lorsque le compte est atteint, le clonage démarre aussitôt le délais ci-dessous écoulé.<br />";
				echo "</td></tr>\n";
				/*
				echo "<tr><td valign='top'><b>Ou</b></td><td>(<i>mettre 0 ou vide pour l'option à ne pas retenir;<br />si aucun des deux champs n'est vidé l'option ci-dessus l'emporte</i>)</td></tr>\n";
				*/
				echo "<tr><td valign='top'>Délais minimum avant le démarrage:</td>\n";
				echo "<td valign='bottom'>\n";
				echo "<input type='text' id='min_wait' name='min_wait' value='$pref_clonage_min_wait' size='3' onkeydown=\"clavier_up_down_increment('min_wait',event,1,60);\" autocomplete=\"off\" /> minutes.\n";
				echo "<br />\n";
				echo "Si vous fixez un nombre de récepteurs inférieur au nombre max de clients pouvant être clonés, ce délais permettra d'attendre les récepteurs au-delà pendant cette durée.\n";
				echo "</td></tr>\n";

				echo "<tr><td valign='top'>Si un ou des clients<br />font défaut,<br />démarrer après: </td>\n";
				echo "<td valign='bottom'>\n";
				echo "<input type='text' id='max_wait' name='max_wait' value='$pref_clonage_max_wait' size='4' onkeydown=\"clavier_up_down_increment('max_wait',event,1,60);\" autocomplete=\"off\" /> minutes.\n";
				echo "<br />\n";
				echo "Néanmoins, le clonage ne démarre que si un client au moins est présent.\n";
				echo "</td></tr>\n";

				echo "<tr><td colspan='2' style='background-color: silver;'>Paramètres spécifiques au(x) récepteur(s)</td></tr>\n";

				echo "<tr><td valign='top'>Abandonner après: </td>\n";
				echo "<td>\n";
				//echo "<input type='text' id='start_timeout' name='start_timeout' value='20' size='3' onkeydown=\"clavier_up_down_increment(this.id,event,1,60);\" autocomplete=\"off\" /> minutes si le clonage ne démarre pas.\n";
				echo "<input type='text' id='start_timeout' name='start_timeout' value='$pref_clonage_start_timeout' size='3' onkeydown=\"clavier_up_down_increment('start_timeout',event,1,60);\" autocomplete=\"off\" /> minutes si le clonage ne démarre pas.\n";
				echo "<br />\n";
				echo "Veillez à ce que le timeout soit supérieur à la valeur 'max-wait' spécifiée pour l'émetteur.\n";
				echo "</td></tr>\n";

				/*
				# --start-timeout sec
				#    receiver aborts at start if it doesn't see a sender within this many seconds. Furthermore, the sender needs to start transmission of data within this delay. Once transmission is started, the timeout no longer applies.
				*/


				echo "</table>\n";

				echo "<input type='hidden' name='validation_parametres' value='y' />\n";
				echo "<noscript><p align='center'><input type=\"submit\" name=\"bouton_submit_validation_parametres\" value=\"Valider 2\" /></p></noscript>\n";
				echo "<p align='center'><input type=\"button\" name=\"bouton_validation_parametres\" value=\"Valider\" onclick=\"verif_et_valide_form()\" /></p>\n";

				echo "<p id='p_message_shutdown_cmd' align='center' style='color:red;'> Attention ! si l'emetteur ne reboote pas tout seul en administrateur local, ouvrez une session administrateur local et lancez c:\\netinst\\shutdown.cmd </p>\n"; 
				echo "</form>\n";


echo "<script type='text/javascript'>
function affecter_valeur_disk(valeur) {
	if(document.getElementById('disk')) {
		document.getElementById('disk').value=valeur;
	}
}

function verif_et_valide_form() {
	if(eval(document.getElementById('min_wait').value)>eval(document.getElementById('max_wait').value)) {
		alert('La valeur minimale d attente de l emetteur ne devrait pas etre inferieure a la valeur maximale d attente.')
	}
	else {
		//alert('max_wait='+document.getElementById('max_wait').value+' et start_timeout='+document.getElementById('start_timeout').value)
		if(eval(document.getElementById('start_timeout').value)<eval(document.getElementById('max_wait').value)) {
			alert('La valeur max d attente de l emetteur ne devrait pas etre superieure a la valeur maximale d attente des recepteurs.')
		}
		else {
			document.form_param.submit();
		}
	}
}

function affiche_sections_distrib() {
	if(document.getElementById('distrib_sysresccd').checked==true) {
		distrib='sysresccd';
	}
	else {
		distrib='udpcast';
	}
	//alert(distrib);

	if(distrib=='udpcast') {
		document.getElementById('div_sysresccd_kernel').style.display='none';
		document.getElementById('tr_module_disk').style.display='';
		document.getElementById('tr_netmodule').style.display='';

		document.getElementById('div_ntfsclone').style.display='none';
		document.getElementById('tr_authorized_keys').style.display='none';
	}
	else {
		document.getElementById('div_sysresccd_kernel').style.display='';
		document.getElementById('tr_module_disk').style.display='none';
		document.getElementById('tr_netmodule').style.display='none';

		document.getElementById('div_ntfsclone').style.display='';
		document.getElementById('tr_authorized_keys').style.display='';
	}
}

affiche_sections_distrib();

function affiche_message_shutdown_cmd() {
	if(document.getElementById('type_os_xp').checked==true) {
		document.getElementById('p_message_shutdown_cmd').style.display='';
	}
	else {
		document.getElementById('p_message_shutdown_cmd').style.display='none';
	}
}

affiche_message_shutdown_cmd();

function clavier_up_down_increment(n,e,vmin,vmax){
	//alert(n);
	// Fonction destinée à incrémenter/décrémenter le champ courant entre 0 et 255 (pour des composantes de couleurs)
	// Modifié pour aller de vmin à vmax
	touche= e.keyCode ;
	//alert('touche='+touche);
	if (touche == '40') {
		valeur=document.getElementById(n).value;
		if(valeur>vmin){
			valeur--;
			document.getElementById(n).value=valeur;
		}
	}
	else{
		if (touche == '38') {
			valeur=document.getElementById(n).value;
			if(valeur<vmax){
				valeur++;
				document.getElementById(n).value=valeur;
			}
		}
		else{
			if(touche == '34'){
				valeur=document.getElementById(n).value;
				if(valeur>vmin+10){
					valeur=valeur-10;
				}
				else{
					valeur=vmin;
				}
				document.getElementById(n).value=valeur;
			}
			else{
				if(touche == '33'){
					valeur=document.getElementById(n).value;
					if(valeur<vmax-10){
						//valeur=valeur+10;
						//valeur+=10;
						valeur=eval(valeur)+10;
					}
					else{
						valeur=vmax;
					}
					document.getElementById(n).value=valeur;
				}
			}
		}
	}
}

</script>\n";

			}
			else {
				echo "<h2>Validation des paramètres du clonage</h2>\n";

				$opt_url_authorized_keys="";
				if((isset($_POST['prendre_en_compte_url_authorized_keys']))&&(isset($_POST['url_authorized_keys']))&&($_POST['url_authorized_keys']!='')&&(preg_replace('|[A-Za-z0-9/:_\.\-]|','',$_POST['url_authorized_keys'])=='')) {
					$opt_url_authorized_keys="url_authorized_keys=".$_POST['url_authorized_keys'];
					crob_setParam('url_authorized_keys',$_POST['url_authorized_keys'],'Url fichier authorized_keys pour acces ssh aux clients TFTP');
				}

				//===================================
				// Contrôle des variables:
				$tab_compr=array('none','gzip','lzop','pbzip2');
				if(!in_array($compr,$tab_compr)){$compr='lzop';}

				if((strlen(preg_replace("/[0-9]/","",$port)!=0))||($port=='')) {$port=9002;}

				if(!isset($enableDiskmodule)){
					$enableDiskmodule="no";
				}
				elseif($enableDiskmodule!="yes"){
					$enableDiskmodule="no";
				}

				if(($auto_reboot!="always")&&($auto_reboot!="success")){$auto_reboot="never";}
				//===================================

				$sauvegarde_pref=crob_setParam('pref_distrib_clonage', $distrib, 'Distrib preferee pour le clonage');
				$sauvegarde_pref=crob_setParam('pref_ntfsclone_udpcast', $ntfsclone_udpcast, 'Utilisation de ntfsclone+udpcast pour le clonage');
				$sauvegarde_pref=crob_setParam('pref_clonage_max_wait', $max_wait, 'Clonage: Valeur max d attente du serveur');
				$sauvegarde_pref=crob_setParam('pref_clonage_min_wait', $min_wait, 'Clonage: Valeur min d attente du serveur');
				$sauvegarde_pref=crob_setParam('pref_clonage_start_timeout', $start_timeout, 'Clonage: Valeur max d attente des recepteurs');
				$sauvegarde_pref=crob_setParam('pref_clonage_compression', $compr, 'Clonage: Mode de compression prefere');

				echo "<p>Rappel des paramètres:</p>\n";

				$temoin_sysresccd=check_sysresccd_files();

				if($temoin_sysresccd=="y") {
					echo "<table class='crob'>\n";
					echo "<tr>\n";
					echo "<th style='text-align:left;'>Distribution linux à utiliser: </th>\n";
					echo "<td>\n";
					echo $distrib;
					if($distrib=='sysresccd') {
						echo " (<i>noyau $sysresccd_kernel</i>)";
					}
					echo "<input type=\"hidden\" name=\"distrib\" value=\"$distrib\" />\n";
					echo "</td>\n";
					echo "</tr>\n";
				}
				else {
					echo "<input type=\"hidden\" name=\"distrib\" value=\"udpcast\" />\n";
					echo "<table class='crob'>\n";
				}

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Partition/disque à cloner: </th>\n";
				echo "<td>\n";
				echo $disk;
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Compression: </th>\n";
				echo "<td>\n";
				echo $compr;
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Port: </th>\n";
				echo "<td>\n";
				echo $port;
				echo "</td>\n";
				echo "</tr>\n";

				if($distrib=='udpcast') {
					echo "<tr>\n";
					echo "<th style='text-align:left;'>Charger un module disque: </th>\n";
					echo "<td>\n";
					echo $enableDiskmodule;
					echo "</td>\n";
					echo "</tr>\n";
	
					if($enableDiskmodule=="yes") {
						echo "<tr>\n";
						echo "<th style='text-align:left;'>Module disque: </th>\n";
						echo "<td>\n";
						echo $diskmodule;
						echo "</td>\n";
						echo "</tr>\n";
					}
	
					echo "<tr>\n";
					echo "<th style='text-align:left;'>Module réseau: </th>\n";
					echo "<td>\n";
					echo $netmodule;
					echo "</td>\n";
					echo "</tr>\n";
				}

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Port: </th>\n";
				echo "<td>\n";
				echo $port;
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Rebooter en fin de restauration: </th>\n";
				echo "<td>\n";
				if($auto_reboot=="always") {
					echo "Toujours";
				}
				elseif($auto_reboot=="success") {
					echo "En cas de succès";
				}
				elseif($auto_reboot=="never") {
					echo "Jamais (<i>reboot manuel</i>)";
				}
				echo "</td>\n";
				echo "</tr>\n";

				echo "</table>\n";


				//=============================
				// On recupere l'interface reseau pour pouvoir par la suite trouver le masque de sous-reseau depuis le script pxe_gen_cfg.sh
				$sql="SELECT * FROM params WHERE name='dhcp_iface';";
				$res=mysql_query($sql);
				if(mysql_num_rows($res)>0) {
					$lig=mysql_fetch_object($res);
					//echo "dhcp_iface=".$lig->value."<br />";
					$dhcp_iface=$lig->value;
				}

				$dhcp=isset($_POST['dhcp']) ? $_POST['dhcp'] : "yes";
				//=============================



				//====================================================
				$sql="SELECT * FROM se3_dhcp WHERE id='$id_emetteur';";
				$res=mysql_query($sql);
				$lig=mysql_fetch_object($res);
				//echo "<p>Emetteur: $lig->name (<i>$id_emetteur</i>)</p>\n";

				/*
				//$udpcparam="--max-wait=".$max_wait."--min-receivers=".$min_receivers;
				$sec_max_wait=$max_wait*60;
				//$udpcparam="--max-wait=".$sec_max_wait."--min-receivers=".$min_receivers;
				$udpcparam="--max-wait=".$sec_max_wait."|--min-receivers=".$min_receivers;

				// Je ne parviens pas à renseigner correctement le /udpcfg.txt avec les infos passées par la boot_cmdline
				// Je ne m'en sors qu'avec un seul paramètre en udpcparam
				if(($min_receivers!=0)&&($min_receivers!='')) {
					$udpcparam="--min-receivers=".$min_receivers;
				}
				elseif(($max_wait!=0)&&($max_wait!='')) {
					$sec_max_wait=$max_wait*60;
					$udpcparam="--max-wait=".$sec_max_wait;
				}
				*/

				$sec_max_wait=$max_wait*60;
				$sec_min_wait=$min_wait*60;
				$udpcparam="--max-wait=".$sec_max_wait." --min-wait=".$sec_min_wait." --min-receivers=".$min_receivers;
				$udpcparam_temp=strtr($udpcparam," ","_"); // Pour passer la récupération de variable dans pxe_gen_cfg.sh, l'espace dans le contenu de la variable pose un pb. On remplace par un _ et on fait la correction inverse dans pxe_gen_cfg.sh

				$mac_machine=$lig->mac;
				$nom_machine=$lig->name;
				$ip_machine=$lig->ip;
				$corrige_mac=strtolower(strtr($mac_machine,":","-"));


				if($restriction_parcs=="y") {
					$temoin_erreur='y';
					for($loop=0; $loop<count($tab_delegated_parcs);$loop++) {
						// La machine est-elle dans un des parcs délégués?
						if(is_machine_in_parc($nom_machine,$tab_delegated_parcs[$loop])) {$temoin_erreur='n';break;}
					}
				}

				if($temoin_erreur=="y") {
					echo "<p style='color:red'>La machine $nom_machine ne vous est pas déléguée</p>\n";
				}
				else {

					// Nettoyage de scories d'autres programmations:
					if(file_exists("/tftpboot/pxelinux.cfg/01-".$corrige_mac)) {
						echo "<p><span style='color:red;'>Suppression d'une programmation précédente pour $nom_machine</span><br>\n";
						//unlink("/tftpboot/pxelinux.cfg/01-".$corrige_mac);

						$resultat.=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'menage_tftpboot_pxelinux_cfg' 'mac=$corrige_mac'", $retour);
						if(count($retour)>0){
							for($j=0;$j<count($retour);$j++){
								echo "$retour[$j]<br />\n";
							}
						}
						echo "</p>\n";
					}

					for($i=0;$i<count($id_recepteur);$i++) {
						$sql="SELECT * FROM se3_dhcp WHERE id='".$id_recepteur[$i]."';";
						//echo "$sql<br />";
						$res_client=mysql_query($sql);
						if(mysql_num_rows($res_client)==0) {
							echo "<p>";
							echo "<span style='color:red;'>La machine d'identifiant $id_recepteur[$i] n'existe pas dans 'se3_dhcp'.</span><br />\n";
							echo "</p>\n";
						}
						else {
							$temoin_erreur_client="n";

							$lig_client=mysql_fetch_object($res_client);
							$mac_machine_client=$lig_client->mac;
							$nom_machine_client=$lig_client->name;
							$ip_machine_client=$lig_client->ip;

							if($restriction_parcs=="y") {
								$temoin_erreur_client='y';
								for($loop=0; $loop<count($tab_delegated_parcs);$loop++) {
									// La machine est-elle dans un des parcs délégués?
									if(is_machine_in_parc($nom_machine_client,$tab_delegated_parcs[$loop])) {$temoin_erreur='n';break;}
								}
							}

							if($temoin_erreur_client=="y") {
								echo "<p style='color:red'>La machine $nom_machine_client ne vous est pas déléguée</p>\n";
							}
							else {

								$corrige_mac_client=strtolower(strtr($mac_machine_client,":","-"));
								if(file_exists("/tftpboot/pxelinux.cfg/01-".$corrige_mac_client)) {
									echo "<p><span style='color:red;'>Suppression d'une programmation précédente pour $nom_machine_client</span><br>\n";
									//unlink("/tftpboot/pxelinux.cfg/01-".$corrige_mac);

									$resultat.=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'menage_tftpboot_pxelinux_cfg' 'mac=$corrige_mac_client'", $retour);
									if(count($retour)>0){
										for($j=0;$j<count($retour);$j++){
											echo "$retour[$j]<br />\n";
										}
									}
									echo "</p>\n";
								}
							}
						}
					}

					flush();

					$temoin_erreur="n";
	
					$num_op=get_free_se3_action_tftp_num_op();
	
					$id_microtime=preg_replace('/[^0-9]/','_',microtime());
	
					$chemin="/usr/share/se3/scripts";
	
					if($type_os=='xp') {
						$duree = 40;
						echo "<p><span style='color:red; font-weight:bold;'>Rappel&nbsp;:</span> Il faut que les postes émetteur et récepteur(s) bootent en priorité sur le réseau (<em>PXE</em>) pour que le redémarrage se fasse sur ".$distrib." et que le clonage s'ensuive.</p>\n";

						echo "<p><span style='font-weight:bold;'>Informations sur la suite&nbsp;:</span> Le poste émetteur va être sorti du domaine, renommé de façon aléatoire et préparé pour une réintégration après clonage,...<br>\n";
						echo "L'opération prend de 5 jusqu'à $duree minutes avant que la préparation sysprep  soit effectuée et que la fin de la présente page HTML s'affiche.<br/>\n";
						echo "Soyez patient...</p>\n";

						flush();

						// on lance la preparation du poste emetteur
						$resultat=system("/usr/bin/sudo /usr/share/se3/scripts/se3sysprep.sh clone $nom_machine $ip_machine adminse3 $xppass 2>&1", $retint);
		
						if ($retint) {
							echo "<span style='color:red;'>ECHEC de la preparation du poste</span><br>\n";
							$temoin_erreur="y";
						} else {
							echo "on attend le rapport de fin de la preparation<br>";
		
							$sql="SELECT COUNT(*) FROM se3_tftp_rapports WHERE id='$id_emetteur' AND tache='preparation' AND statut='SUCCES' AND date>(now()-100);";
							$num=0;
							$incr=0;
							while ($num==0) { 
								$count=mysql_query($sql);
								$num=mysql_result($count, 0);
								$reste=$duree-$incr;
								echo "on attend encore $reste minutes <br>";
								flush();
								sleep(60);
								if ($incr++==$duree) { 
									echo "<br>Probleme : pas de rapport remonte pour la preparation du clonage. Si le poste emetteur n'a pas reboote en administrateur local, relancez le clonage, connectez vous en administrateur local et lancez netinst\\shutdown.cmd";
									$temoin_erreur="y";
									break;
								}
							}
		
							if ("$temoin_erreur"=="n"){
								echo "<br> preparation reussie <br>";
							}
						}
					}
	
	
					if($distrib=='slitaz') {
						$ajout_kernel="";
					}
					else {
						$ajout_kernel="|kernel=$sysresccd_kernel";
					}
				}

				if ("$temoin_erreur"=="n") {
					echo "<p>Génération des fichiers dans /tftpboot/pxelinux.cfg/ pour l'émetteur.<br />\n";
					echo "<p>Emetteur: $lig->name (<i>$id_emetteur</i>): \n";
					if($distrib=='udpcast') {
						//$resultat.=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'udpcast_emetteur' '$corrige_mac' '$ip_machine' '$nom_machine' '$compr' '$port' '$enableDiskmodule' '$diskmodule' '$netmodule' '$disk' '$auto_reboot' '$udpcparam' '$urlse3' '$num_op' '$dhcp' '$dhcp_iface'", $retour);
						$resultat.=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'udpcrlse3 num_op=$num_op dhcp=$dhcp dhcp_iface=$dhcp_iface'", $retour);
					}
					else {
						//$resultat.=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_udpcast_emetteur' '$corrige_mac' '$ip_machine' '$nom_machine' '$compr' '$port' '$enableDiskmodule' '$diskmodule' '$netmodule' '$disk' '$auto_reboot' '$udpcparam' '$urlse3' '$num_op' '$dhcp' '$dhcp_iface'", $retour);
						if($ntfsclone_udpcast=='y') {
							$resultat.=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_ntfsclone_udpcast_emetteur' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine compr=$compr port=$port enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule disk=$disk auto_reboot=$auto_reboot udpcparam=$udpcparam_temp urlse3=$urlse3 num_op=$num_op dhcp=$dhcp dhcp_iface=$dhcp_iface kernel=$sysresccd_kernel id_microtime=$id_microtime $opt_url_authorized_keys'", $retour);
						}
						else {
							$resultat.=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_udpcast_emetteur' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine compr=$compr port=$port enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule disk=$disk auto_reboot=$auto_reboot udpcparam=$udpcparam_temp urlse3=$urlse3 num_op=$num_op dhcp=$dhcp dhcp_iface=$dhcp_iface kernel=$sysresccd_kernel $opt_url_authorized_keys'", $retour);
						}
					}

					if(count($retour)>0){
						echo "<span style='color:red;'>ECHEC de la génération du fichier</span><br />\n";
						for($j=0;$j<count($retour);$j++){
							echo "$retour[$j]<br />\n";
						}
						$temoin_erreur="y";
					}
					else {
						// Numéro de l'opération de sauvegarde:
						//$num_op=get_free_se3_action_tftp_num_op();
						$sql="UPDATE se3_tftp_rapports SET statut='VALIDE' WHERE id='$id_emetteur' AND tache='preparation' AND statut='SUCCES';";
						$upd=mysql_query($sql); 
						$sql="DELETE FROM se3_tftp_action WHERE id='$id_emetteur';";
						$suppr=mysql_query($sql);

						$timestamp=time();
						$sql="INSERT INTO se3_tftp_action SET id='$id_emetteur',
																mac='$mac_machine',
																name='$nom_machine',
																date='$timestamp',
																type='udpcast_emetteur',
																num_op='$num_op',
																infos='compr=$compr|disk=$disk|port=$port|enableDiskmodule=$enableDiskmodule|diskmodule=$diskmodule|netmodule=$netmodule|auto_reboot=$auto_reboot|udpcparam=${udpcparam}${ajout_kernel}';";
						$insert=mysql_query($sql);
						if(!$insert) {
							echo "<span style='color:red;'>ECHEC de l'enregistrement dans 'se3_tftp_action'</span><br />\n";
							$temoin_erreur="y";
						}

						if($temoin_erreur=="n") {
							echo "<span style='color:green;'>OK</span>\n";
							// Application de l'action choisie:
							echo " <span id='wake_shutdown_or_reboot_emetteur'></span>";

							echo "<script type='text/javascript'>
								// <![CDATA[
								new Ajax.Updater($('wake_shutdown_or_reboot_emetteur'),'ajax_lib.php?ip=$ip_machine&nom=$nom_machine&mode=wake_shutdown_or_reboot&wake=$wake&shutdown_reboot=$shutdown_reboot',{method: 'get'});
								//]]>
							</script>\n";

							echo "<br />\n";
						}
					}
				}
				//====================================================


				if($temoin_erreur=="y") {
					echo "<p>La mise en place a échoué pour l'emetteur.<br />On abandonne avant de générer les fichiers pour les émetteurs.</p>\n et on retablit la configuration initiale";
					system("/usr/bin/sudo /usr/share/se3/scripts/se3sysprep.sh ldap $nom_machine $ip_machine $mac_machine 2>&1");
					include ("pdp.inc.php");
					exit();
				}

				// On n'affiche le fichier que pour le dernier (à titre d'info):
				if(isset($corrige_mac)) {
					//$fich=fopen("/tftpboot/pxelinux.cfg/01-$lig1->mac","r");
					$fich=fopen("/tftpboot/pxelinux.cfg/01-$corrige_mac","r");
					if($fich) {
						echo "<p>Pour information, voici le contenu du fichier généré:<br />\n";
						echo "<pre style='border:1px solid black; color:green;'>";
						while(!feof($fich)) {
							$ligne=fgets($fich,4096);
							echo htmlentities($ligne);
						}
						echo "</pre>\n";
						fclose($fich);
					}
					else {
						echo "<p style='color:red;'>Il n'a pas été possible d'ouvrir le fichier /tftpboot/pxelinux.cfg/01-$corrige_mac</p>\n";
					}
				}

				//====================================================

				echo "<p>Génération des fichiers dans /tftpboot/pxelinux.cfg/ pour les récepteurs.<br />\n";
				//$udpcparam="--start-timeout=".$start_timeout;
				$sec_start_timeout=$start_timeout*60;
				//$udpcparam="--start-timeout=".$sec_start_timeout;

				//$sec_max_wait=$max_wait*60;
				//$udpcparam="--start-timeout=".$sec_start_timeout." --max-wait=".$sec_max_wait." --min-wait=".$sec_min_wait;
				$udpcparam="--start-timeout=".$sec_start_timeout;
				$udpcparam_temp=strtr($udpcparam," ","_"); // Pour passer la récupération de variable dans pxe_gen_cfg.sh, l'espace dans le contenu de la variable pose un pb. On remplace par un _ et on fait la correction inverse dans pxe_gen_cfg.sh

				// BOUCLE SUR LA LISTE DES $id_recepteur[$i]

				for($i=0;$i<count($id_recepteur);$i++) {
					$sql="SELECT * FROM se3_dhcp WHERE id='".$id_recepteur[$i]."';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)==0) {
						echo "<span style='color:red;'>La machine d'identifiant $id_recepteur[$i] n'existe pas dans 'se3_dhcp'.</span><br />\n";
					}
					else {
						$temoin_erreur="n";

						$lig=mysql_fetch_object($res);
						$mac_machine=$lig->mac;
						$nom_machine=$lig->name;
						$ip_machine=$lig->ip;

						if($restriction_parcs=="y") {
							$temoin_erreur='y';
							for($loop=0; $loop<count($tab_delegated_parcs);$loop++) {
								// La machine est-elle dans un des parcs délégués?
								if(is_machine_in_parc($nom_machine,$tab_delegated_parcs[$loop])) {$temoin_erreur='n';break;}
							}
						}

						if($temoin_erreur=="y") {
							echo "<p style='color:red'>La machine $nom_machine ne vous est pas déléguée</p>\n";
						}
						else {
							echo "Génération pour $nom_machine: ";
	
							$corrige_mac=strtolower(strtr($mac_machine,":","-"));
	
							$chemin="/usr/share/se3/scripts";
							//$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'udpcast_recepteur' '$corrige_mac' '$ip_machine' '$nom_machine' '$compr' '$port' '$enableDiskmodule' '$diskmodule' '$netmodule' '$disk' '$auto_reboot' '$udpcparam' '$urlse3' '$num_op'", $retour);
							if($distrib=='udpcast') {
								//$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'udpcast_recepteur' '$corrige_mac' '$ip_machine' '$nom_machine' '$compr' '$port' '$enableDiskmodule' '$diskmodule' '$netmodule' '$disk' '$auto_reboot' '$udpcparam' '$urlse3' '$num_op' '$dhcp' '$dhcp_iface'", $retour);
								$resultat.=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'udpcast_recepteur' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine compr=$compr port=$port enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule disk=$disk auto_reboot=$auto_reboot udpcparam=$udpcparam_temp urlse3=$urlse3 num_op=$num_op dhcp=$dhcp dhcp_iface=$dhcp_iface'", $retour);
							}
							else {
								//$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_udpcast_recepteur' '$corrige_mac' '$ip_machine' '$nom_machine' '$compr' '$port' '$enableDiskmodule' '$diskmodule' '$netmodule' '$disk' '$auto_reboot' '$udpcparam' '$urlse3' '$num_op' '$dhcp' '$dhcp_iface'", $retour);
								if($ntfsclone_udpcast=='y') {
									$resultat.=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_ntfsclone_udpcast_recepteur' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine compr=$compr port=$port enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule disk=$disk auto_reboot=$auto_reboot udpcparam=$udpcparam_temp urlse3=$urlse3 num_op=$num_op dhcp=$dhcp dhcp_iface=$dhcp_iface kernel=$sysresccd_kernel id_microtime=$id_microtime $opt_url_authorized_keys'", $retour);
								}
								else {
									$resultat.=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'sysresccd_udpcast_recepteur' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine compr=$compr port=$port enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule disk=$disk auto_reboot=$auto_reboot udpcparam=$udpcparam_temp urlse3=$urlse3 num_op=$num_op dhcp=$dhcp dhcp_iface=$dhcp_iface kernel=$sysresccd_kernel $opt_url_authorized_keys'", $retour);
								}
							}
	
							if(count($retour)>0){
								echo "<span style='color:red;'>ECHEC de la génération du fichier</span><br />\n";
								for($j=0;$j<count($retour);$j++){
									echo "$retour[$j]<br />\n";
								}
								$temoin_erreur="y";
							}
							else {
								$sql="DELETE FROM se3_tftp_action WHERE id='$id_recepteur[$i]';";
								$suppr=mysql_query($sql);
	
								$timestamp=time();
								$sql="INSERT INTO se3_tftp_action SET id='$id_recepteur[$i]',
																		mac='$mac_machine',
																		name='$nom_machine',
																		date='$timestamp',
																		type='udpcast_recepteur',
																		num_op='$num_op',
																		infos='compr=$compr|disk=$disk|port=$port|enableDiskmodule=$enableDiskmodule|diskmodule=$diskmodule|netmodule=$netmodule|auto_reboot=$auto_reboot|udpcparam=${udpcparam}${ajout_kernel}';";
								$insert=mysql_query($sql);
								if(!$insert) {
									echo "<span style='color:red;'>ECHEC de l'enregistrement dans 'se3_tftp_action'</span><br />\n";
									$temoin_erreur="y";
								}
	
								if($temoin_erreur=="n") {
									echo "<span style='color:green;'>OK</span>\n";
									// Application de l'action choisie:
									echo " <span id='wake_shutdown_or_reboot_$i'></span>";
	
									echo "<script type='text/javascript'>
										// <![CDATA[
										new Ajax.Updater($('wake_shutdown_or_reboot_$i'),'ajax_lib.php?ip=$ip_machine&nom=$nom_machine&mode=wake_shutdown_or_reboot&wake=$wake&shutdown_reboot=$shutdown_reboot',{method: 'get'});
										//]]>
									</script>\n";
	
									echo "<br />\n";
								}
							}
						}
					}
				}

				// +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
				// POUVOIR TAGUER DANS LA TABLE se3_dhcp LES MACHINES QUI PEUVENT BOOTER EN PXE
				// Ajouter un champ?
				// +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-

				// On n'affiche le fichier que pour le dernier (à titre d'info):
				if(isset($corrige_mac)) {
					$fich=fopen("/tftpboot/pxelinux.cfg/01-$corrige_mac","r");
					if($fich) {
						echo "<p>Pour information, voici le contenu du fichier généré:<br />\n";
						echo "<pre style='border:1px solid black; color:green;'>";
						while(!feof($fich)) {
							$ligne=fgets($fich,4096);
							echo htmlentities($ligne);
						}
						echo "</pre>\n";
						fclose($fich);
					}
					else {
						echo "<p style='color:red;'>Il n'a pas été possible d'ouvrir le fichier /tftpboot/pxelinux.cfg/01-$corrige_mac</p>\n";
					}
				}
			}
		}
		echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";
	}
}
else {
	print (gettext("Vous n'avez pas les droits nécessaires pour ouvrir cette page..."));
}

// Footer
include ("pdp.inc.php");
?>ast_emetteur' 'mac=$corrige_mac ip=$ip_machine pc=$nom_machine compr=$compr port=$port enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule disk=$disk auto_reboot=$auto_reboot udpcparam=$udpcparam_temp urlse3=$u
