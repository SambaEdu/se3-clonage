<?php
/* $Id: action_unattend_xp_tftp.php 5491 2010-05-04 20:55:01Z dbo $
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
$_SESSION["pageaide"]="Le_module_Clonage_des_stations#Unattend_XP";

// On active les rapports d'erreurs:
//error_reporting(E_ALL);

// Bibliothèque prototype Ajax pour afficher en décalé l'état des machines:
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
	// Création de la table dès que possible:
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

	// Paramètres SliTaz:
	/*
	$nom_image=isset($_POST['nom_image']) ? $_POST['nom_image'] : (isset($_GET['nom_image']) ? $_GET['nom_image'] : NULL);
	$src_part=isset($_POST['src_part']) ? $_POST['src_part'] : (isset($_GET['src_part']) ? $_GET['src_part'] : NULL);
	$dest_part=isset($_POST['dest_part']) ? $_POST['dest_part'] : (isset($_GET['dest_part']) ? $_GET['dest_part'] : NULL);
	*/
	//$auto_reboot=isset($_POST['auto_reboot']) ? $_POST['auto_reboot'] : (isset($_GET['auto_reboot']) ? $_GET['auto_reboot'] : NULL);
	//$delais_reboot=isset($_POST['delais_reboot']) ? $_POST['delais_reboot'] : (isset($_GET['delais_reboot']) ? $_GET['delais_reboot'] : NULL);

	// Paramètres concernant l'action immédiate sur les machines choisies:
	$wake=isset($_POST['wake']) ? $_POST['wake'] : (isset($_GET['wake']) ? $_GET['wake'] : "n");
	$shutdown_reboot=isset($_POST['shutdown_reboot']) ? $_POST['shutdown_reboot'] : (isset($_GET['shutdown_reboot']) ? $_GET['shutdown_reboot'] : NULL);


	echo "<h1>".gettext("Action Unattend XP")."</h1>\n";

	if(!isset($parc)){
		echo "<p>Cette page doit vous permettre de programmer une installation de XP via Unattended sur les machines choisies.</p>\n";

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

		echo "<p><a href='index.php'>Retour à l'index</a>.</p>\n";
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

				//echo "<th>Sauvegarde</th>\n";
				echo "<th>Install XP unattend<br />\n";
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

						echo "<tr>\n";
						echo "<td width='20%'>".$mp[$loop]."</td>\n";

						// Etat: allumé ou éteint
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
							// mp_curr[0]["macAddress"] correspond à une adresse mac recherchée dans l'annuaire LDAP.
							// Si les machines ont été changées et que l'on a ré-attribué le nom, il faut penser à nettoyer l'entrée dans l'annuaire:
							// source /usr/share/se3/sbin/variables_admin_ldap.sh
							// ldapdelete -x -D $ROOTDN -w $PASSDN cn=NOM_MACHINE,ou=Computers,$BASEDN
							// Et se reconnecter une fois sur la machine pour que le connexion.pl renseigne une nouvelle entrée cn=NOM_MACHINE
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


						// Sélection des machines à sauvegarder:
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
				echo "<h2>Paramétrage du lancement de l'installation</h2>\n";

				$nombre_machines=count($id_machine);
				if($nombre_machines==0){
					echo "<p>ERREUR: Il faut choisir au moins une machine.</p>\n";

					echo "<p><a href='#' onclick='history.go(-1);'>Retour au choix des machines sur lesquelles installer XP.</a>.</p>\n";

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

				// Liste des machines sur lesquelles lancer l'install:
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
				echo "<p>Machine$s concernée$s: $chaine</p>\n";


				// Date pour le nom de l'image à générer:
				$aujourdhui = getdate();
				$mois_se3 = sprintf("%02d",$aujourdhui['mon']);
				$jour_se3 = sprintf("%02d",$aujourdhui['mday']);
				$annee_se3 = $aujourdhui['year'];
				$heure_se3 = sprintf("%02d",$aujourdhui['hours']);
				$minute_se3 = sprintf("%02d",$aujourdhui['minutes']);
				$seconde_se3 = sprintf("%02d",$aujourdhui['seconds']);

				$date_se3=$annee_se3.$mois_se3.$jour_se3;

				echo "<p>Choisissez les paramètres pour le lancement de l'installation: <br />\n";

echo "<style type='text/css'>
table.crob {
	border-style: solid;
	border-width: 1px;
	border-color: black;
	border-collapse: collapse;
}

.crob th {
	border-style: solid;
	border-width: 1px;
	border-color: black;
	background-color: khaki;
	font-weight:bold;
	text-align:center;
	vertical-align: middle;
}

.crob td {
	text-align:center;
	vertical-align: middle;
	border-style: solid;
	border-width: 1px;
	border-color: black;
}

.crob .lig-1 {
	background-color: #6699cc;
}
.crob .lig1 {
	background-color: #FFFFFF;
}
</style>\n";

				echo "<table border='1' class='crob'>\n";

//===========================================================
echo "<tr>\n";
echo "<td valign='top'>Partitionnement</td>\n";
echo "<td valign='top'>\n";
	echo "<table>\n";
	echo "<tr>\n";

	echo "<td valign='top'>Partitionnement :</td>\n";
	echo "<td style='text-align:left;'>\n";
	echo "<input type='radio' name='fdisk_cmds' id='fdisk_cmds0' value='0' checked /><label for='fdisk_cmds0'> Supprimer toutes les partitions et créer une unique partition NTFS</label><br />\n";
	echo "<input type='radio' name='fdisk_cmds' id='fdisk_cmds1' value='1' /><label for='fdisk_cmds1'> Formater puis installer sur la première partition principale sans toucher à la table de partitions</label><br />\n";
	echo "<input type='radio' name='fdisk_cmds' id='fdisk_cmds2' value='2' /><label for='fdisk_cmds2'> Formater puis installer sur la deuxième partition principale sans toucher à la table de partitions</label><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td valign='top'>Ecraser le MBR :</td>\n";
	echo "<td style='text-align:left;'>\n";
	echo "<input type='radio' name='replace_mbr' id='replace_mbr1' value='1' checked /><label for='replace_mbr1'> Oui</label><br />\n";
	echo "<input type='radio' name='replace_mbr' id='replace_mbr0' value='0' /><label for='replace_mbr0'> Non</label><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

echo "</td>\n";
echo "</tr>\n";
//===========================================================
echo "<tr>\n";
echo "<td valign='top'>Display</td>\n";
echo "<td align='left'>\n";
	echo "<table>\n";
	echo "<tr>\n";

	echo "<td valign='top'>Nombre de couleurs :</td>\n";
	echo "<td style='text-align:left;'>\n";
	echo "<input type='radio' name='BitsPerPel' id='BitsPerPel0' value='16' checked /><label for='BitsPerPel0'> 16</label><br />\n";
	echo "<input type='radio' name='BitsPerPel' id='BitsPerPel1' value='32' /><label for='BitsPerPel1'> 32</label><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td valign='top'>Résolution :</td>\n";
	echo "<td style='text-align:left;'>\n";
	echo "<input type='radio' name='Xresolution' id='Xresolution800' value='800' /><label for='Xresolution800'> 800*600</label><br />\n";
	echo "<input type='radio' name='Xresolution' id='Xresolution1024' value='1024' checked /><label for='Xresolution1024'> 1024*768</label><br />\n";
	echo "<input type='radio' name='Xresolution' id='Xresolution1280' value='1280' /><label for='Xresolution1280'> 1280*800</label><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

echo "</td>\n";
echo "</tr>\n";
//===========================================================
echo "<tr>\n";
echo "<td valign='top'>Mises à jour</td>\n";
echo "<td align='left'>\n";
	echo "<table>\n";
	echo "<tr>\n";
/*
DisableDynamicUpdates=Yes
Si vous mettez la valeur " Yes ", vous obligerez Windows à ne pas se connecter à Windows Update lors de son installation.
*/
	echo "<td valign='top'>Ne pas se connecter à Windows Update lors de l'installation :</td>\n";
	echo "<td style='text-align:left;'>\n";
	echo "<input type='radio' name='DisableDynamicUpdates' id='DisableDynamicUpdatesYes' value='yes' /><label for='DisableDynamicUpdatesYes'> Oui</label><br />\n";
	echo "<input type='radio' name='DisableDynamicUpdates' id='DisableDynamicUpdatesNo' value='no' checked /><label for='DisableDynamicUpdatesNo'> Non</label><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "<tr>\n";
	echo "<td valign='top'>Activer les mises à jour automatiques de Windows :</td>\n";
	echo "<td style='text-align:left;'>\n";
	echo "<input type='radio' name='AutomaticUpdates' id='AutomaticUpdatesYes' value='yes' checked /><label for='AutomaticUpdatesYes'> Oui</label><br />\n";
	echo "<input type='radio' name='AutomaticUpdates' id='AutomaticUpdatesNo' value='no' /><label for='AutomaticUpdatesNo'> Non</label><br />\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

echo "</td>\n";
echo "</tr>\n";
//===========================================================
/*
echo "<tr>\n";
echo "<td colspan='2' bgcolor='grey'>\n";
echo "&nbsp;";
echo "</td>\n";
echo "</tr>\n";
*/
//===========================================================
echo "<tr>\n";
echo "<td valign='top'><p>Composants Windows XP</p></td>\n";
echo "<td style='text-align:left;'>\n";

	/*
	echo "<table>\n";
	echo "<tr>\n";
	echo "<td></td>\n";
	echo "<td>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	*/
	echo "<a name='win_components'></a>";
	echo "<p><a href='#win_components' onclick='display_div_components();'>Sélection des composants à installer</a></p>\n";

	echo "<div id='div_components'>\n";
	echo "<table>\n";
/*
	echo "<tr>
          <td width='67%'>Options d'accessibilit&eacute;s</td>
          <td width='33%' style='text-align:left;'>
            <select name='accessopt' id='accessopt'>
                <option value='on' selected='selected'>oui</option>
                <option value='off'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Calculatrice</td>
          <td style='text-align:center;'>
            <select name='calc' id='calc'>

                <option value='on' selected='selected'>oui</option>
                <option value='off'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Table des caract&egrave;res sp&eacute;ciaux </td>

          <td style='text-align:center;'>
            <select name='charmap' id='charmap'>
                <option value='on' selected='selected'>oui</option>
                <option value='off'>non</option>
            </select>
          </div></td>
        </tr>

        <tr>
          <td style='text-align:left;'>Hyper Terminal</td>

          <td style='text-align:center;'>
            <select name='hypertrm' id='hypertrm'>
                <option value='on' selected='selected'>oui</option>
                <option value='off'>non</option>
            </select>
          </div></td>
        </tr>

        <tr>
          <td style='text-align:left;'>Chat (rien avoir avec windows messenger; utile pour les popup)</td>
          <td style='text-align:center;'>
            <select name='chat' id='chat'>
                <option value='on' selected='selected'>oui</option>
                <option value='off'>non</option>
            </select>
          </div></td>

        </tr>

        <tr>
          <td style='text-align:left;'>Fonds d' &eacute;cran standards de windows </td>
          <td style='text-align:center;'>
            <select name='deskpaper' id='deskpaper'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>

            </select>
          </div></td>
        </tr>

        <tr>
          <td style='text-align:left;'>Utilitaire de D&eacute;bogage de Microsoft </td>
          <td style='text-align:center;'>
            <select name='iisdbg' id='iisdbg'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Service d' indexation des fichiers</td>
          <td style='text-align:center;'>
            <select name='indexsrv_system' id='indexsrv_system'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>

            </select>
          </div></td>
        </tr>

        <tr>
          <td style='text-align:left;'>Fichiers sons suppl&eacute;mentaires </td>
          <td style='text-align:center;'>
            <select name='media_clips' id='media_clips'>
                <option value='on'>oui</option>

                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Fichiers sons du mod&egrave;le Utopia</td>
          <td style='text-align:center;'>

            <select name='media_utopia' id='media_utopia'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Diff&eacute;rents pointeurs de la souris</td>
          <td style='text-align:center;'>
            <select name='mousepoint' id='mousepoint'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>

        <tr>
          <td style='text-align:left;'>Windows Media Player</td>
          <td style='text-align:center;'>
            <select name='mplay' id='mplay'>
                <option value='on' selected='selected'>oui</option>
                <option value='off'>non</option>
            </select>
          </div></td>
        </tr>

        <tr>
          <td style='text-align:left;'>Wordpad</td>
          <td style='text-align:center;'>
            <select name='mswordpad' id='mswordpad'>
                <option value='on' selected='selected'>oui</option>

                <option value='off'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Microsoft Paint</td>
          <td style='text-align:center;'>
            <select name='paint' id='paint'>
                <option value='on' selected='selected'>oui</option>
                <option value='off'>non</option>
            </select>
          </div></td>
        </tr>

        <tr>
          <td style='text-align:left;'>Magn&eacute;tophone</td>
          <td style='text-align:center;'>
            <select name='rec' id='rec'>
                <option value='on' selected='selected'>oui</option>
                <option value='off'>non</option>
            </select>
          </div></td>

        </tr>
        <tr>
          <td style='text-align:left;'>Utilitaire de r&eacute;glage du volume du son.</td>
          <td style='text-align:center;'>

            <select name='vol' id='vol'>
                <option value='on' selected='selected'>oui</option>
                <option value='off'>non</option>
            </select>
          </div></td>
        </tr>

        <tr>
          <td style='text-align:left;'>Diff&eacute;rents raccourcis d' Internet exploreur.</td>
          <td style='text-align:center;'>
            <select name='IEAccess' id='IEAccess'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>

        <tr>
          <td style='text-align:left;'>MSN Explorer 4.7</td>
          <td style='text-align:center;'>
            <select name='msnexplr' id='msnexplr'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Installer Messenger 4.6</td>
          <td style='text-align:center;'>
            <select name='msmsgs' id='msmsgs'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>

        <tr><td colspan='2' bgcolor='#6699cc'>Jeux (Soyez s&eacute;rieux...SVP)</td></tr>
        <tr>
          <td style='text-align:left;'>Jeu &quot; Freecel &quot; </td>
          <td style='text-align:center;'>
            <select name='freecell' id='freecell'>

                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Jeu &quot; le d&eacute;mineur&quot;</td>
          <td style='text-align:center;'>
            <select name='minesweeper' id='minesweeper'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Jeu &quot; pinball &quot; </td>

          <td style='text-align:center;'>
            <select name='pinball' id='pinball'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Jeu &quot; le Solitaire &quot; </td>
          <td style='text-align:center;'>
            <select name='solitaire' id='solitaire'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>        <tr>
          <td style='text-align:left;'>Jeu &quot; Dame de pique &quot; </td>

          <td style='text-align:center;'>
            <select name='hearts' id='hearts'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Jeu &quot; Spider Solitaire &quot; </td>
          <td style='text-align:center;'>
            <select name='spider' id='spider'>

                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>
        <tr>
          <td style='text-align:left;'>Jeux sur internet &quot; </td>

          <td style='text-align:center;'>
            <select name='zonegames' id='zonegames'>
                <option value='on'>oui</option>
                <option value='off' selected='selected'>non</option>
            </select>
          </div></td>
        </tr>";
*/
	echo "<tr>
<td style='text-align:left;'>Options d'accessibilit&eacute;s</td>
<td style='text-align:center;'>
<input type='radio' name='accessopt' id='accessopton' value='on' checked /><label for='accessopton'> oui</label>
<br />
<input type='radio' name='accessopt' id='accessoptoff' value='off' /><label for='accessoptoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Calculatrice</td>
<td style='text-align:center;'>
<input type='radio' name='calc' id='calcon' value='on' checked /><label for='calcon'> oui</label>
<br />
<input type='radio' name='calc' id='calcoff' value='off' /><label for='calcoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Table des caract&egrave;res sp&eacute;ciaux </td>
<td style='text-align:center;'>
<input type='radio' name='charmap' id='charmapon' value='on' checked /><label for='charmapon'> oui</label>
<br />
<input type='radio' name='charmap' id='charmapoff' value='off' /><label for='charmapoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Hyper Terminal</td>
<td style='text-align:center;'>
<input type='radio' name='hypertrm' id='hypertrmon' value='on' checked /><label for='hypertrmon'> oui</label>
<br />
<input type='radio' name='hypertrm' id='hypertrmoff' value='off' /><label for='hypertrmoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Chat (rien avoir avec windows messenger; utile pour les popup)</td>
<td style='text-align:center;'>
<input type='radio' name='chat' id='chaton' value='on' checked /><label for='chaton'> oui</label>
<br />
<input type='radio' name='chat' id='chatoff' value='off' /><label for='chatoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Fonds d' &eacute;cran standards de windows </td>
<td style='text-align:center;'>
<input type='radio' name='deskpaper' id='deskpaperon' value='on' /><label for='deskpaperon'> oui</label>
<br />
<input type='radio' name='deskpaper' id='deskpaperoff' value='off' checked /><label for='deskpaperoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Utilitaire de D&eacute;bogage de Microsoft </td>
<td style='text-align:center;'>
<input type='radio' name='iisdbg' id='iisdbgon' value='on' /><label for='iisdbgon'> oui</label>
<br />
<input type='radio' name='iisdbg' id='iisdbgoff' value='off' checked /><label for='iisdbgoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Service d' indexation des fichiers</td>
<td style='text-align:center;'>
<input type='radio' name='indexsrv_system' id='indexsrv_systemon' value='on' /><label for='indexsrv_systemon'> oui</label>
<br />
<input type='radio' name='indexsrv_system' id='indexsrv_systemoff' value='off' checked /><label for='indexsrv_systemoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Fichiers sons suppl&eacute;mentaires </td>
<td style='text-align:center;'>
<input type='radio' name='media_clips' id='media_clipson' value='on' /><label for='media_clipson'> oui</label>
<br />
<input type='radio' name='media_clips' id='media_clipsoff' value='off' checked /><label for='media_clipsoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Fichiers sons du mod&egrave;le Utopia</td>
<td style='text-align:center;'>
<input type='radio' name='media_utopia' id='media_utopiaon' value='on' /><label for='media_utopiaon'> oui</label>
<br />
<input type='radio' name='media_utopia' id='media_utopiaoff' value='off' checked /><label for='media_utopiaoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Diff&eacute;rents pointeurs de la souris</td>
<td style='text-align:center;'>
<input type='radio' name='mousepoint' id='mousepointon' value='on' /><label for='mousepointon'> oui</label>
<br />
<input type='radio' name='mousepoint' id='mousepointoff' value='off' checked /><label for='mousepointoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Windows Media Player</td>
<td style='text-align:center;'>
<input type='radio' name='mplay' id='mplayon' value='on' checked /><label for='mplayon'> oui</label>
<br />
<input type='radio' name='mplay' id='mplayoff' value='off' /><label for='mplayoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Wordpad</td>
<td style='text-align:center;'>
<input type='radio' name='mswordpad' id='mswordpadon' value='on' checked /><label for='mswordpadon'> oui</label>
<br />
<input type='radio' name='mswordpad' id='mswordpadoff' value='off' /><label for='mswordpadoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Microsoft Paint</td>
<td style='text-align:center;'>
<input type='radio' name='paint' id='painton' value='on' checked /><label for='painton'> oui</label>
<br />
<input type='radio' name='paint' id='paintoff' value='off' /><label for='paintoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Magn&eacute;tophone</td>
<td style='text-align:center;'>
<input type='radio' name='rec' id='recon' value='on' checked /><label for='recon'> oui</label>
<br />
<input type='radio' name='rec' id='recoff' value='off' /><label for='recoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Utilitaire de r&eacute;glage du volume du son.</td>
<td style='text-align:center;'>
<input type='radio' name='vol' id='volon' value='on' checked /><label for='volon'> oui</label>
<br />
<input type='radio' name='vol' id='voloff' value='off' /><label for='voloff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Diff&eacute;rents raccourcis d' Internet exploreur.</td>
<td style='text-align:center;'>
<input type='radio' name='IEAccess' id='IEAccesson' value='on' /><label for='IEAccesson'> oui</label>
<br />
<input type='radio' name='IEAccess' id='IEAccessoff' value='off' checked /><label for='IEAccessoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>MSN Explorer 4.7</td>
<td style='text-align:center;'>
<input type='radio' name='msnexplr' id='msnexplron' value='on' /><label for='msnexplron'> oui</label>
<br />
<input type='radio' name='msnexplr' id='msnexplroff' value='off' checked /><label for='msnexplroff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Installer Messenger 4.6</td>
<td style='text-align:center;'>
<input type='radio' name='msmsgs' id='msmsgson' value='on' /><label for='msmsgson'> oui</label>
<br />
<input type='radio' name='msmsgs' id='msmsgsoff' value='off' checked /><label for='msmsgsoff'> non</label>
<br />
</div></td>
</tr>
<tr><td colspan='2' bgcolor='#6699cc'>Jeux (Soyez s&eacute;rieux...SVP)</td></tr>
<tr>
<td style='text-align:left;'>Jeu &quot; Freecel &quot; </td>
<td style='text-align:center;'>
<input type='radio' name='freecell' id='freecellon' value='on' /><label for='freecellon'> oui</label>
<br />
<input type='radio' name='freecell' id='freecelloff' value='off' checked /><label for='freecelloff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Jeu &quot; le d&eacute;mineur&quot;</td>
<td style='text-align:center;'>
<input type='radio' name='minesweeper' id='minesweeperon' value='on' /><label for='minesweeperon'> oui</label>
<br />
<input type='radio' name='minesweeper' id='minesweeperoff' value='off' checked /><label for='minesweeperoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Jeu &quot; pinball &quot; </td>
<td style='text-align:center;'>
<input type='radio' name='pinball' id='pinballon' value='on' /><label for='pinballon'> oui</label>
<br />
<input type='radio' name='pinball' id='pinballoff' value='off' checked /><label for='pinballoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Jeu &quot; le Solitaire &quot; </td>
<td style='text-align:center;'>
<input type='radio' name='solitaire' id='solitaireon' value='on' /><label for='solitaireon'> oui</label>
<br />
<input type='radio' name='solitaire' id='solitaireoff' value='off' checked /><label for='solitaireoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Jeu &quot; Dame de pique &quot; </td>
<td style='text-align:center;'>
<input type='radio' name='hearts' id='heartson' value='on' /><label for='heartson'> oui</label>
<br />
<input type='radio' name='hearts' id='heartsoff' value='off' checked /><label for='heartsoff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Jeu &quot; Spider Solitaire &quot; </td>
<td style='text-align:center;'>
<input type='radio' name='spider' id='spideron' value='on' /><label for='spideron'> oui</label>
<br />
<input type='radio' name='spider' id='spideroff' value='off' checked /><label for='spideroff'> non</label>
<br />
</div></td>
</tr>
<tr>
<td style='text-align:left;'>Jeux sur internet </td>
<td style='text-align:center;'>
<input type='radio' name='zonegames' id='zonegameson' value='on' /><label for='zonegameson'> oui</label>
<br />
<input type='radio' name='zonegames' id='zonegamesoff' value='off' checked /><label for='zonegamesoff'> non</label>
<br />
</div></td>
</tr>\n";

	echo "</table>\n";
	echo "</div>\n";

	echo "<script type='text/javascript'>
var alt=1;
function display_div_components() {
	alt=alt*-1;
	if(alt==1) {
		document.getElementById('div_components').style.display='block';
	}
	else {
		document.getElementById('div_components').style.display='none';
	}
}
display_div_components();
</script>\n";

//echo "<input type='radio' name='fdisk_cmds' id='fdisk_cmds0' value='0' /><label for='fdisk_cmds0'> détruire la première partition principale</label><br />\n";
//echo "<input type='radio' name='fdisk_cmds' id='fdisk_cmds1' value='1' /><label for='fdisk_cmds1'> installer sur la première partition principale sans toucher à la table de partitions</label><br />\n";

echo "</td>\n";
echo "</tr>\n";
//===========================================================
echo "<tr>\n";
echo "<td>Firewall de Windows XP</td>\n";
echo "<td style='text-align:left;'>\n";
/*
echo "D&eacute;sactiver le Firewall int&eacute;gr&eacute; &agrave; Windows: 
<select name='firewall' id='firewall'>
	<option value='1' selected='selected'>oui</option>
	<option value='0'>non</option>
</select>\n";
*/
echo "D&eacute;sactiver le Firewall int&eacute;gr&eacute; &agrave; Windows: 
<input type='radio' id='firewall1' name='firewall' value='1' checked /><label for='firewall1'> Oui </label>
ou <label for='firewall0'> Non </label>
<input type='radio' id='firewall0' name='firewall' value='0' />\n";

echo "</td>\n";
echo "</tr>\n";
//===========================================================


				echo "<tr><td valign='top'>Pour la ou les machines sélectionnées: </td>\n";
				echo "<td>\n";
					echo "<table border='0'>\n";
					echo "<tr><td valign='top'><input type='checkbox' id='wake' name='wake' value='y' checked /> </td><td style='text-align:left;'><label for='wake'>Démarrer les machines par Wake-On-Lan/etherwake<br />si elles sont éteintes.</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_wait1' name='shutdown_reboot' value='wait1' /> </td><td style='text-align:left;'><label for='shutdown_reboot_wait1'>Attendre le reboot des machines<br />même si aucune session n'est ouverte,</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_wait2' name='shutdown_reboot' value='wait2' checked /> </td><td style='text-align:left;'><label for='shutdown_reboot_wait2'>Redémarrer les machines sans session ouverte<br />et attendre le reboot pour les machines<br />qui ont des sessions ouvertes,</label></td></tr>\n";
					echo "<tr><td valign='top'><input type='radio' id='shutdown_reboot_reboot' name='shutdown_reboot' value='reboot' /> </td><td style='text-align:left;'><label for='shutdown_reboot_reboot'>Redémarrer les machines<br />même si une session est ouverte (<i>pô cool</i>).</label></td></tr>\n";
					echo "</table>\n";
				echo "</td></tr>\n";

				echo "</table>\n";

				echo "<p align='center'><input type=\"submit\" name=\"validation_parametres\" value=\"Valider\" /></p>\n";
				echo "</form>\n";


				echo "<p><i>NOTES:</i></p>\n";
				echo "<ul>\n";
				//echo "<li>Ce choix nécessite une partition de sauvegarde sur la machine.</li>\n";
				/*
				echo "<li><b>Attention:</b > Le délai avant reboot ajouté au temps de l'opération lancée doit dépasser la périodicité du script controle_actions_tftp.sh en crontab.<br />
				Ce délai doit aussi permettre de récupérer en http://IP_CLIENT/~hacker/Public/*.txt des informations sur le succès ou l'échec de l'opération.<br />
				Une tâche cron se charge d'effectuer le 'wget' sur les infos, puis le remplissage d'une table MySQL.<br />
				La tâche cron est lancée toutes les 60s.</li>\n";
				*/
				echo "<li>Pour que l'opération puisse être entièrement provoquée depuis le serveur, il faut que les postes clients soient configurés pour booter en PXE (<i>ou au moins s'éveiller (wol) en bootant sur le réseau</i>).<br />Dans le cas contraire, vous devrez passer sur les postes et presser F12 pour choisir de booter en PXE.</li>\n";
				echo "</ul>\n";

			}
			else {
				echo "<h2>Validation des paramètres du lancement de l'installation XP unattended</h2>\n";

				//debug_var();
				//while read A;do B=$(echo "$A"|cut -d"'" -f2);echo "\$$B=isset($A) ? $A : 'on';";done < liste_champs.txt
				//while read A;do B=$(echo "$A"|cut -d"'" -f2);echo "$B=\$$B";done < liste_champs.txt

				//=========================
				// Partitionnement
				$fdisk_cmds=isset($_POST['fdisk_cmds']) ? $_POST['fdisk_cmds'] : 0;
				$replace_mbr=isset($_POST['replace_mbr']) ? $_POST['replace_mbr'] : 0;
				//=========================

				//=========================
				// Mises à jour
				$DisableDynamicUpdates=isset($_POST['DisableDynamicUpdates']) ? $_POST['DisableDynamicUpdates'] : "no";
				$AutomaticUpdates=isset($_POST['AutomaticUpdates']) ? $_POST['AutomaticUpdates'] : 'yes';
				//=========================

				//=========================
				// Composants W$
				$accessopt=isset($_POST['accessopt']) ? $_POST['accessopt'] : 'on';
				$calc=isset($_POST['calc']) ? $_POST['calc'] : 'on';
				$charmap=isset($_POST['charmap']) ? $_POST['charmap'] : 'on';
				$chat=isset($_POST['chat']) ? $_POST['chat'] : 'on';
				$deskpaper=isset($_POST['deskpaper']) ? $_POST['deskpaper'] : 'off';
				$hypertrm=isset($_POST['hypertrm']) ? $_POST['hypertrm'] : 'on';

				$iisdbg=isset($_POST['iisdbg']) ? $_POST['iisdbg'] : 'off';
				$indexsrv_system=isset($_POST['indexsrv_system']) ? $_POST['indexsrv_system'] : 'off';
				$media_clips=isset($_POST['media_clips']) ? $_POST['media_clips'] : 'off';
				$media_utopia=isset($_POST['media_utopia']) ? $_POST['media_utopia'] : 'off';
				$mousepoint=isset($_POST['mousepoint']) ? $_POST['mousepoint'] : 'off';

				$mplay=isset($_POST['mplay']) ? $_POST['mplay'] : 'off';

				$mswordpad=isset($_POST['mswordpad']) ? $_POST['mswordpad'] : 'on';
				$paint=isset($_POST['paint']) ? $_POST['paint'] : 'on';

				$rec=isset($_POST['rec']) ? $_POST['rec'] : 'on';
				$vol=isset($_POST['vol']) ? $_POST['vol'] : 'on';

				$IEAccess=isset($_POST['IEAccess']) ? $_POST['IEAccess'] : 'on';

				$msnexplr=isset($_POST['msnexplr']) ? $_POST['msnexplr'] : 'off';
				$msmsgs=isset($_POST['msmsgs']) ? $_POST['msmsgs'] : 'off';

				// Jeux
				$freecell=isset($_POST['freecell']) ? $_POST['freecell'] : 'off';
				$hearts=isset($_POST['hearts']) ? $_POST['hearts'] : 'off';
				$minesweeper=isset($_POST['minesweeper']) ? $_POST['minesweeper'] : 'off';
				$pinball=isset($_POST['pinball']) ? $_POST['pinball'] : 'off';
				$solitaire=isset($_POST['solitaire']) ? $_POST['solitaire'] : 'off';
				$spider=isset($_POST['spider']) ? $_POST['spider'] : 'off';
				$zonegames=isset($_POST['zonegames']) ? $_POST['zonegames'] : 'off';

				$firewall=isset($_POST['firewall']) ? $_POST['firewall'] : 1;
				// Contrôler les valeurs: on/off, 0/1
				//=========================

				//=========================
				// Display:
				$BitsPerPel=isset($_POST['BitsPerPel']) ? $_POST['BitsPerPel'] : 16;
				if(($BitsPerPel!=16)&&($BitsPerPel!=32)) {$BitsPerPel=16;}

				$Xresolution=isset($_POST['Xresolution']) ? $_POST['Xresolution'] : 1024;
				if(($Xresolution!=800)&&($Xresolution!=1024)&&($Xresolution!=1280)) {$Xresolution=1024;}
				if($Xresolution=800) {$Yresolution=600;}
				elseif($Xresolution=1024) {$Yresolution=768;}
				elseif($Xresolution=1280) {$Yresolution=800;}
				//=========================

				//=========================
				// Serveur TFTP
				$dhcp_tftp_server=$_SERVER["SERVER_ADDR"];

				$sql="SELECT value FROM params WHERE name='dhcp_tftp_server';";
				$res=mysql_query($sql);
				if(mysql_num_rows($res)>0) {
					$lig=mysql_fetch_object($res);
					if($lig->value!="") {$dhcp_tftp_server=$lig->value;}
					// Il faudrait contrôler que l'adresse est valide, non?
				}
				//=========================

				//=========================
				// Serveur de temps NTP
				$dhcp_ntp="91.121.73.63";
				/*
				$ host fr.pool.ntp.org
				fr.pool.ntp.org has address 91.121.73.63
				fr.pool.ntp.org has address 81.25.192.148
				fr.pool.ntp.org has address 87.98.146.16
				fr.pool.ntp.org has address 88.191.23.205
				fr.pool.ntp.org has address 88.178.32.159
				*/
				$sql="SELECT value FROM params WHERE name='dhcp_ntp';";
				$res=mysql_query($sql);
				if(mysql_num_rows($res)>0) {
					$lig=mysql_fetch_object($res);
					if($lig->value!="") {$dhcp_ntp=$lig->value;}
					// Il faudrait contrôler que l'adresse est valide, non?
				}
				//=========================

				//=========================
				// M$IE
				$Home_Page="http://www.mozilla.com/en-US/";
				$sql="select valeur from corresp where chemin='HKEY_CURRENT_USER\\software\\microsoft\\Internet Explorer\\Main\\Start Page';";
				$res=mysql_query($sql);
				if(mysql_num_rows($res)>0) {
					$lig=mysql_fetch_object($res);
					if($lig->value!="") {$Home_Page=$lig->valeur;}
				}

				$Help_Page="http://www.mozilla.com/en-US/";
				$Search_Page="http://www.google.fr/firefox";
				//=========================

				echo "<p>";
				/*
				for ($i=0;$i<count($parc);$i++) {
					echo "Génération du fichier /var/se3/unattended/install/site/$parc[$i]/unattend.txt<br />\n";
	
					$dossier_unattend_txt="/var/se3/unattended/install/site/$parc[$i]";
					if(!file_exists($dossier_unattend_txt)) {mkdir($dossier_unattend_txt);}
					$fu=fopen("$dossier_unattend_txt/unattend.txt","w+");
					if(!$fu) {
						echo "<p>ERREUR lors de la création de $dossier_unattend_txt/unattend.txt</p>\n";
						include ("pdp.inc.php");
						die();
					}
				*/
				for($i=0;$i<count($id_machine);$i++) {
					$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine[$i]."';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)==0) {
						echo "<span style='color:red;'>La machine d'identifiant $id_machine[$i] n'existe pas dans 'se3_dhcp'.</span><br />\n";
					}
					else {

						$lig=mysql_fetch_object($res);
						//$mac_machine=$lig->mac;
						$nom_machine=$lig->name;

						$dossier_unattend_txt="/var/se3/unattended/install/site/";
						if(!file_exists($dossier_unattend_txt)) {mkdir($dossier_unattend_txt);}
						$fu=fopen("$dossier_unattend_txt/$nom_machine.txt","w+");
						if(!$fu) {
							echo "<p>ERREUR lors de la création de $dossier_unattend_txt/$nom_machine.txt</p>\n";
							include ("pdp.inc.php");
							die();
						}
						fwrite($fu,"[GuiUnattended]\r
TimeZone=105\r
OEMSkipRegional=1\r
OemSkipWelcome=1\r
EncryptedAdminPassword=No\r
AutoLogon=Yes\r
\r
[Unattended]\r
UnattendMode=FullUnattended\r
FileSystem=ConvertNTFS\r
ExtendOemPartition=1\r
OemSkipEula=Yes\r
OemPreinstall=Yes\r
AutomaticUpdates=$AutomaticUpdates\r
; OemFilesPath=\"..\\\$OEM$\"\r
OemPnpDriverPath=\\D\r
TargetPath=\"\\WINDOWS\"\r
AutoActivate=Yes\r
DisableDynamicUpdates=$DisableDynamicUpdates\r
; Needed for XP - see <http://support.microsoft.com/?kbid=294801>.\r
UnattendSwitch=Yes\r
DriverSigningPolicy=Ignore\r
Hibernation=No\r
NtUpgrade=No\r
Win9xUpgrade=No\r
\r
[Display]\r
BitsPerPel=$BitsPerPel\r
Xresolution=$Xresolution\r
YResolution=$Yresolution\r
Vrefresh=60\r
\r
[URL]\r
Home_Page=\"$Home_Page\"\r
Help_Page=\"$Help_Page\"\r
Search_Page=\"$Search_Page\"\r
\r
[Proxy]\r
; le proxy est inactif par defaut : l admin l'active via les clefs si besoin\r
Proxy_Enable=0\r
Use_Same_Proxy=1\r
\r
[Networking]\r
InstallDefaultComponents=Yes\r
\r
[NetOptionalComponents]\r
; Install Print Services for UNIX\r
LPDSVC=1\r
\r
[_meta]\r
ntinstall_cmd = \"nt5x-install\"\r
fdisk_lba=1\r
fdisk_confirm=0\r
edit_files=0\r
middle=\"\"\r
bottom=\"\"\r
local_admins=\"\"\r\n");
		
						if($fdisk_cmds==0) {
							fwrite($fu,"fdisk_cmds=\"fdisk /clear 1;fdisk /pri:8000;fdisk /activate:1\"\r\n");
							fwrite($fu,"format_cmd=\"format /y /z:seriously /q /u /a /v: c:\"\r\n");
						}
						elseif($fdisk_cmds==1) {
							fwrite($fu,"fdisk_cmds=\"echo On ne modifie pas les partitions\"\r\n");
							fwrite($fu,"format_cmd=\"format /y /z:seriously /q /u /a /v: c:\"\r\n");
						}
						elseif($fdisk_cmds==2) {
							fwrite($fu,"fdisk_cmds=\"echo On ne modifie pas les partitions\"\r\n");
							fwrite($fu,"format_cmd=\"format /y /z:seriously /q /u /a /v: d:\"\r\n");
						}
		
	
						fwrite($fu,"replace_mbr=$replace_mbr\r
local_admins=\"\"\r
ntp_servers=\"$dhcp_ntp\"\r
z_path=\"\\\\$dhcp_tftp_server\install\"\r
autolog=\"autolog.pl --logon=1 --user=administrateur --password=wawa\"\r
\r
[RegionalSettings]\r
; In french in the text\r
;Language=000040C\r
LanguageGroup=1\r
SystemLocale=0000040c\r
UserLocale=0000040c\r
InputLocale=040c:0000040c\r
\r
[Branding]\r
BrandIEUsingUnattended=Yes\r
\r
[Components]\r
accessopt=$accessopt\r
calc=$calc\r
charmap=$charmap\r
chat=$chat\r
deskpaper=$deskpaper\r
hypertrm=$hypertrm\r
iisdbg=$iisdbg\r
indexsrv_system=$indexsrv_system\r
media_clips=$media_clips\r
media_utopia=$media_utopia\r
mousepoint=$mousepoint\r
mplay=$mplay\r
mswordpad=$mswordpad\r
paint=$paint\r
rec=$rec\r
vol=$vol\r
; Jeux\r
freecell=$freecell\r
hearts=$hearts\r
minesweeper=$minesweeper\r
pinball=$pinball\r
solitaire=$solitaire\r
spider=$spider\r
zonegames=$zonegames\r
IEAccess=$IEAccess\r
msnexplr=$msnexplr\r
\r
hearts=off\r
IEAccess=off\r
\r
; Install IIS by default\r
iis_common=Off\r
iis_inetmgr=Off\r
iis_www=off\r
; Docs suggest iis_pwmgr only works on Win2k, but include it\r
; anyway\r
iis_pwmgr=Off\r
; Include docs\r
iis_doc=Off\r
\r\n");
						if($firewall==1) {
							fwrite($fu,"[WindowsFirewall]\r
Profiles=WindowsFirewall.TurnOffFirewall\r
\r
[WindowsFirewall.TurnOffFirewall]\r
Mode=0\r\n");
						}
						else {
							fwrite($fu,"[WindowsFirewall]\r
Profiles=WindowsFirewall.TurnOnFirewall\r
\r
[WindowsFirewall.TurnOnFirewall]\r
Mode=1\r\n");
						}
						fclose($fu);
					}
				}

				echo "<p>Génération des fichiers dans /tftpboot/pxelinux.cfg/ pour l'installation XP unattended.<br />\n";

				// BOUCLE SUR LA LISTE DES $id_machine[$i]

				// Numéro de l'opération de remontée de rapport:
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

						echo "Génération pour $nom_machine: ";

						$corrige_mac=strtolower(strtr($mac_machine,":","-"));

						$chemin="/usr/share/se3/scripts";
						$resultat=exec("/usr/bin/sudo $chemin/pxe_gen_cfg.sh 'unattend_xp' '$corrige_mac' '$ip_machine' '$nom_machine'", $retour);
						// patch devenu inutile car integre a pxe_gen_cfg.sh
						// patch pour lancer unattended-generate.sh lorsque l'on personnalise les unattend.txt
						//$nomscript=date(Y_m_d_H_i_s");
						//$nomscript="tmp_unattended_$nomscript.sh";
						//system("echo \"#!/bin/bash\n\" > /tmp/$nomscript");
						//chmod ("/tmp/$nomscript",0700);
						//system("echo \"sudo /usr/share/se3/scripts/unattended_generate.sh\n\" >> /tmp/$nomscript");
						//system("echo \"rm -f /tmp/$nomscript \n\" >> /tmp/$nomscript");
						//exec("at -f /tmp/$nomscript now + 1 minute");
						// fin du patch pour lancer unattended-generate.sh
						
						if(count($retour)>0){
							//echo "<p>";
							//echo "<span style='color:red;'>Il semble que la génération du fichier ait échoué...</span><br />\n";
							echo "<span style='color:red;'>ECHEC de la génération du fichier</span><br />\n";
							for($j=0;$j<count($retour);$j++){
								echo "$retour[$j]<br />\n";
							}
							$temoin_erreur="y";
							//echo "</p>\n";
						}
						else {
							$sql="DELETE FROM se3_tftp_action WHERE id='$id_machine[$i]';";
							$suppr=mysql_query($sql);

							$timestamp=time();
							$sql="INSERT INTO se3_tftp_action SET id='$id_machine[$i]',
																	mac='$mac_machine',
																	name='$nom_machine',
																	date='$timestamp',
																	type='unattend_xp',
																	num_op='$num_op',
																	infos='';";
							$insert=mysql_query($sql);
							if(!$insert) {
								echo "<span style='color:red;'>ECHEC de l'enregistrement dans 'se3_tftp_action'</span><br />\n";
								$temoin_erreur="y";
							}

							/*
							// Génération du lanceur de récupération:
							//$dossier="/var/se3/tmp/tftp/$id_machine[$i]";
							$dossier="/etc/se3/www-tools/tftp/$id_machine[$i]";
							if(!file_exists($dossier)) { mkdir($dossier,0700);}
							$lanceur_recup="$dossier/lanceur_recup_rapport_action_tftp.sh";
							$fich=fopen($lanceur_recup,"w+");
							// On donne 4H pour que la récup soit effectuée:
							$timestamp_limit=time()+4*3600;
							//fwrite($fich,"/usr/share/se3/scripts/recup_rapport.php '$id_machine[$i]' '$ip_machine' 'rapport' '$timestamp_limit'");
							fwrite($fich,"sudo /usr/share/se3/scripts/recup_rapport.php '$id_machine[$i]' '$ip_machine' 'rapport' '$timestamp_limit'");
							fclose($fich);
							chmod($lanceur_recup,0750);

							// Ménage dans les tâches précédentes
							@exec("sudo /usr/share/se3/scripts/se3_tftp_menage_atq.sh $id_machine[$i]",$retour);

							// Planification de la tâche
							//@exec("at -f $lanceur_recup now + 1 minute 2>/dev/null",$retour);
							@exec("at -f $lanceur_recup now + 1 minute 2>$dossier/at.txt",$retour);
							//passthru("at -f $lanceur_recup now + 1 minute",$retour);
							if($retour) {
								echo "<span style='color:red;'>ECHEC de la planification de la tâche.</span><br />\n";
								for($j=0;$j<count($retour);$j++){echo "$retour[$j]<br />\n";}
								//echo "$retour<br />\n";
								$temoin_erreur="y";
							}
							*/

							/*
							// Avec ça on arrive à récupérer l'info:
							//	-warning: commands will be executed using /bin/sh -
							//	-job 1572 at 2008-03-01 15:13 -
							// Mais une fois le at repoussé, ce n'est plus www-se3, mais root qui en est proprio...
							if(file_exists("$dossier/at.txt")) {
								$fp=fopen("$dossier/at.txt","r");
								while(!feof($fp)) {
									$ligne=fgets($fp,4096);
									echo "<p>-".$ligne."-</p>";
								}
								fclose($fp);
							}
							*/

							/*
							$fp=popen("at -f $lanceur_recup now + 1 minute","r");
							while(!feof($fp)) {
								$ligne=fgets($fp,4096);
								echo "<p>-".$ligne."-</p>";
							}
							fclose($fp);
							*/

							if($temoin_erreur=="n") {
								//echo "<span style='color:green;'>OK</span><br />\n";
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
?>
