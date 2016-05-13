#!/bin/bash

# $Id$
# Auteur: Stephane Boireau
# Dernière modification: 16/03/2015

# Ajout en visudo:
# Cmnd_Alias SE3CLONAGE=/usr/share/se3/scripts/se3_tftp_boot_pxe.sh,/usr/share/se3/scripts/se3_get_sysresccd.sh,/usr/share/se3/scripts/se3_get_clonezilla.sh

. /usr/share/se3/includes/config.inc.sh -d
. /usr/share/se3/includes/functions.inc.sh

COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu

COLTXT="\033[0;37m"     # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert

COLCMD="\033[1;37m"     # Blanc

COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan

# Parametres
timestamp=$(date +%s)
timedate=$(date "+%Y%m%d_%H%M%S")

src="http://wawadeb.crdp.ac-caen.fr/iso/clonezilla"

tmp="/var/se3/tmp_clonezilla_${timedate}"
mkdir -p "$tmp"
chmod 700 $tmp

# ========================================

t=$(echo "$*" | grep "check_version")
if [ -n "$t" ]; then
	cd $tmp
	wget -O versions.txt $src/versions.txt? > /dev/null 2>&1
	if [ "$?" = 0 -a -e versions.txt ]; then
		version_clonezilla_en_ligne=$(grep ";clonezilla.zip$" $tmp/versions.txt | cut -d";" -f1)
		version_clonezilla64_en_ligne=$(grep ";clonezilla64.zip$" $tmp/versions.txt | cut -d";" -f1)

		version_clonezilla_en_place="$clonezilla_version"
		version_clonezilla64_en_place="$clonezilla64_version"

		if [ "$version_clonezilla_en_ligne" = "$version_clonezilla_en_place" -a "$version_clonezilla64_en_ligne" = "$version_clonezilla64_en_place" ]; then
			echo "<p><span style='color:green'>Dispositif &agrave; jour</span></p>";
		else
			echo "<p><span style='color:red'>Mise &agrave; jour disponible</span></p>";
		fi

		echo "<table class='crob'>
<tr>
	<th>&nbsp;</th>
	<th>Sur votre SE3</th>
	<th>En ligne</th>
</tr>
<tr>
	<th>Clonezilla</th>
	<td>$version_clonezilla_en_place</td>
	<td>$version_clonezilla_en_ligne</td>
</tr>
<tr>
	<th>Clonezilla64</th>
	<td>$version_clonezilla64_en_place</td>
	<td>$version_clonezilla64_en_ligne</td>
</tr>
</table>";

	else
		echo "<p><span style='color:red'>ECHEC du telechargement du fichier des versions.</span></p>"
	fi

	exit
fi

# ========================================

t=$(echo "$*" | grep "mode=html")
if [ -z "$t" ]; then
	mode="cmdline"
else
	mode="html"
fi

if [ "$mode" = "cmdline" ]; then
	echo -e "$COLTXT"
else
	echo "<pre>"
	echo "<h3>"
fi
echo "Mise en place des fichiers utiles pour Clonezilla en boot PXE."
if [ "$mode" = "cmdline" ]; then
	echo -e "$COLCMD"
else
	echo "</h3>"
fi

mkdir -p /tftpboot
# Emplacement des fichiers telecharges par Clonezilla lors du boot
depot_clonezilla="/var/se3/clonezilla"
mkdir -p "$depot_clonezilla"
chmod 755 "$depot_clonezilla"
ln -sf $depot_clonezilla /tftpboot/
ln -sf $depot_clonezilla /var/www/

#============================
# Script test
# Si le script existe deja, il n'est pas ecrase.
# C'est une possibilite pour tester...
if [ ! -e $depot_clonezilla/script_clonezilla_test.sh ]; then
	echo '#!/bin/bash

echo "Test clonezilla: $(date +%Y%m%d%H%M%S)">>/tmp/test_clonezilla.txt'>$depot_clonezilla/script_clonezilla_test.sh
fi
#============================

depot_clonezilla64="/var/se3/clonezilla64"
mkdir -p "$depot_clonezilla64"
chmod 755 "$depot_clonezilla64"
ln -sf $depot_clonezilla64 /tftpboot/
ln -sf $depot_clonezilla64 /var/www/

cpt=0

#===================================================================

cd $tmp
wget -O versions.txt $src/versions.txt?
if [ "$?" != "0" ]; then
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLERREUR"
	else
		echo "<span style='color:red'>"
	fi
	echo "ERREUR lors du telechargement de $src/versions.txt"
	echo "ABANDON."
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLTXT"
	else
		echo "</span>"
		echo "</pre>"
	fi
	exit
else
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLINFO"
	else
		echo "<span style='color:green'>"
	fi
	echo "SUCCES du telechargement de $src/versions.txt"
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLTXT"
	else
		echo "</span>"
	fi
	cpt=$(($cpt+1))
fi
version_clonezilla_en_place="$clonezilla_version"
version_clonezilla_en_ligne=$(grep ";clonezilla.zip$" $tmp/versions.txt | cut -d";" -f1)

if [ -e "$depot_clonezilla/vmlinuz" -a -e "$depot_clonezilla/initrd.img" -a -e "$depot_clonezilla/filesystem.squashfs" ]; then
	# On controle la version Clonezilla
	version_clonezilla_en_place="$srcd_version"
	version_clonezilla_en_ligne=$(grep ";clonezilla.zip$" $tmp/versions.txt | cut -d";" -f1)
	if [ "$version_clonezilla_en_place" = "$version_clonezilla_en_ligne" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "Les fichiers de Clonezilla sont deja les plus recents; On ne re-telecharge pas l'ISO."
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi
		temoin_clonezilla="n"
	else
		# La version a change.
		temoin_clonezilla="y"
	fi
else
	# Il manque au moins un fichier, on telecharge pour mettre a jour
	temoin_clonezilla="y"
fi

if [ "$temoin_clonezilla" = "y" ]; then
	wget -O clonezilla.zip $src/clonezilla.zip?
	if [ "$?" != "0" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLERREUR"
		else
			echo "<span style='color:red'>"
		fi
		echo "ERREUR lors du telechargement de $src/clonezilla.zip"
		echo "ABANDON."
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
			echo "</pre>"
		fi
		exit
	else
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "SUCCES du telechargement de $src/clonezilla.zip"
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi

		md5_telech=$(md5sum clonezilla.zip|cut -d" " -f1)
		md5_en_ligne=$(grep ";clonezilla.zip$" $tmp/versions.txt | cut -d";" -f2)
		if [ "$md5_telech" != "$md5_en_ligne" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ANOMALIE: La somme MD5 ne coincide pas: $md5_en_ligne en ligne et $md5_telech telecharge."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
				echo "</pre>"
			fi
			# Pour ne pas remplacer les fichiers par la suite.
			temoin_clonezilla="n"
		else
			SETMYSQL clonezilla_version "$version_clonezilla_en_ligne" "version actuelle de Clonezilla" 7

			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "<b>"
			fi
			echo "Extraction du ZIP..."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLCMD"
			else
				echo "</b>"
			fi
			mkdir -p $tmp/clonezilla
			cd $tmp/clonezilla
			unzip ../clonezilla.zip

			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "<b>"
			fi
			echo "Copie des fichiers du ZIP vers leur emplacement..."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLCMD"
			else
				echo "</b>"
			fi

			cp -fv live/vmlinuz $depot_clonezilla/
			cp -fv live/initrd.img $depot_clonezilla/
			cp -fv live/filesystem.squashfs $depot_clonezilla/

			# Nettoyage
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "<b>"
			fi
			echo "Nettoyage"
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLCMD"
			else
				echo "</b>"
			fi
			cd $tmp
			rm -fr clonezilla
			#rm -fr $tmp
		fi
		cpt=$(($cpt+1))
	fi
fi



#===================================================================

version_clonezilla64_en_place="$clonezilla64_version"
version_clonezilla64_en_ligne=$(grep ";clonezilla64.zip$" $tmp/versions.txt | cut -d";" -f1)

if [ -e "$depot_clonezilla64/vmlinuz" -a -e "$depot_clonezilla64/initrd.img" -a -e "$depot_clonezilla64/filesystem.squashfs" ]; then
	# On controle la version Clonezilla64
	version_clonezilla64_en_place="$srcd_version"
	version_clonezilla64_en_ligne=$(grep ";clonezilla64.zip$" $tmp/versions.txt | cut -d";" -f1)
	if [ "$version_clonezilla64_en_place" = "$version_clonezilla64_en_ligne" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "Les fichiers de Clonezilla64 sont deja les plus recents; On ne re-telecharge pas l'ISO."
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi
		temoin_clonezilla64="n"
	else
		# La version a change.
		temoin_clonezilla64="y"
	fi
else
	# Il manque au moins un fichier, on telecharge pour mettre a jour
	temoin_clonezilla64="y"
fi

if [ "$temoin_clonezilla64" = "y" ]; then
	wget -O clonezilla64.zip $src/clonezilla64.zip?
	if [ "$?" != "0" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLERREUR"
		else
			echo "<span style='color:red'>"
		fi
		echo "ERREUR lors du telechargement de $src/clonezilla64.zip"
		echo "ABANDON."
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
			echo "</pre>"
		fi
		exit
	else
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "SUCCES du telechargement de $src/clonezilla64.zip"
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi

		md5_telech=$(md5sum clonezilla64.zip|cut -d" " -f1)
		md5_en_ligne=$(grep ";clonezilla64.zip$" $tmp/versions.txt | cut -d";" -f2)
		if [ "$md5_telech" != "$md5_en_ligne" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ANOMALIE: La somme MD5 ne coincide pas: $md5_en_ligne en ligne et $md5_telech telecharge."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
				echo "</pre>"
			fi
			# Pour ne pas remplacer les fichiers par la suite.
			temoin_clonezilla64="n"
		else
			SETMYSQL clonezilla64_version "$version_clonezilla64_en_ligne" "version actuelle de Clonezilla64" 7

			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "<b>"
			fi
			echo "Extraction du ZIP..."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLCMD"
			else
				echo "</b>"
			fi
			mkdir -p $tmp/clonezilla64
			cd $tmp/clonezilla64
			unzip ../clonezilla64.zip

			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "<b>"
			fi
			echo "Copie des fichiers du ZIP vers leur emplacement..."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLCMD"
			else
				echo "</b>"
			fi

			cp -fv live/vmlinuz $depot_clonezilla64/
			cp -fv live/initrd.img $depot_clonezilla64/
			cp -fv live/filesystem.squashfs $depot_clonezilla64/

			# Nettoyage
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "<b>"
			fi
			echo "Nettoyage"
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLCMD"
			else
				echo "</b>"
			fi
			cd $tmp
			rm -fr clonezilla64
			#rm -fr $tmp
			
			
			
		fi
		cpt=$(($cpt+1))
	fi
fi

#rm -fr $tmp


if [ "${tftp_aff_menu_pxe}" != "y" ]; then
	/usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh 'standard'
else
	/usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh 'menu'
fi

if [ "$mode" = "cmdline" ]; then
	echo -e "$COLTITRE"
else
	echo "<b>"
fi
echo "Termine."
if [ "$mode" = "cmdline" ]; then
	echo -e "$COLTXT"
else
	echo "</b>"
	echo "</pre>"
fi

