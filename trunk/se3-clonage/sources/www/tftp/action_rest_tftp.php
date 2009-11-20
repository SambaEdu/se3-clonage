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
//require_once "../dhcp/dhcpd.inc.php";
include "printers.inc.php";

require("lib_action_tftp.php");

//aide
$_SESSION["pageaide"]="Le_module_Clonage_des_stations#Programmer_une_restauration";

// On active les rapports d'erreurs:
//error_reporting(E_ALL);

// Biblioth�que prototype Ajax pour afficher en d�cal� l'�tat des machines:
echo "<script type='text/javascript' src='../includes/prototype.js'></script>\n";

// CSS pour mes tableaux:
echo "<link type='text/css' rel='stylesheet' href='tftp.css' />\n";

if (is_admin("system_is_admin",$login)=="Y")
{
	// Choix des parcs:
	$parc=isset($_POST['parc']) ? $_POST['parc'] : (isset($_GET['parc']) ? $_GET['parc'] : NULL);
	// Choix des machines:
	$id_machine=isset($_POST['id_machine']) ? $_POST['id_machine'] : (isset($_GET['id_machine']) ? $_GET['id_machine'] : NULL);

	$parametrage_action=isset($_POST['parametrage_action']) ? $_POST['parametrage_action'] : (isset($_GET['parametrage_action']) ? $_GET['parametrage_action'] : NULL);

	/*
	// Cr�ation de la table d�s que possible:
	$sql="CREATE TABLE IF NOT EXISTS se3_tftp_action (
			id INT(11),
			mac VARCHAR(255),
			name VARCHAR(255),
			date INT(11),
			type VARCHAR(255),
			num_op INT(11)
			);";
	$creation_table=mysql_query($sql);
	*/
	creation_tftp_tables();

	// Param�tres SliTaz:
	$nom_image=isset($_POST['nom_image']) ? $_POST['nom_image'] : (isset($_GET['nom_image']) ? $_GET['nom_image'] : NULL);
	$src_part=isset($_POST['src_part']) ? $_POST['src_part'] : (isset($_GET['src_part']) ? $_GET['src_part'] : NULL);
	$dest_part=isset($_POST['dest_part']) ? $_POST['dest_part'] : (isset($_GET['dest_part']) ? $_GET['dest_part'] : NULL);
	$auto_reboot=isset($_POST['auto_reboot']) ? $_POST['auto_reboot'] : (isset($_GET['auto_reboot']) ? $_GET['auto_reboot'] : NULL);
	$delais_reboot=isset($_POST['delais_reboot']) ? $_POST['delais_reboot'] : (isset($_GET['delais_reboot']) ? $_GET['delais_reboot'] : NULL);

	// Param�tres concernant l'action imm�diate sur les machines choisies:
	$wake=isset($_POST['wake']) ? $_POST['wake'] : (isset($_GET['wake']) ? $_GET['wake'] : "n");
	$shutdown_reboot=isset($_POST['shutdown_reboot']) ? $_POST['shutdown_reboot'] : (isset($_GET['shutdown_reboot']) ? $_GET['shutdown_reboot'] : NULL);


	echo "<h1>".gettext("Action restauration TFTP")."</h1>\n";

	if(!isset($parc)){

		echo "<p>Choisissez un ou des parcs:</p>\n";

		$list_parcs=search_machines("objectclass=groupOfNames","parcs");
		if ( count($list_parcs)==0) {
			echo "<br><br>";
			echo gettext("Il n'existe aucun parc. Vous devez d'abord cr�er un parc");
			include ("pdp.inc.php");
			exit;
		}
		sort($list_parcs);

		echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";

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

			echo "<label for='parc_$loop'><input type='checkbox' id='parc_$loop' name='parc[]' value=\"".$list_parcs[$loop]["cn"]."\" />".$list_parcs[$loop]["cn"]."</label>\n";
			echo "<br />\n";
		}

		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<p align='center'><input type=\"submit\" name=\"submit\" value=\"Valider\" /></p>\n";

		echo "</form>\n";

		echo "<p><a href='index.php'>Retour � l'index</a>.</p>\n";
	}
	else {
		if(!isset($_POST['parametrage_action'])){

			echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
			echo "<input type=\"hidden\" name=\"parametrage_action\" value=\"1\" />\n";
			$max_eff_parc=0;
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

				//echo "<th>Restauration</th>\n";
				echo "<th>Restauration<br />\n";
				echo "<a href='#' onclick='check_machine($i,\"check\");return false'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Tout cocher\" title=\"Tout cocher\" /></a>\n";
				echo " / <a href='#' onclick='check_machine($i,\"uncheck\");return false'><img src=\"../elements/images/disabled.gif\" border='0' alt=\"Tout d�cocher\" title=\"Tout d�cocher\" /></a>\n";
				echo "</th>\n";
				echo "<th>Actions programm�es</th>\n";
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
						// R�initialisation:
						$id_machine="";

						echo "<tr>\n";
						echo "<td width='20%'>".$mp[$loop]."</td>\n";

						// Etat: allum� ou �teint
						echo "<td width='20%'>";
						$mp_curr=search_machines2("(&(cn=$mpenc)(objectClass=ipHost))","computers");
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


						// Session: ouverte ou pas... sous quelle identit�
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
						// mais dans la config DHCP parce que ce sont ces IP qui seront attribu�es lors du boot PXE
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
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse IP attribu�e\" title=\"Pas d'adresse IP attribu�e\" />";
							}
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse MAC dans l'annuaire???\" title=\"Pas d'adresse MAC dans l'annuaire???\" />";
						}
						echo "</td>\n";


						// S�lection des machines � sauvegarder:
						echo "<td width='20%'>\n";
						/*
						foreach($mp_curr[0] as $champ => $valeur) {
							echo "\$mp_curr[0]['$champ']=$valeur<br />";
						}
						*/
						if($id_machine!=""){
							echo "<input type='checkbox' name='id_machine[]' id='machine_".$i."_".$loop."' value='$id_machine' />\n";
						}
						else {
							echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Il faut commencer par effectuer la configuration DHCP\" title=\"Il faut commencer par effectuer la configuration DHCP\" />";
						}
						echo "</td>\n";


						// Action programm�e
						echo "<td>\n";
						if($id_machine!=""){
							$sql="SELECT * FROM se3_tftp_action WHERE id='".$id_machine."';";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								$lig=mysql_fetch_object($res);
								echo "<a href='visu_action.php?id_machine=$id_machine' target='_blank'>$lig->type programm�(e)</a>";
							}
							else {
								echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'action programm�e\" title=\"Pas d'action programm�e\" />";
							}
						}
						echo "</td>\n";

						echo "</tr>\n";
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
				echo "<h2>Param�trage de la restauration</h2>\n";

				$nombre_machines=count($id_machine);
				if($nombre_machines==0){
					echo "<p>ERREUR: Il faut choisir au moins une machine.</p>\n";

					echo "<p><a href='#' onclick='history.go(-1);'>Retour au choix des machines � restaurer</a>.</p>\n";

					echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";
					include ("pdp.inc.php");
					exit();
				}

				echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
				echo "<input type=\"hidden\" name=\"parametrage_action\" value=\"1\" />\n";
				// Liste des parcs:
				for($i=0;$i<count($parc);$i++){
					echo "<input type=\"hidden\" name=\"parc[]\" value=\"$parc[$i]\" />\n";
				}

				// Liste des machines sur lesquelles lancer la restauration:
				$chaine="";
				for($i=0;$i<count($id_machine);$i++){
					if($i>0) {$chaine.=", ";}
					$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine[$i]."';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)>0) {
						$lig=mysql_fetch_object($res);
						$chaine.=$lig->name;
						echo "<input type=\"hidden\" name=\"id_machine[]\" value=\"$id_machine[$i]\" />\n";
					}
				}
				if(count($id_machine)>1){$s="s";}else{$s="";}
				echo "<p>Machine$s concern�e$s: $chaine</p>\n";



				$aujourdhui = getdate();
				$mois_se3 = sprintf("%02d",$aujourdhui['mon']);
				$jour_se3 = sprintf("%02d",$aujourdhui['mday']);
				$annee_se3 = $aujourdhui['year'];
				$heure_se3 = sprintf("%02d",$aujourdhui['hours']);
				$minute_se3 = sprintf("%02d",$aujourdhui['minutes']);
				$seconde_se3 = sprintf("%02d",$aujourdhui['seconds']);

				$date_se3=$annee_se3.$mois_se3.$jour_se3;

				echo "<p>Choisissez les param�tres de restauration: <br />\n";


				echo "<table border='0'>\n";
				echo "<tr><td>Nom de l'image � restaurer: </td><td><input type='text' name='nom_image' value='' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Si vous laissez vide, la sauvegarde la plus r�cente r�ussie est utilis�e.')")."\"><img name=\"action_image1\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td></tr>\n";

				echo "<tr><td>Partition � restaurer: </td><td><input type='text' name='dest_part' value='auto' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Proposer hda1, sda1,... selon les cas, ou laissez \'auto\' si la premi�re partition du disque est bien la partition syst�me � restaurer.')")."\"><img name=\"action_image2\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td></tr>\n";

				echo "<tr><td>Partition de stockage de l'image: </td><td><input type='text' name='src_part' value='auto' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Proposer hda5, sda5,... selon les cas, ou laissez \'auto\' si la premi�re partition Linux (<i>ou � d�faut W$ apr�s la partition syst�me</i>) est bien la partition de stockage.')")."\"><img name=\"action_image3\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td></tr>\n";

				echo "<tr><td valign='top'>Rebooter en fin de restauration: </td>\n";
				echo "<td>\n";
				echo "<input type='radio' name='auto_reboot' value='y' checked />\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr><td valign='top'>Eteindre en fin de restauration: </td>\n";
				echo "<td>\n";
				echo "<input type='radio' name='auto_reboot' value='halt' />\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr><td valign='top'>Ne pas rebooter ni �teindre la machine<br />en fin de restauration: </td>\n";
				echo "<td>\n";
				echo "<input type='radio' name='auto_reboot' value='n' />\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr><td valign='top'>\n";
				echo "D�lai avant reboot/arr�t:</td>\n";
				echo "<td>\n";
				echo "<input type='text' name='delais_reboot' value='90' size='3' />\n";
				echo "<u onmouseover=\"this.T_SHADOWWIDTH=5;this.T_STICKY=1;return escape".gettext("('Le d�lai doit �tre sup�rieur � 60 secondes pour permettre la r�cup�ration du rapport de restauration.')")."\"><img name=\"action_image4\"  src=\"../elements/images/help-info.gif\"></u>\n";
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr><td valign='top'>Pour la ou les machines s�lectionn�es: </td>\n";
				echo "<td>\n";
					echo "<table border='0'>\n";
					echo "<tr><td valign='top'><input type='checkbox' id='wake' name='wake' value='y' checked /> </td><td><label for='wake'>D�marrer les machines par Wake-On-Lan/etherwake<br />si elles sont �teintes.</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_wait1' name='shutdown_reboot' value='wait1' /> </td><td><label for='shutdown_reboot_wait1'>Attendre le reboot des machines<br />m�me si aucune session n'est ouverte,</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_wait2' name='shutdown_reboot' value='wait2' checked /> </td><td><label for='shutdown_reboot_wait2'>Red�marrer les machines sans session ouverte<br />et attendre le reboot pour les machines<br />qui ont des sessions ouvertes,</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_reboot' name='shutdown_reboot' value='reboot' /> </td><td><label for='shutdown_reboot_reboot'>Red�marrer les machines<br />m�me si une session est ouverte (<i>p� cool</i>).</label></td></tr>\n";
					echo "</table>\n";
				echo "</td></tr>\n";

				echo "</table>\n";

				echo "<p align='center'><input type=\"submit\" name=\"validation_parametres\" value=\"Valider\" /></p>\n";
				echo "</form>\n";

				//======================================================
				$temoin_svg_existantes=0;
				$chaine_tab="<p>Liste des sauvegardes existantes:</p>\n";
				$chaine_tab.="<table class='crob'>\n";
				$chaine_tab.="<tr>\n";
				$chaine_tab.="<th>Id</th>\n";
				$chaine_tab.="<th>Nom</th>\n";
				$chaine_tab.="<th>Partition</th>\n";
				$chaine_tab.="<th>Sauvegarde</th>\n";
				$chaine_tab.="<th>Date</th>\n";
				$chaine_tab.="<th>Descriptif</th>\n";
				$chaine_tab.="</tr>\n";
				for($i=0;$i<count($id_machine);$i++){
					$sql="SELECT * FROM se3_tftp_sauvegardes WHERE id='".$id_machine[$i]."';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)>0) {
						$lig=mysql_fetch_object($res);

						$chaine_tab.="<tr>\n";
						$chaine_tab.="<th>$lig->id</th>\n";
						$chaine_tab.="<th>$lig->nom</th>\n";
						$chaine_tab.="<th>$lig->partition</th>\n";
						$chaine_tab.="<th>$lig->image</th>\n";
						$chaine_tab.="<th>".mysql_date_to_fr_date($lig->date)."</th>\n";
						$chaine_tab.="<th>$lig->descriptif</th>\n";
						$chaine_tab.="</tr>\n";

						$temoin_svg_existantes++;
					}
				}
				$chaine_tab.="</table>\n";
				$chaine_tab.="<p><br /></p>\n";
				if($temoin_svg_existantes>0) {
					echo $chaine_tab;
				}
				//======================================================

				echo "<p><i>NOTES:</i></p>\n";
				echo "<ul>\n";
				echo "<li>Ce choix n�cessite une partition de sauvegarde sur la machine.</li>\n";
				echo "<li>Si le nom de l'image est laiss� vide, c'est la sauvegarde la plus r�cente taggu�e 'SUCCES' qui est utilis�e.<br />C'est-�-dire qu'il doit exister un fichier NOM_SAUVEGARDE.SUCCES.txt<br />Les sauvegardes sont recherch�es dans un dossier /oscar/ � la racine de la partition (<i>si le nom de l'image est laiss� vide</i>).</li>\n";
				echo "<li>Il faudra stocker dans une table les informations sur les sauvegardes effectu�es/trouv�es pour pouvoir ne proposer ici que des choix valides.</li>\n";
				echo "<li><b>Attention:</b > Le d�lai avant reboot ajout� au temps de l'op�ration lanc�e doit d�passer la p�riodicit� du script controle_actions_tftp.sh en crontab.<br />
				Ce d�lai doit aussi permettre de r�cup�rer en http://IP_CLIENT/~hacker/Public/*.txt des informations sur le succ�s ou l'�chec de l'op�ration.<br />
				Une t�che cron se charge d'effectuer le 'wget' sur les infos, puis le remplissage d'une table MySQL.<br />
				La t�che cron est lanc�e toutes les 60s.</li>\n";
				echo "<li>Pour que la restauration puisse �tre enti�rement provoqu�e depuis le serveur, il faut que les postes clients soient configur�s pour booter en PXE (<i>ou au moins s'�veiller (wol) en bootant sur le r�seau</i>).<br />Dans le cas contraire, vous devrez passer sur les postes et presser F12 pour choisir de booter en PXE.</li>\n";
				echo "</ul>\n";


			}
			else {
				echo "<h2>Validation des param�tres de la restauration</h2>\n";

				echo "<p>Rappel des param�tres:</p>\n";
				echo "<table class='crob'>\n";
				echo "<tr>\n";
				echo "<th style='text-align:left;'>Nom de l'image: </th>\n";
				echo "<td>\n";
				if($nom_image=="") {echo "D�tect� automatiquement lors de la restauration.";} else {echo $nom_image;}
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Partition � restaurer: </th>\n";
				echo "<td>\n";
				if($dest_part=="auto") {echo "D�tect�e automatiquement lors de la sauvegarde.";} else {echo $dest_part;}
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Partition de stockage de la sauvegarde: </th>\n";
				echo "<td>\n";
				if($src_part=="auto") {echo "D�tect�e automatiquement lors de la sauvegarde.";} else {echo $src_part;}
				echo "</td>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th style='text-align:left;'>Rebooter en fin de restauration: </th>\n";
				echo "<td>\n";
				echo $auto_reboot;
				echo "</td>\n";
				echo "</tr>\n";

				//if($auto_reboot=='y') {
				if(($auto_reboot=='y')||($auto_reboot=='halt')) {
					echo "<tr>\n";
					echo "<th style='text-align:left;'>D�lai avant reboot: </th>\n";
					echo "<td>\n";
					echo "$delais_reboot s";
					echo "</td>\n";
					echo "</tr>\n";
				}

				echo "</table>\n";


				echo "<p>G�n�ration du fichier dans /tftpboot/pxelinux.cfg/ pour la restauration.<br />\n";

				// BOUCLE SUR LA LISTE DES $id_machine[$i]

				// Num�ro de l'op�ration de sauvegarde:
				$num_op=get_free_se3_action_tftp_num_op();
				for($i=0;$i<count($id_machine);$i++) {
					$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine[$i]."';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)==0) {
						echo "<span style='color:red;'>La machine d'identifiant $id_machine[$i] n'existe pas dans 'se3_dhcp'.</span><br />\n";
					}
					else {
						$temoin_erreur="n";

						$lig=mysql_fetch_object($res);
						$mac_machine=$lig->mac;
						$nom_machine=$lig->name;
						$ip_machine=$lig->ip;

						echo "G�n�ration pour $nom_machine: ";

						$corrige_mac=strtolower(strtr($mac_machine,":","-"));

						$chemin="/usr/share/se3/scripts";
						$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'restaure' '$corrige_mac' '$ip_machine' '$nom_machine' '$nom_image' '$src_part' '$dest_part' '$auto_reboot' '$delais_reboot'", $retour);

						if(count($retour)>0){
							echo "<span style='color:red;'>ECHEC de la g�n�ration du fichier</span><br />\n";
							for($j=0;$j<count($retour);$j++){
								echo "$retour[$j]<br />\n";
							}
							$temoin_erreur="y";
						}
						else {
							$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine[$i]';";
							$suppr=mysql_query($sql);

							$timestamp=time();
							$sql="INSERT INTO se3_tftp_action SET id='$id_machine[$i]',
																	mac='$mac_machine',
																	name='$nom_machine',
																	date='$timestamp',
																	type='restauration',
																	num_op='$num_op',
																	infos='nom_image=$nom_image|src_part=$src_part|dest_part=$dest_part|auto_reboot=$auto_reboot|delais_reboot=$delais_reboot';";
							$insert=mysql_query($sql);
							if(!$insert) {
								echo "<span style='color:red;'>ECHEC de l'enregistrement dans 'se3_tftp_action'</span><br />\n";
								$temoin_erreur="y";
							}

							// G�n�ration du lanceur de r�cup�ration:
							//$dossier="/var/se3/tmp/tftp/$id_machine[$i]";
							$dossier="/etc/se3/www-tools/tftp/$id_machine[$i]";
							if(!file_exists($dossier)) { mkdir($dossier,0700);}
							$lanceur_recup="$dossier/lanceur_recup_rapport_action_tftp.sh";
							$fich=fopen($lanceur_recup,"w+");
							$timestamp_limit=time()+4*3600;
							//fwrite($fich,"/usr/share/se3/scripts/recup_rapport.php '$id_machine[$i]' '$ip_machine' 'restauration' '$timestamp_limit'");
							fwrite($fich,"sudo /usr/share/se3/scripts/recup_rapport.php '$id_machine[$i]' '$ip_machine' 'restauration' '$timestamp_limit'");
							fclose($fich);
							chmod($lanceur_recup,0750);

							// M�nage dans les t�ches pr�c�dentes
							@exec("sudo /usr/share/se3/scripts/se3_tftp_menage_atq.sh $id_machine[$i]",$retour);

							// Planification de la t�che
							//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
							@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
							if($retour) {
								echo "<span style='color:red;'>ECHEC de la planification de la t�che.</span><br />\n";
								for($j=0;$j<count($retour);$j++){echo "$retour[$j]<br />\n";}
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

				// +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-
				// POUVOIR TAGUER DANS LA TABLE se3_dhcp LES MACHINES QUI PEUVENT BOOTER EN PXE
				// Ajouter un champ?
				// +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-


				// On n'affiche le fichier que pour le dernier (� titre d'info):
				if(isset($corrige_mac)) {
					$fich=fopen("/tftpboot/pxelinux.cfg/01-$corrige_mac","r");
					if($fich) {
						echo "<p>Pour information, voici le contenu du fichier g�n�r�:<br />\n";
						echo "<pre style='color:green;'>";
						while(!feof($fich)) {
							$ligne=fgets($fich,4096);
							echo htmlentities($ligne);
						}
						echo "</pre>\n";
						fclose($fich);
					}
					else {
						echo "<p style='color:red;'>Il n'a pas �t� possible d'ouvrir le fichier /tftpboot/pxelinux.cfg/01-$corrige_mac</p>\n";
					}
				}
			}
		}
		echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";
	}
}
else {
	print (gettext("Vous n'avez pas les droits n�cessaires pour ouvrir cette page..."));
}

// Footer
include ("pdp.inc.php");
?>
