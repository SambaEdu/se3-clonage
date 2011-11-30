#!/bin/bash

# $Id$
# Auteur: Stephane Boireau
# Derniere modification: 30/11/2011

# Ajout en visudo:
# Cmnd_Alias SE3CLONAGE=/usr/share/se3/scripts/se3_tftp_boot_pxe.sh,/usr/share/se3/scripts/se3_get_sysresccd.sh

# Coquille vide pour le moment
# - telecharger udpcast depuis http://wawadeb.crdp.ac-caen.fr/iso/udpcast/

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

src="http://wawadeb.crdp.ac-caen.fr/iso/udpcast"

tmp="/var/se3/tmp_udpcast_${timedate}"
mkdir -p "$tmp"
chmod 700 $tmp

# ========================================

# Valeurs des versions en place recuperees de se3db.params:
version_noyo_udpcast_en_place="$udpcast_noyo_version"
version_initrd_udpcast_en_place="$udpcast_initrd_version"
version_noyo_old_udpcast_en_place="$udpcast_noyo_old_version"
version_initrd_old_udpcast_en_place="$udpcast_initrd_old_version"

# ========================================

t=$(echo "$*" | grep "check_version")
if [ -n "$t" ]; then
	cd $tmp
	wget $src/versions.txt > /dev/null 2>&1

	#vmlu26
	#udprd
	#vmlu26.old
	#udprd.old

	if [ "$?" = 0 -a -e versions.txt ]; then
		version_noyo_udpcast_en_ligne=$(grep ";vmlu26$" $tmp/versions.txt | cut -d";" -f1)
		version_initrd_udpcast_en_ligne=$(grep ";udprd$" $tmp/versions.txt | cut -d";" -f1)
		version_noyo_old_udpcast_en_ligne=$(grep ";vmlu26.old$" $tmp/versions.txt | cut -d";" -f1)
		version_initrd_old_udpcast_en_ligne=$(grep ";udprd.old$" $tmp/versions.txt | cut -d";" -f1)


		# Pour le premier lancement: mise en place du nouveau dispositif
		if [ -z "$version_noyo_udpcast_en_place" ]; then
			md5_en_ligne=$(grep ";vmlu26$" $tmp/versions.txt | cut -d";" -f2)
			if [ -e "/tftpboot/vmlu26" ]; then
				md5_en_place=$(md5sum /tftpboot/vmlu26|cut -d" " -f1)
				if [ "$md5_en_place" = "$md5_en_ligne" ]; then
					udpcast_noyo_version=$version_noyo_udpcast_en_ligne
					SETMYSQL udpcast_noyo_version "$udpcast_noyo_version" "version actuelle du noyau udpcast" 7
					version_noyo_udpcast_en_place=$version_noyo_udpcast_en_ligne
				fi
			fi
		
		fi
		
		if [ -z "$version_initrd_udpcast_en_place" ]; then
			md5_en_ligne=$(grep ";udprd$" $tmp/versions.txt | cut -d";" -f2)
			if [ -e "/tftpboot/udprd" ]; then
				md5_en_place=$(md5sum /tftpboot/udprd|cut -d" " -f1)
				if [ "$md5_en_place" = "$md5_en_ligne" ]; then
					udpcast_initrd_version=$version_initrd_udpcast_en_ligne
					SETMYSQL udpcast_initrd_version "$udpcast_initrd_version" "version actuelle du initrd udpcast" 7
					version_initrd_udpcast_en_place=$version_initrd_udpcast_en_ligne
				fi
			fi
		
		fi
		
		
		if [ -z "$version_noyo_old_udpcast_en_place" ]; then
			md5_en_ligne=$(grep ";vmlu26.old$" $tmp/versions.txt | cut -d";" -f2)
			if [ -e "/tftpboot/vmlu26.old" ]; then
				md5_en_place=$(md5sum /tftpboot/vmlu26.old|cut -d" " -f1)
				if [ "$md5_en_place" = "$md5_en_ligne" ]; then
					udpcast_noyo_old_version=$version_noyo_old_udpcast_en_ligne
					SETMYSQL udpcast_noyo_old_version "$udpcast_noyo_old_version" "version old du noyau old udpcast" 7
					version_noyo_old_udpcast_en_place=$version_noyo_old_udpcast_en_ligne
				fi
			fi
		
		fi
		
		if [ -z "$version_initrd_old_udpcast_en_place" ]; then
			md5_en_ligne=$(grep ";udprd.old$" $tmp/versions.txt | cut -d";" -f2)
			if [ -e "/tftpboot/udprd.old" ]; then
				md5_en_place=$(md5sum /tftpboot/udprd.old|cut -d" " -f1)
				if [ "$md5_en_place" = "$md5_en_ligne" ]; then
					udpcast_initrd_old_version=$version_initrd_old_udpcast_en_ligne
					SETMYSQL udpcast_initrd_old_version "$udpcast_initrd_old_version" "version old du initrd udpcast" 7
					version_initrd_old_udpcast_en_place=$version_initrd_old_udpcast_en_ligne
				fi
			fi
		
		fi

		temoin_erreur="n"
		temoin_fichier_manquant="n"
		if [ ! -e "/tftpboot/vmlu26" ]; then
			temoin_fichier_manquant="y"
			version_noyo_udpcast_en_place="<span style='color:red'>Absent</span>"
		else
			md5_en_place=$(md5sum /tftpboot/vmlu26|cut -d" " -f1)
			md5_en_ligne=$(grep ";vmlu26$" $tmp/versions.txt | cut -d";" -f2)
	
			if [ "$md5_en_ligne" != "$md5_en_place" ]; then
				version_noyo_udpcast_en_place="<span style='color:red'>Somme MD5 incorrecte</span>"
				temoin_erreur="y"
			fi
		fi

		if [ ! -e "/tftpboot/udprd" ]; then
			temoin_fichier_manquant="y"
			version_initrd_udpcast_en_place="<span style='color:red'>Absent</span>"
		else
			md5_en_place=$(md5sum /tftpboot/udprd|cut -d" " -f1)
			md5_en_ligne=$(grep ";udprd$" $tmp/versions.txt | cut -d";" -f2)
	
			if [ "$md5_en_ligne" != "$md5_en_place" ]; then
				version_initrd_udpcast_en_place="<span style='color:red'>Somme MD5 incorrecte</span>"
				temoin_erreur="y"
			fi
		fi

		if [ ! -e "/tftpboot/vmlu26.old" ]; then
			temoin_fichier_manquant="y"
			version_noyo_old_udpcast_en_place="<span style='color:red'>Absent</span>"
		else
			md5_en_place=$(md5sum /tftpboot/vmlu26.old|cut -d" " -f1)
			md5_en_ligne=$(grep ";vmlu26.old$" $tmp/versions.txt | cut -d";" -f2)
	
			if [ "$md5_en_ligne" != "$md5_en_place" ]; then
				version_noyo_old_udpcast_en_place="<span style='color:red'>Somme MD5 incorrecte</span>"
				temoin_erreur="y"
			fi
		fi

		if [ ! -e "/tftpboot/udprd.old" ]; then
			temoin_fichier_manquant="y"
			version_initrd_old_udpcast_en_place="<span style='color:red'>Absent</span>"
		else
			md5_en_place=$(md5sum /tftpboot/udprd.old|cut -d" " -f1)
			md5_en_ligne=$(grep ";udprd.old$" $tmp/versions.txt | cut -d";" -f2)
	
			if [ "$md5_en_ligne" != "$md5_en_place" ]; then
				version_initrd_old_udpcast_en_place="<span style='color:red'>Somme MD5 incorrecte</span>"
				temoin_erreur="y"
			fi
		fi

		if [ "$temoin_erreur" != "y" -a "$temoin_fichier_manquant" != "y" -a "$version_noyo_udpcast_en_ligne" = "$version_noyo_udpcast_en_place" -a "$version_initrd_udpcast_en_ligne" = "$version_initrd_udpcast_en_place" -a "$version_noyo_old_udpcast_en_ligne" = "$version_noyo_old_udpcast_en_place" -a "$version_initrd_old_udpcast_en_ligne" = "$version_initrd_old_udpcast_en_place" ]; then
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
	<th>Noyau udpcast</th>
	<td>$version_noyo_udpcast_en_place</td>
	<td>$version_noyo_udpcast_en_ligne</td>
</tr>
<tr>
	<th>initrd udpcast</th>
	<td>$version_initrd_udpcast_en_place</td>
	<td>$version_initrd_udpcast_en_ligne</td>
</tr>
<tr>
	<th>Noyau old udpcast</th>
	<td>$version_noyo_old_udpcast_en_place</td>
	<td>$version_noyo_old_udpcast_en_ligne</td>
</tr>
<tr>
	<th>initrd old udpcast</th>
	<td>$version_initrd_old_udpcast_en_place</td>
	<td>$version_initrd_old_udpcast_en_ligne</td>
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
echo "Mise en place des fichiers utiles pour udpcast en boot PXE."
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

version_noyo_udpcast_en_ligne=$(grep ";vmlu26$" $tmp/versions.txt | cut -d";" -f1)
version_initrd_udpcast_en_ligne=$(grep ";udprd$" $tmp/versions.txt | cut -d";" -f1)
version_noyo_old_udpcast_en_ligne=$(grep ";vmlu26.old$" $tmp/versions.txt | cut -d";" -f1)
version_initrd_old_udpcast_en_ligne=$(grep ";udprd.old$" $tmp/versions.txt | cut -d";" -f1)

# On controle si des fichiers udpcast sont deja en place
if [ -e "/tftpboot/vmlu26" -a -e "/tftpboot/udprd" -a -e "/tftpboot/vmlu26.old" -a -e "/tftpboot/udprd.old" ]; then

	# Pour le premier lancement: mise en place du nouveau dispositif
	if [ -z "$version_noyo_udpcast_en_place" ]; then
		md5_en_ligne=$(grep ";vmlu26$" $tmp/versions.txt | cut -d";" -f2)
		if [ -e "/tftpboot/vmlu26" ]; then
			md5_en_place=$(md5sum /tftpboot/vmlu26|cut -d" " -f1)
			if [ "$md5_en_place" = "$md5_en_ligne" ]; then
				udpcast_noyo_version=$version_noyo_udpcast_en_ligne
				SETMYSQL udpcast_noyo_version "$udpcast_noyo_version" "version actuelle du noyau udpcast" 7
				version_noyo_udpcast_en_place=$version_noyo_udpcast_en_ligne
			fi
		fi
	
	fi
	
	if [ -z "$version_initrd_udpcast_en_place" ]; then
		md5_en_ligne=$(grep ";udprd$" $tmp/versions.txt | cut -d";" -f2)
		if [ -e "/tftpboot/udprd" ]; then
			md5_en_place=$(md5sum /tftpboot/udprd|cut -d" " -f1)
			if [ "$md5_en_place" = "$md5_en_ligne" ]; then
				udpcast_initrd_version=$version_initrd_udpcast_en_ligne
				SETMYSQL udpcast_initrd_version "$udpcast_initrd_version" "version actuelle du initrd udpcast" 7
				version_initrd_udpcast_en_place=$version_initrd_udpcast_en_ligne
			fi
		fi
	
	fi
	
	
	if [ -z "$version_noyo_old_udpcast_en_place" ]; then
		md5_en_ligne=$(grep ";vmlu26.old$" $tmp/versions.txt | cut -d";" -f2)
		if [ -e "/tftpboot/vmlu26.old" ]; then
			md5_en_place=$(md5sum /tftpboot/vmlu26.old|cut -d" " -f1)
			if [ "$md5_en_place" = "$md5_en_ligne" ]; then
				udpcast_noyo_old_version=$version_noyo_old_udpcast_en_ligne
				SETMYSQL udpcast_noyo_old_version "$udpcast_noyo_old_version" "version old du noyau old udpcast" 7
				version_noyo_old_udpcast_en_place=$version_noyo_old_udpcast_en_ligne
			fi
		fi
	
	fi
	
	if [ -z "$version_initrd_old_udpcast_en_place" ]; then
		md5_en_ligne=$(grep ";udprd.old$" $tmp/versions.txt | cut -d";" -f2)
		if [ -e "/tftpboot/udprd.old" ]; then
			md5_en_place=$(md5sum /tftpboot/udprd.old|cut -d" " -f1)
			if [ "$md5_en_place" = "$md5_en_ligne" ]; then
				udpcast_initrd_old_version=$version_initrd_old_udpcast_en_ligne
				SETMYSQL udpcast_initrd_old_version "$udpcast_initrd_old_version" "version old du initrd udpcast" 7
				version_initrd_old_udpcast_en_place=$version_initrd_old_udpcast_en_ligne
			fi
		fi
	
	fi



	if [ -e "/tftpboot/vmlu26" ]; then
		md5_en_place=$(md5sum /tftpboot/vmlu26|cut -d" " -f1)
		md5_en_ligne=$(grep ";vmlu26$" $tmp/versions.txt | cut -d";" -f2)

		if [ "$md5_en_ligne" != "$md5_en_place" ]; then
			version_noyo_udpcast_en_place=""
		fi
	fi

	if [ -e "/tftpboot/udprd" ]; then
		md5_en_place=$(md5sum /tftpboot/udprd|cut -d" " -f1)
		md5_en_ligne=$(grep ";udprd$" $tmp/versions.txt | cut -d";" -f2)

		if [ "$md5_en_ligne" != "$md5_en_place" ]; then
			version_initrd_udpcast_en_place=""
		fi
	fi

	if [ -e "/tftpboot/vmlu26.old" ]; then
		md5_en_place=$(md5sum /tftpboot/vmlu26.old|cut -d" " -f1)
		md5_en_ligne=$(grep ";vmlu26.old$" $tmp/versions.txt | cut -d";" -f2)

		if [ "$md5_en_ligne" != "$md5_en_place" ]; then
			version_noyo_old_udpcast_en_place=""
		fi
	fi

	if [ -e "/tftpboot/udprd.old" ]; then
		md5_en_place=$(md5sum /tftpboot/udprd.old|cut -d" " -f1)
		md5_en_ligne=$(grep ";udprd.old$" $tmp/versions.txt | cut -d";" -f2)

		if [ "$md5_en_ligne" != "$md5_en_place" ]; then
			version_initrd_old_udpcast_en_place=""
		fi
	fi


	# On controle la version des fichiers
	if [ "$version_noyo_udpcast_en_ligne" = "$version_noyo_udpcast_en_place" -a "$version_initrd_udpcast_en_ligne" = "$version_initrd_udpcast_en_place" -a "$version_noyo_old_udpcast_en_ligne" = "$version_noyo_old_udpcast_en_place" -a "$version_initrd_old_udpcast_en_ligne" = "$version_initrd_old_udpcast_en_place" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "Les fichiers vmlu26 et udprd de udpcast sont deja les plus recents; On ne les re-telecharge pas."
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
	if [ -e "/tftpboot/vmlu26" ]; then
		md5_en_place=$(md5sum /tftpboot/vmlu26|cut -d" " -f1)
		md5_en_ligne=$(grep ";vmlu26$" $tmp/versions.txt | cut -d";" -f2)
	fi

	if [ ! -e "/tftpboot/vmlu26" -o "$md5_en_ligne" != "$md5_en_place" -o "$version_noyo_udpcast_en_ligne" != "$version_noyo_udpcast_en_place" ]; then
		wget $src/vmlu26
		if [ "$?" != "0" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ERREUR lors du telechargement de $src/vmlu26"
			echo "ABANDON."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
				echo "</pre>"
			fi
			exit
		fi
	
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "SUCCES du telechargement de $src/vmlu26"
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi

		md5_telech=$(md5sum vmlu26|cut -d" " -f1)
		md5_en_ligne=$(grep ";vmlu26$" $tmp/versions.txt | cut -d";" -f2)
		if [ "$md5_telech" != "$md5_en_ligne" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ANOMALIE: La somme MD5 ne coincide pas: $md5_en_ligne en ligne et $md5_telech telecharge."
			echo "ABANDON."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
				echo "</pre>"
			fi
			exit
		fi

		liste_fichiers_a_copier="$liste_fichiers_a_copier vmlu26"
	fi

	md5_en_place=""
	md5_en_ligne=""
	if [ -e "/tftpboot/udprd" ]; then
		md5_en_place=$(md5sum /tftpboot/udprd|cut -d" " -f1)
		md5_en_ligne=$(grep ";udprd$" $tmp/versions.txt | cut -d";" -f2)
	fi

	if [ ! -e "/tftpboot/udprd" -o "$md5_en_ligne" != "$md5_en_place" -o "$version_initrd_udpcast_en_ligne" != "$version_initrd_udpcast_en_place" ]; then
		wget $src/udprd
		if [ "$?" != "0" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ERREUR lors du telechargement de $src/udprd"
			echo "ABANDON."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
				echo "</pre>"
			fi
			exit
		fi

		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "SUCCES du telechargement de $src/udprd"
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi

		md5_telech=$(md5sum udprd|cut -d" " -f1)
		md5_en_ligne=$(grep ";udprd$" $tmp/versions.txt | cut -d";" -f2)
		if [ "$md5_telech" != "$md5_en_ligne" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ANOMALIE: La somme MD5 ne coincide pas: $md5_en_ligne en ligne et $md5_telech telecharge."
			echo "ABANDON"
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
				echo "</pre>"
			fi
			exit
		fi

		liste_fichiers_a_copier="$liste_fichiers_a_copier udprd"
	fi


	md5_en_place=""
	md5_en_ligne=""
	if [ -e "/tftpboot/vmlu26.old" ]; then
		md5_en_place=$(md5sum /tftpboot/vmlu26.old|cut -d" " -f1)
		md5_en_ligne=$(grep ";vmlu26.old$" $tmp/versions.txt | cut -d";" -f2)
	fi

	if [ ! -e "/tftpboot/vmlu26.old" -o "$md5_en_ligne" != "$md5_en_place" -o "$version_noyo_old_udpcast_en_ligne" != "$version_noyo_old_udpcast_en_place" ]; then
		wget $src/vmlu26.old
		if [ "$?" != "0" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ERREUR lors du telechargement de $src/vmlu26.old"
			echo "ABANDON."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
				echo "</pre>"
			fi
			exit
		fi
	
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "SUCCES du telechargement de $src/vmlu26.old"
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi

		md5_telech=$(md5sum vmlu26.old|cut -d" " -f1)
		md5_en_ligne=$(grep ";vmlu26.old$" $tmp/versions.txt | cut -d";" -f2)
		if [ "$md5_telech" != "$md5_en_ligne" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ANOMALIE: La somme MD5 ne coincide pas: $md5_en_ligne en ligne et $md5_telech telecharge."
			echo "ABANDON."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
				echo "</pre>"
			fi
			exit
		fi

		liste_fichiers_a_copier="$liste_fichiers_a_copier vmlu26.old"
	fi

	md5_en_place=""
	md5_en_ligne=""
	if [ -e "/tftpboot/udprd.old" ]; then
		md5_en_place=$(md5sum /tftpboot/udprd.old|cut -d" " -f1)
		md5_en_ligne=$(grep ";udprd.old$" $tmp/versions.txt | cut -d";" -f2)
	fi

	if [ ! -e "/tftpboot/udprd.old" -o "$md5_en_ligne" != "$md5_en_place" -o "$version_initrd_old_udpcast_en_ligne" != "$version_initrd_old_udpcast_en_place" ]; then
		wget $src/udprd.old
		if [ "$?" != "0" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ERREUR lors du telechargement de $src/udprd.old"
			echo "ABANDON."
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
				echo "</pre>"
			fi
			exit
		fi

		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "SUCCES du telechargement de $src/udprd.old"
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi

		md5_telech=$(md5sum udprd.old|cut -d" " -f1)
		md5_en_ligne=$(grep ";udprd.old$" $tmp/versions.txt | cut -d";" -f2)
		if [ "$md5_telech" != "$md5_en_ligne" ]; then
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLERREUR"
			else
				echo "<span style='color:red'>"
			fi
			echo "ANOMALIE: La somme MD5 ne coincide pas: $md5_en_ligne en ligne et $md5_telech telecharge."
			echo "ABANDON"
			if [ "$mode" = "cmdline" ]; then
				echo -e "$COLTXT"
			else
				echo "</span>"
				echo "</pre>"
			fi
			exit
		fi

		liste_fichiers_a_copier="$liste_fichiers_a_copier udprd.old"
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
		SETMYSQL udpcast_noyo_version "$version_noyo_udpcast_en_ligne" "version actuelle du noyau udpcast" 7
		SETMYSQL udpcast_initrd_version "$version_initrd_udpcast_en_ligne" "version actuelle du initrd udpcast" 7
	
		SETMYSQL udpcast_noyo_old_version "$version_noyo_old_udpcast_en_ligne" "version old du noyau udpcast" 7
		SETMYSQL udpcast_initrd_old_version "$version_initrd_old_udpcast_en_ligne" "version old du initrd udpcast" 7
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
