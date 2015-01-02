<script type='text/javascript'>
function check_fdisk()
    {
    var i = 0;
    while(document.getElementById('fdisk' + i))
    {
     if(document.getElementById('fdisk' + i).checked)
      return true;
     i++;
    }
    window.alert("Il faut choisir le partitionnement souhait&eacute; !" );
    return false;
    }
</script>

<?php

require ("config.inc.php");

/**

 * Fonctions tftp
 * @Version $Id$

 * @Projet LCS / SambaEdu

 * @auteurs keyser   
 

 * @note Ce fichier de fonction doit etre appele par un include

 */
/**

 * @Repertoire: tftp
 * file: tftp.inc.php

 */


/**

* Retourne la liste des parc dans un tableau

* @Parametres 
* @Return 
*/

function choix_parc () {
echo "<p>Choisissez un ou des parcs:</p>\n";

$list_parcs=search_machines("objectclass=groupOfNames","parcs");
if ( count($list_parcs)==0) {
	echo "<br><br>";
	echo gettext("Il n'existe aucun parc. Vous devez d'abord cr&eacute;er un parc");
	include ("pdp.inc.php");
	exit;
}
sort($list_parcs);

echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
echo "<input type=\"hidden\" name=\"os\" value=\"$os\" />\n";

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
//return $parc;
//echo "<p><a href='index.php'>Retour à l'index</a>.</p>\n";
}



/**

* Retourne la liste des machines du ou des parcs dans un tableau

* @Parametres $parc
* @Return 
*/

function params_action ($parc)
{
echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
echo "<input type=\"hidden\" name=\"parametrage_action\" value=\"1\" />\n";
$max_eff_parc=0;
for($i=0;$i<count($parc);$i++){

	echo "<h2>Parc $parc[$i]</h2>\n";
	echo "<input type=\"hidden\" name=\"parc[]\" value=\"$parc[$i]\" />\n";
	echo "<input type=\"hidden\" name=\"os\" value=\"$os\" />\n";

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
	echo "<th>Install ".$os." Linux<br />\n";
	echo "<a href='#' onclick='check_machine($i,\"check\");return false'><img src=\"../elements/images/enabled.gif\" border='0' alt=\"Tout cocher\" title=\"Tout cocher\" /></a>\n";
	echo " / <a href='#' onclick='check_machine($i,\"uncheck\");return false'><img src=\"../elements/images/disabled.gif\" border='0' alt=\"Tout d&eacute;cocher\" title=\"Tout d&eacute;cocher\" /></a>\n";
	echo "</th>\n";
	echo "<th>Actions programm&eacute;es</th>\n";
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
			echo "<td width='15%'>".$mp[$loop]."</td>\n";

			// Etat: allumé ou éteint
			echo "<td width='15%'>";
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
			echo "<td width='15%'>\n";
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
			echo "<td width='15%'>\n";
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
					echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse IP attribu&eacute;e\" title=\"Pas d'adresse IP attribu&eacute;e\" />";
				}
			}
			else {
				echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'adresse MAC dans l'annuaire???\" title=\"Pas d'adresse MAC dans l'annuaire???\" />";
			}
			echo "</td>\n";
			// Sélection des machines à sauvegarder:
			echo "<td width='15%'>\n";
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
					echo "<a href='visu_action.php?id_machine=$id_machine' target='_blank'>$lig->type programm&eacute;(e)</a>";
				}
				else {
					echo "<img src=\"../elements/images/disabled.gif\" border='0' alt=\"Pas d'action programm&eacute;e\" title=\"Pas d'action programm&eacute;e\" />";
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

/**

* Retourne la liste des machines du ou des parcs dans un tableau

* @Parametres $parc,$os,$id_machine,$se3ip,$ntpserv,$xppass
* @Return 
*/

function choix_params_dist ($parc,$os,$id_machine,$se3ip,$ntpserv,$xppass) {

echo "<h2>Param&eacute;trage du lancement de l'installation</h2>\n";

$nombre_machines=count($id_machine);
if($nombre_machines==0){
	echo "<p>ERREUR: Il faut choisir au moins une machine.</p>\n";

	echo "<p><a href='#' onclick='history.go(-1);'>Retour au choix des machines sur lesquelles installer Debian.</a>.</p>\n";

	echo "<p><a href='".$_SERVER['PHP_SELF']."'>Retour au choix du/des parc(s)</a>.</p>\n";
	include ("pdp.inc.php");
	exit();
}

echo "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\" onsubmit=\"return check_fdisk();\">\n";
echo "<input type=\"hidden\" name=\"parametrage_action\" value=\"1\" />\n";
echo "<input type=\"hidden\" name=\"os\" value=\"$os\" />\n";
//echo "<input type=\"hidden\" name=\"parc\" value=\"dummy\" />\n";
// Liste des parcs:
for($i=0;$i<count($parc);$i++){
	echo "<input type=\"hidden\" name=\"parc[]\" value=\"$parc[$i]\" />\n";
        //echo "parcs : $parc[$i]";
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
echo "<p>Machine$s concern&eacute;e$s: $chaine</p>\n";


// Date pour le nom de l'image à générer:
$aujourdhui = getdate();
$mois_se3 = sprintf("%02d",$aujourdhui['mon']);
$jour_se3 = sprintf("%02d",$aujourdhui['mday']);
$annee_se3 = $aujourdhui['year'];
$heure_se3 = sprintf("%02d",$aujourdhui['hours']);
$minute_se3 = sprintf("%02d",$aujourdhui['minutes']);
$seconde_se3 = sprintf("%02d",$aujourdhui['seconds']);

$date_se3=$annee_se3.$mois_se3.$jour_se3;

// ip du serveur de temps (on suppose que la passerelle est aussi le serveur de temps) et mdp adminse3
    

//$query = "SELECT * from params where name REGEXP 'dhcp_ntp' OR name REGEXP'xppass' ";
//$result = mysql_query($query);
//while($resultat = mysql_fetch_assoc($result))
//{
// $infos[$resultat['name']] = $resultat['value'];
//}
//$ntp_server = $infos['dhcp_ntp'];
//    $xppass = $infos['xppass'];


$content .= "<p>Choisissez les paramètres pour le lancement de l'installation: <br />\n

<ul>
  <li>
   <label for='mirroir'>Choix du mirroir Debian</label> : <input type='text' name='mirroir' id='mirroir' value='$se3ip/debian' disabled='disabled'/><div style='font-size:small;'>On utilisera le se3 et son service apt-cacher <br /></div>
  </li>
  <li>Choix de la distribution :<br />";

//    <label for='distro'>Distribution</label> : <input type='text' name='distro' id='distro' value='Debian' disabled='disabled' /><br />
 $content .= "   <label for='version'>Version</label> : <input type='text' name='version' id='version' value='$os' disabled='disabled' /><br />";
//    <label for='sections'>Sections</label> : <input type='text' name='sections' id='sections' value='main, contrib, non-free' disabled='disabled' /><br />
 $content .= " </li>
  <li>Architecture :<br />
   <ol>
    <li><input type='radio' name='arch' id='arch32' value='i386' check='checked' /><label for='arch32'>32 bits</label></li>
    <li><input type='radio' name='arch' id='arch64' value='amd64' /><label for='arch64'>64 bits</label></li>
   </ol>
  </li>
  <li>
   <label for='ip_slis'>Adresse ip du serveur de temps</label> : <input type='text' name='ntp_server' id='ip_slis' value='$ntpserv' />
  </li>
  <li>Partitionnement :<br />
   <ol>
    <li><input type='radio' name='fdisk' id='fdisk0' value='0' onclick=\"alert('Attention, toute autre installation sera supprim&eacute;e');\" /><label for='fdisk0'>Installer Debian sur le disque dur entier dans une seule partition</label></li>
    <!--<li><input type='radio' name='fdisk' id='fdisk2' value='2' oncheck=\"alert('Attention, toute autre installation sera supprim&eacute;e');\" /><label for='fdisk2'>Installer Debian sur le disque dur entier dans deux partitions (/home s&eacute;par&eacute;)</label></li>-->
    <li><input type='radio' name='fdisk' id='fdisk1' value='1' /><label for='fdisk1'>Installer Debian sur une partition libre à côt&eacute; de Windows</label></li>
   </ol>
  </li>
  <li>
   <label for='root_mdp'>Mot de passe de l'utilisateur root</label> : <input type='password' name='root_mdp' id='root_mdp' value='".$xppass."' /> <small>Si vous laissez vide, c'est le mot de passe adminse3 qui sera utilis&eacute;</small>
  </li>
  <li>
   <label for='newuser_name'>Nom du nouvel utilisateur</label> : <input type='text' name='newuser_name' id='newuser_name' value='enseignant' /><br />
   <label for='newuser_mdp'>Mot de passe du nouvel utilisateur</label> : <input type='password' name='newuser_mdp' id='newuser_mdp' value='' /> <small>(Cet utilisateur n'est pas g&eacute;r&eacute; par le se3)</small>
  </li>
  <!--<li>
   <label for='grub_mdp'>Mot de passe Grub</label> : <input type='password' name='grub_mdp' id='grub_mdp' value='' /> <small>(Uniquement pour installation sur partition libre)</small>
  </li>-->
 </ul>\n";
$content .="<p align='center'><input type=\"submit\" name=\"validation_parametres\" value=\"Valider\" /></p>\n
</form>\n


<p><i>NOTES:</i></p>\n
<ul>\n

<li>Pour que l'op&eacute;ration puisse être entièrement provoqu&eacute;e depuis le serveur, il faut que les postes clients soient configur&eacute;s pour booter en PXE (<i>ou au moins s'&eacute;veiller (wol) en bootant sur le r&eacute;seau</i>).<br />Dans le cas contraire, vous devrez passer sur les postes et presser F12 pour choisir de booter en PXE.</li>\n
</ul>\n";
return $content;
}

/**

* ecriture des fichiers pressed en fction des infos précendentes 
* @Parametres $id_machine
* @Return 
*/


function valid_dist ($id_machine)
{
echo "<h2>Validation des param&egrave;tres du lancement de l'installation</h2>\n";

//debug_var();
//while read A;do B=$(echo "$A"|cut -d"'" -f2);echo "\$$B=isset($A) ? $A : 'on';";done < liste_champs.txt
//while read A;do B=$(echo "$A"|cut -d"'" -f2);echo "$B=\$$B";done < liste_champs.txt

//echo "$_POST[arch]";

//=========================
// Extraction de paramètres nécessaires par la suite
$query = "SELECT * from params where name='xppass' OR name='se3_domain' OR name='se3ip'";
$result = mysql_query($query);
while($resultat = mysql_fetch_assoc($result))
{
 $infos[$resultat['name']] = $resultat['value'];
}
$xppass = $infos['xppass'];
$domaine_local = $infos['se3_domain'];
$se3ip = $infos['se3ip'];
//=========================

//=========================
// Mirroir
$mirroir=isset($_POST['mirroir']) ? explode('/',$_POST['mirroir']) : explode('/','ftp.fr.debian.org/debian');
$mirror['hostname'] = $mirroir[0];
//$mirror['directory'] = $mirroir[1];
$mirror['directory'] = "/depot/debian";
//=========================

//=========================
// Architecture
$arch = isset($_POST['arch']) ? $_POST['arch'] : 'i386';
//=========================

//=========================
// Partitionnement
$fdisk=isset($_POST['fdisk']) ? $_POST['fdisk'] : 1; // Au cas où l'admin a désactivé Javascript, on installe dans une partition libre ...
//=========================

//=========================
// Serveur de temps NTP

$ntpserv = isset($_POST['ntpserv']) ? $_POST['ntpserv'] : 'ntp.ac-creteil.fr';

//$sql="SELECT value FROM params WHERE name='dhcp_ntp';";
//$res=mysql_query($sql);
//if(mysql_num_rows($res)>0) {
//	$lig=mysql_fetch_object($res);
//	if($lig->value!="") {$dhcp_ntp=$lig->value;}
//	// Il faudrait contrôler que l'adresse est valide, non?
//}
//=========================

//=========================
// Mot de passe root
$root_pass=isset($_POST['root_mdp']) ? md5($_POST['root_mdp']) : md5($xppass);
//=========================

//=========================
// Nouvel utilisateur
$newuser_name=isset($_POST['newuser_name']) ? $_POST['newuser_name'] : "enseignant";
$newuser_pass=isset($_POST['newuser_pass']) ? md5($_POST['newuser_pass']) : md5(enseignant);
//=========================

//=========================
// Mot de passe grub
$grub_pass=isset($_POST['grub_mdp']) ? md5($_POST['grub_mdp']) : md5($xppass);
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

echo "<p>";

for($i=0;$i<count($id_machine);$i++) {
	$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine[$i]."';";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)==0) {
		echo "<span style='color:red;'>La machine d'identifiant $id_machine[$i] n'existe pas dans 'se3_dhcp'.</span><br />\n";
	}
	else {

		$lig=mysql_fetch_object($res);
		$nom_machine=$lig->name;

		// On écrit le fichier preseed dans le bon dossier
		$dossier_preseed="/var/www/se3/tmp/";
		//$dossier_unattend_txt="/var/www/preseeds";
		$fu=fopen($dossier_preseed.$nom_machine."_preseed.cfg","w+");
		if(!$fu) {
			echo "<p>ERREUR lors de la cr&eacute;ation de ".$dossier_preseed."/".$nom_machine."_preseed.cfg</p>\n";
			include ("pdp.inc.php");
			die();
		}
		fwrite($fu,"##########
#### Fichier de réponses pour l'installation de Debian Wheezy

#############
# langue et pays
d-i localechooser/shortlist	select	FR
d-i debian-installer/locale string fr_FR.UTF-8
d-i debian-installer/language string fr
d-i debian-installer/country string FR

# clavier
d-i console-keymaps-at/keymap select fr-latin9
d-i debian-installer/keymap string fr-latin9
d-i console-setup/modelcode string pc105
d-i console-setup/layoutcode string fr


### 2. Configuration du réseau avec le serveur DHCP du SE3
#############
# choix automatique de l'interface
d-i netcfg/choose_interface select auto

# À décommenter quand le serveur dhcp est lent et que l'installateur s'arrête pour l'attendre
d-i netcfg/dhcp_timeout string 60

# Si pour le réseau ou pour un autre matériel vous avez besoin d'un microprogramme
# ( firmware ) non libre, vous pouvez forcer l'installateur à  le télécharger,
# en évitant la demande de confirmation.
d-i hw-detect/load_firmware boolean true


### 3. Configuration du mirroir : utilisation du mirroir local se3 (apt-cacher-ng)
#############
d-i mirror/country string manual
d-i mirror/http/hostname string $se3ip:9999
d-i mirror/http/directory string /debian
d-i mirror/http/proxy string



### 4. Configuration du fuseau horaire : serveur de temps du Slis
#############
# réglage de l'horloge matérielle sur UTC et du fuseau horaire
d-i clock-setup/utc boolean true
d-i time/zone string Europe/Paris

# autorisation de l'utilisation de NTP pour régler l'horloge pendant l'installation avec le serveur ntp du Slis
d-i clock-setup/ntp boolean true
d-i clock-setup/ntp-server string $ntpserv

### 5. Partitionnement du disque dur
#############\n");

if($fdisk==0) {
    // On ecrase tout !
    // une root 15/20Go , une swap qq go au max et une sauvegarde pour le reste...

    fwrite($fu,"d-i partman-auto/automatically_partition string regular
d-i partman-auto/select_disk select /dev/discs/disc0/disc
d-i partman-auto/method string regular
d-i partman-lvm/confirm boolean true
d-i partman-auto/purge_lvm_from_device boolean true
d-i partman-auto/expert_recipe_file string /hd-media/recipe
d-i partman-auto/expert_recipe string                         \
      cl-recette ::                                          \
            10000 15000 20000 ext4                                \
                      \$primary{ } \$bootable{ }              \
                      method{ format } format{ }              \
                      use_filesystem{ } filesystem{ ext4 }    \
                      mountpoint{ / }                     \
       .                                               		\
            100% 200% 400% linux-swap               \
                      \$primary{ }                             \
                      method{ swap } format{ }                \
	.                                               		\
            15000 20000 400000  ext4                            \
                      \$primary{ }                             \
                      method{ format } format{ }              \
                      use_filesystem{ } filesystem{ ext4 }    \
                      mountpoint{ /sav }	\
		.		       
d-i partman-auto/choose_recipe select cl-recette
d-i partman-partitioning/confirm_write_new_label boolean true
d-i partman/alignment select cylinder
d-i partman/confirm boolean true
d-i partman/choose_partition select Finish partitioning and write changes to disk
d-i partman/confirm boolean true
d-i partman/confirm_nooverwrite boolean true");
} else {
   // on installe sur un espace libre pré-existant
    fwrite($fu,"d-i partman-auto/init_automatically_partition select Assisté - utiliser le plus grand espace disponible
        
d-i partman-auto/choose_recipe select atomic


# choix du format ext4
d-i partman/default_filesystem string ext4

# partitionnement automatique sans demander de confirmation
d-i partman/confirm_write_new_label boolean true
d-i partman/choose_partition select finish
d-i partman/confirm boolean true
d-i partman/confirm_nooverwrite boolean true");
    
    
}		
		fwrite($fu,"
### 6. Configuration des comptes Root et utilisateur
#############
# Création du compte root (false => non, true => oui)
d-i passwd/root-login boolean true

d-i passwd/root-password-crypted password ".$root_pass."

# Création d'un compte utilisateur normal.
d-i passwd/user-fullname string ".$newuser_name."
d-i passwd/username string ".$newuser_name."

d-i passwd/user-password-crypted password ".$newuser_pass."


### 7. Configuration d'Apt
# le fichier /etc/apt/sources.list sera reconfiguré après l'installation
# à l'aide d'un script de post-installation
#############
# Vous pouvez installer des logiciels des distributions non-free et contrib.
d-i apt-setup/non-free boolean true
d-i apt-setup/contrib boolean true

# Décommentez cette ligne si vous n'utilisez pas de miroir sur le réseau.
#d-i apt-setup/use_mirror boolean false

# Choisissez les services de mise à jour et les miroirs à utiliser.
# Les valeurs ci-après sont les valeurs par défaut :
d-i apt-setup/services-select multiselect security
# d-i apt-setup/security_host string security.debian.org
d-i apt-setup/security_host string $se3ip:9999/security.debian.org




### 8. Choix des paquets
#############");
// 
//Pour le moment xfce uniquement mais il faudrait proposer gnome et lxde

            
# choix du paquet gnome-desktop
#tasksel tasksel/first multiselect gnome-desktop
# installation d'un serveur ssh (administration distante de la machine)
#d-i pkgsel/include string openssh-server 

fwrite($fu,"
# choix du paquet xfce
tasksel tasksel/first multiselect standard, print-server
tasksel tasksel/desktop multiselect xfce4

# installation d'un serveur ssh (administration distante de la machine)
d-i pkgsel/include string openssh-server ldap-utils zip unzip tree screen vim vlc ssmtp ntp xfce4 gdm3 iceweasel iceweasel-l10n-fr evince geogebra leafpad xterm

# Sélection du pack de langues
d-i pkgsel/language-packs multiselect fr, en, es, de



# Gestion des mises à  jour avec 3 possibilités prédéfinies :
# - \"none\" => pas de mise à  jour automatique
# - \"unattended-upgrades\" => installe les mises à  jour de sécurité automatiquement
# - \"landscape\" => manage system with Landscape
d-i pkgsel/update-policy select unattended-upgrades

# Ne pas envoyer de rapport d'installation
popularity-contest popularity-contest/participate boolean false


### 9. Installation du programme d'amorçage GRUB
#############
# Installation automatique sur le MBR si aucun autre système n'est détecté
d-i grub-installer/only_debian boolean true

# S'il reconnaît un système d'exploitation, vous en serez informé
# et l'installateur configurera Grub pour pouvoir démarrer aussi bien ce système que Debian
d-i grub-installer/with_other_os boolean true

# Mot de passe optionnel pour Grub, en clair...
#d-i grub-installer/password password r00tme
#d-i grub-installer/password-again password r00tme
# ... ou chiffré sans confirmation avec MD5 hash, voir grub-md5-crypt(8)
# pour le chiffrage, utiliser la commande suivante dans une console
# printf \"uSerpass\" | mkpasswd -s -m md5\n");
		
	// A faire !!!
                if($fdisk==10)
			fwrite($fu,"#");
		fwrite($fu,"d-i grub-installer/password-crypted password ".$grub_pass."\n");
		fwrite($fu,"
### 10. Configuration de X (gestion de l'affichage)
#############
# Détection automatique du moniteur.
xserver-xorg xserver-xorg/autodetect_monitor boolean true
xserver-xorg xserver-xorg/config/monitor/selection-method \
       select medium
xserver-xorg xserver-xorg/config/monitor/mode-list \
       select 1024x768 @ 60 Hz


### 11. Exécution d'une commande avant la fin de l'installation
# Cette commande est exécutée juste avant que l'installation ne se termine,
# quand le répertoire /target est encore utilisable.
#############
# À décommenter pour que le script post_installation.sh soit lancé au 1er redémarrage de la machine
# il faudra rajouter à la fin du script la suppression de ce fichier?
d-i preseed/late_command string wget http://$se3ip/install/post-install_debian_wheezy.sh; \
wget http://$se3ip/install/params.sh; \
wget http://$se3ip/install/mesapplis-debian.txt; \
wget http://$se3ip/install/bashrc; \
wget http://$se3ip/install/inittab; \
chmod +x ./post-install_debian_wheezy.sh ./params.sh; \
mkdir /target/root/bin; \
cp params.sh mesapplis-debian.txt post-install_debian_wheezy.sh /target/root/bin/; \
cp bashrc /target/root/.bashrc; \
cp /target/etc/inittab /target/root/bin/inittab.orig; \
cp inittab /target/etc/; \
in-target update-rc.d gdm3 remove; \
cp /target/etc/inittab /target/root/bin/


### 12. Fin de l'installation
d-i finish-install/reboot_in_progress note\n");

		fclose($fu);
		#Copie du fichier dans /var/www/se3/tmp/ accessible depuis l'url http://se3ip:909/tmp/
		//shell_exec("/bin/cp ".$dossier_unattend_txt.$nom_machine."_preseed.cfg  /var/www/se3/tmp/");
	}
}

echo "<p>G&eacute;n&eacute;ration des fichiers dans /tftpboot/pxelinux.cfg/ pour l'installation automatique<br />\n";

// BOUCLE SUR LA LISTE DES $id_machine[$i]

// Numéro de l'opération de remontée de rapport:
$num_op=get_free_se3_action_tftp_num_op();
for($i=0;$i<count($id_machine);$i++) {
	$sql="SELECT * FROM se3_dhcp WHERE id='".$id_machine[$i]."';";
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
//Ajouter ici le domaine local et l'url du preseed à  passer à  pxe_gen_cfg_debian.sh
// domaine fait au début du script
		$url_du_preseed="http://".$se3ip.":909/tmp/".$nom_machine."_preseed.cfg";

		echo "G&eacute;n&eacute;ration pour $nom_machine : ";

		$corrige_mac=strtolower(strtr($mac_machine,":","-"));

		$chemin="/usr/share/se3/scripts";
		$resultat=exec("$chemin/pxe_gen_cfg.sh 'install_linux' '$corrige_mac' '$ip_machine' '$nom_machine'  '$url_du_preseed' '$arch'", $retour);

		if(count($retour)>0){
			echo "<span style='color:red;'>ECHEC de la g&eacute;n&eacute;ration du fichier</span><br />\n";
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
	type='auto_linux',
	num_op='$num_op',
	infos='';";
			$insert=mysql_query($sql);
			if(!$insert) {
echo "<span style='color:red;'>ECHEC de l'enregistrement dans 'se3_tftp_action'</span><br />\n";
$temoin_erreur="y";
			}

			if($temoin_erreur=="n") {
echo "<span style='color:green;'>OK</span>\n";
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


// On n'affiche le fichier que pour le dernier (à  titre d'info):
if(isset($corrige_mac)) {
	$fich=fopen("/tftpboot/pxelinux.cfg/01-$corrige_mac","r");
	if($fich) {
		echo "<p>Pour information, voici le contenu du fichier g&eacute;n&eacute;r&eacute;:<br />\n";
		echo "<pre style='border:1px solid black; color:green;'>";
		while(!feof($fich)) {
			$ligne=fgets($fich,4096);
			echo htmlentities($ligne);
		}
		echo "</pre>\n";
		fclose($fich);
	}
	else {
		echo "<p style='color:red;'>Il n'a pas &eacute;t&eacute; possible d'ouvrir le fichier /tftpboot/pxelinux.cfg/01-$corrige_mac</p>\n";
	}
}

}

?>
