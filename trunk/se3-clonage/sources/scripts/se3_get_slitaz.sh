#!/bin/bash

# $Id$
# Auteur: Stephane Boireau
# Derniere modification: 30/11/2011

# - telecharger SliTaz depuis http://wawadeb.crdp.ac-caen.fr/iso/slitaz/

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

src="http://wawadeb.crdp.ac-caen.fr/iso/slitaz"

tmp="/var/se3/tmp_slitaz_${timedate}"
mkdir -p "$tmp"
chmod 700 $tmp

# ========================================

# Valeurs des versions en place recuperees de se3db.params:
version_noyo_slitaz_en_place="$slitaz_noyo_version"
version_rootfs_slitaz_en_place="$slitaz_roofs_version"

# ========================================

t=$(echo "$*" | grep "check_version")
if [ -n "$t" ]; then
	cd $tmp
	wget $src/versions.txt > /dev/null 2>&1
	if [ "$?" = 0 -a -e versions.txt ]; then
		version_noyo_slitaz_en_ligne=$(grep ";bzImage$" $tmp/versions.txt | cut -d";" -f1)
		version_rootfs_slitaz_en_ligne=$(grep ";rootfs.gz$" $tmp/versions.txt | cut -d";" -f1)

		# Pour le premier lancement: mise en place du nouveau dispositif
		if [ -z "$version_noyo_slitaz_en_place" ]; then
			md5_en_ligne=$(grep ";bzImage$" $tmp/versions.txt | cut -d";" -f2)
			if [ -e "/tftpboot/bzImage" ]; then
				md5_en_place=$(md5sum /tftpboot/bzImage|cut -d" " -f1)
				if [ "$md5_en_place" = "$md5_en_ligne" ]; then
					slitaz_noyo_version=$version_noyo_slitaz_en_ligne
					SETMYSQL slitaz_noyo_version "$slitaz_noyo_version" "version actuelle du noyau SliTaz" 7
					version_noyo_slitaz_en_place=$version_noyo_slitaz_en_ligne
				fi
			fi

		fi

		if [ -z "$version_rootfs_slitaz_en_place" ]; then
			md5_en_ligne=$(grep ";rootfs.gz$" $tmp/versions.txt | cut -d";" -f2)
			if [ -e "/tftpboot/rootfs.gz" ]; then
				md5_en_place=$(md5sum /tftpboot/rootfs.gz|cut -d" " -f1)
				if [ "$md5_en_place" = "$md5_en_ligne" ]; then
					slitaz_roofs_version=$version_rootfs_slitaz_en_ligne
					SETMYSQL slitaz_roofs_version "$slitaz_roofs_version" "version actuelle du rootfs SliTaz" 7
					version_rootfs_slitaz_en_place=$version_rootfs_slitaz_en_ligne
				fi
			fi

		fi


		temoin_erreur="n"
		temoin_fichier_manquant="n"
		if [ ! -e "/tftpboot/bzImage" ]; then
			temoin_fichier_manquant="y"
			version_noyo_slitaz_en_place="<span style='color:red'>Absent</span>"
		else
			md5_en_place=$(md5sum /tftpboot/bzImage|cut -d" " -f1)
			md5_en_ligne=$(grep ";bzImage$" $tmp/versions.txt | cut -d";" -f2)
	
			if [ "$md5_en_ligne" != "$md5_en_place" ]; then
				version_noyo_slitaz_en_place="<span style='color:red'>Somme MD5 incorrecte</span>"
				temoin_erreur="y"
			fi
		fi

		if [ ! -e "/tftpboot/rootfs.gz" ]; then
			temoin_fichier_manquant="y"
			version_rootfs_slitaz_en_place="<span style='color:red'>Absent</span>"
		else
			md5_en_place=$(md5sum /tftpboot/rootfs.gz|cut -d" " -f1)
			md5_en_ligne=$(grep ";rootfs.gz$" $tmp/versions.txt | cut -d";" -f2)
	
			if [ "$md5_en_ligne" != "$md5_en_place" ]; then
				version_rootfs_slitaz_en_place="<span style='color:red'>Somme MD5 incorrecte</span>"
				temoin_erreur="y"
			fi
		fi

		if [ "$temoin_erreur" != "y" -a "$temoin_fichier_manquant" != "y" -a "$version_noyo_slitaz_en_ligne" = "$version_noyo_slitaz_en_place" -a "$version_rootfs_slitaz_en_ligne" = "$version_rootfs_slitaz_en_place" ]; then
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
	<th>Noyau SliTaz</th>
	<td>$version_noyo_slitaz_en_place</td>
	<td>$version_noyo_slitaz_en_ligne</td>
</tr>
<tr>
	<th>rootfs SliTaz</th>
	<td>$version_rootfs_slitaz_en_place</td>
	<td>$version_rootfs_slitaz_en_ligne</td>
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
echo "Mise en place des fichiers utiles pour SliTaz en boot PXE."
if [ "$mode" = "cmdline" ]; then
	echo -e "$COLCMD"
else
	echo "</h3>"
fi

# Telecharger
cd $tmp
wget $src/versions.txt
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
fi

version_noyo_slitaz_en_ligne=$(grep ";bzImage$" $tmp/versions.txt | cut -d";" -f1)
version_rootfs_slitaz_en_ligne=$(grep ";rootfs.gz$" $tmp/versions.txt | cut -d";" -f1)

# On controle si des fichiers SliTaz sont deja en place
if [ -e "/tftpboot/bzImage" -a -e "/tftpboot/rootfs.gz" ]; then

	# Pour le premier lancement: mise en place du nouveau dispositif
	if [ -z "$version_noyo_slitaz_en_place" ]; then
		md5_en_ligne=$(grep ";bzImage$" $tmp/versions.txt | cut -d";" -f2)
		if [ -e "/tftpboot/bzImage" ]; then
			md5_en_place=$(md5sum /tftpboot/bzImage|cut -d" " -f1)
			if [ "$md5_en_place" = "$md5_en_ligne" ]; then
				slitaz_noyo_version=$version_noyo_slitaz_en_ligne
				SETMYSQL slitaz_noyo_version "$slitaz_noyo_version" "version actuelle du noyau SliTaz" 7
				version_noyo_slitaz_en_place=$version_noyo_slitaz_en_ligne
			fi
		fi

	fi

	if [ -z "$version_rootfs_slitaz_en_place" ]; then
		md5_en_ligne=$(grep ";rootfs.gz$" $tmp/versions.txt | cut -d";" -f2)
		if [ -e "/tftpboot/rootfs.gz" ]; then
			md5_en_place=$(md5sum /tftpboot/rootfs.gz|cut -d" " -f1)
			if [ "$md5_en_place" = "$md5_en_ligne" ]; then
				slitaz_roofs_version=$version_rootfs_slitaz_en_ligne
				SETMYSQL slitaz_roofs_version "$slitaz_roofs_version" "version actuelle du rootfs SliTaz" 7
				version_rootfs_slitaz_en_place=$version_rootfs_slitaz_en_ligne
			fi
		fi

	fi


	if [ -e "/tftpboot/bzImage" ]; then
		md5_en_place=$(md5sum /tftpboot/bzImage|cut -d" " -f1)
		md5_en_ligne=$(grep ";bzImage$" $tmp/versions.txt | cut -d";" -f2)

		if [ "$md5_en_ligne" != "$md5_en_place" ]; then
			version_noyo_slitaz_en_place=""
		fi
	fi

	if [ -e "/tftpboot/rootfs.gz" ]; then
		md5_en_place=$(md5sum /tftpboot/rootfs.gz|cut -d" " -f1)
		md5_en_ligne=$(grep ";rootfs.gz$" $tmp/versions.txt | cut -d";" -f2)

		if [ "$md5_en_ligne" != "$md5_en_place" ]; then
			version_rootfs_slitaz_en_place=""
		fi
	fi


	# On controle la version des fichiers
	if [ "$version_noyo_slitaz_en_ligne" = "$version_noyo_slitaz_en_place" -a "$version_rootfs_slitaz_en_ligne" = "$version_rootfs_slitaz_en_place" ]; then

		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "Les fichiers de bzImage et rootfs.gz de SliTaz sont deja les plus recents; On ne les re-telecharge pas."
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi
		temoin_telech_requis="n"
	else
		# La version a change.
		temoin_telech_requis="y"
	fi
else
	# Il manque au moins un fichier, on telecharge pour mettre a jour
	temoin_telech_requis="y"
fi

if [ "$temoin_telech_requis" = "y" ]; then

	md5_en_place=""
	md5_en_ligne=""
	if [ -e "/tftpboot/bzImage" ]; then
		md5_en_place=$(md5sum /tftpboot/bzImage|cut -d" " -f1)
		md5_en_ligne=$(grep ";bzImage$" $tmp/versions.txt | cut -d";" -f2)
	fi

	if [ ! -e "/tftpboot/bzImage" -o "$md5_en_ligne" != "$md5_en_place" -o "$version_noyo_slitaz_en_ligne" != "$version_noyo_slitaz_en_place" ]; then
		wget $src/bzImage
		if [ "$?" != "0" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ERREUR lors du telechargement de $src/bzImage"
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
			echo "SUCCES du telechargement de $src/bzImage"
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
			fi
	
			md5_telech=$(md5sum bzImage|cut -d" " -f1)
			md5_en_ligne=$(grep ";bzImage$" $tmp/versions.txt | cut -d";" -f2)
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
				exit
			fi
		fi

		liste_fichiers_a_copier="$liste_fichiers_a_copier bzImage"
	fi


	md5_en_place=""
	md5_en_ligne=""
	if [ -e "/tftpboot/rootfs.gz" ]; then
		md5_en_place=$(md5sum /tftpboot/rootfs.gz|cut -d" " -f1)
		md5_en_ligne=$(grep ";rootfs.gz$" $tmp/versions.txt | cut -d";" -f2)
	fi

	if [ ! -e "/tftpboot/rootfs.gz" -o "$md5_en_ligne" != "$md5_en_place" -o "$version_rootfs_slitaz_en_ligne" != "$version_rootfs_slitaz_en_place" ]; then

		wget $src/rootfs.gz
		if [ "$?" != "0" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ERREUR lors du telechargement de $src/rootfs.gz"
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
			echo "SUCCES du telechargement de $src/rootfs.gz"
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
			fi

			md5_telech=$(md5sum rootfs.gz|cut -d" " -f1)
			md5_en_ligne=$(grep ";rootfs.gz$" $tmp/versions.txt | cut -d";" -f2)
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
				exit
			fi
		fi
		liste_fichiers_a_copier="$liste_fichiers_a_copier rootfs.gz"
	fi


	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLTXT"
	else
		echo "<b>"
	fi
	echo "Copie des fichiers vers leur emplacement..."
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLCMD"
	else
		echo "</b>"
	fi
	cp -fv $liste_fichiers_a_copier /tftpboot/

	if [ "$?" != "0"  ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLERREUR"
		else
			echo "<span style='color:red'>"
		fi

		echo "ERREUR lors de la copie"

		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
			echo "</pre>"
		fi
	else
		SETMYSQL slitaz_noyo_version "$version_noyo_slitaz_en_ligne" "version actuelle du noyau SliTaz" 7
		SETMYSQL slitaz_roofs_version "$version_rootfs_slitaz_en_ligne" "version actuelle du rootfs SliTaz" 7
	fi
fi

rm -fr $tmp

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
