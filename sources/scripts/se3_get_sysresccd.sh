#!/bin/bash

# $Id$
# Auteur: Stephane Boireau
# Dernière modification: 01/11/2010

# Ajout en visudo:
# Cmnd_Alias SE3CLONAGE=/usr/share/se3/scripts/se3_tftp_boot_pxe.sh,/usr/share/se3/scripts/se3_get_sysresccd.sh

# Coquille vide pour le moment
# - telecharger SysRescCD depuis http://wawadeb.crdp.ac-caen.fr/iso/sysresccd/
#   Deux options:
#      . on n'y met que les sysrcd.dat, sysrcd.md5, rescuecd et initram.igz pour alleger... mais il risque de manquer les noyaux alternatifs utiles pour certaines stations
#      . on y met l'ISO et on fait le montage en loop et la recup des fichiers utiles une fois le telechargement effectue
# - Mettre en place les fichiers sysrcd.dat, sysrcd.md5, autorun2 et scripts.tar.gz dans /var/www/sysresccd
# - Mettre en place les fichiers noyau et initram dans /tftpboot/

#echo "Script non encore fonctionnel... la mise en place est a faire a la mano."
#exit
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

src="http://wawadeb.crdp.ac-caen.fr/iso/sysresccd"

tmp="/var/se3/tmp_sysresccd_${timedate}"
mkdir -p "$tmp"
chmod 700 $tmp

# ========================================

t=$(echo "$*" | grep "check_version")
if [ -n "$t" ]; then
	cd $tmp
	wget http://wawadeb.crdp.ac-caen.fr/iso/sysresccd/versions.txt > /dev/null 2>&1
	if [ "$?" = 0 -a -e versions.txt ]; then
		version_srcd_en_ligne=$(grep ";systemrescuecd.iso$" $tmp/versions.txt | cut -d";" -f1)
		version_autorun2_en_ligne=$(grep ";autorun2$" $tmp/versions.txt | cut -d";" -f1)
		version_scripts_en_ligne=$(grep ";scripts.tar.gz$" $tmp/versions.txt | cut -d";" -f1)

		version_srcd_en_place="$srcd_version"
		version_autorun2_en_place="$srcd_autorun2_vers"
		version_scripts_en_place="$srcd_scripts_vers"

		if [ "$version_srcd_en_ligne" = "$version_srcd_en_place" -a "$version_autorun2_en_ligne" = "$version_autorun2_en_place" -a "$version_scripts_en_ligne" = "$version_scripts_en_place" ]; then
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
	<th>SystemRescueCD</th>
	<td>$version_srcd_en_place</td>
	<td>$version_srcd_en_ligne</td>
</tr>
<tr>
	<th>Autorun</th>
	<td>$version_autorun2_en_place</td>
	<td>$version_autorun2_en_ligne</td>
</tr>
<tr>
	<th>Scripts</th>
	<td>$version_scripts_en_place</td>
	<td>$version_scripts_en_ligne</td>
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
echo "Mise en place des fichiers utiles pour SystemRescueCD en boot PXE."
if [ "$mode" = "cmdline" ]; then
	echo -e "$COLCMD"
else
	echo "</h3>"
fi

# Emplacement des fichiers telecharges par SysRescCD lors du boot
depot_sysrcd="/var/www/sysresccd"
mkdir -p "$depot_sysrcd"
chmod 755 "$depot_sysrcd"

cpt=0

#===================================================================

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
	cpt=$(($cpt+1))
fi
version_srcd_en_place="$srcd_version"
version_srcd_en_ligne=$(grep ";systemrescuecd.iso$" $tmp/versions.txt | cut -d";" -f1)

if [ -e "$depot_sysrcd/sysrcd.dat" -a -e "$depot_sysrcd/sysrcd.md5" -a -e "/tftpboot/rescuecd" -a -e "/tftpboot/rescue64" -a -e "/tftpboot/altker32" -a -e "/tftpboot/initram.igz" ]; then
	# On controle la version SysRescCD
	version_srcd_en_place="$srcd_version"
	version_srcd_en_ligne=$(grep ";systemrescuecd.iso$" $tmp/versions.txt | cut -d";" -f1)
	if [ "$version_srcd_en_place" = "$version_srcd_en_ligne" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "Les fichiers de l'ISO sont deja les plus recents; On ne re-telecharge pas l'ISO."
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi
		temoin_sysrcd="n"
	else
		# La version a change.
		temoin_sysrcd="y"
	fi
else
	# Il manque au moins un fichier, on telecharge pour mettre a jour
	temoin_sysrcd="y"
fi

if [ "$temoin_sysrcd" = "y" ]; then
	wget $src/systemrescuecd.iso
	if [ "$?" != "0" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLERREUR"
		else
			echo "<span style='color:red'>"
		fi
		echo "ERREUR lors du telechargement de $src/systemrescuecd.iso"
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
		echo "SUCCES du telechargement de $src/systemrescuecd.iso"
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi

		md5_telech=$(md5sum systemrescuecd.iso|cut -d" " -f1)
		md5_en_ligne=$(grep ";systemrescuecd.iso$" $tmp/versions.txt | cut -d";" -f2)
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
			temoin_sysrcd="n"
		else
			SETMYSQL srcd_version "$version_srcd_en_ligne" "version actuelle de System Rescue CD" 7
# 			if [ -n "$version_srcd_en_place" ]; then
# 				echo "UPDATE params SET value='$version_srcd_en_ligne' WHERE name='srcd_version';" | mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname
# 			else
# 				echo "INSERT INTO params SET value='$version_srcd_en_ligne', name='srcd_version';" | mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname
# 			fi
		fi
		cpt=$(($cpt+1))
	fi
fi

#===================================================================

# Pour parer des bugs suite a des suppressions de fichiers, on recupere la version meme si ce n'est pas utile
version_autorun2_en_place="$srcd_autorun2_vers"
version_autorun2_en_ligne=$(grep ";autorun2$" $tmp/versions.txt | cut -d";" -f1)
if [ -e "$depot_sysrcd/autorun2" ]; then
	# On controle la version
	if [ "$version_autorun2_en_place" = "$version_autorun2_en_ligne" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "Le fichier autorun2 est deja le plus recent; On ne re-telecharge pas."
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi
		temoin_autorun2="n"
	else
		# La version a change.
		temoin_autorun2="y"
	fi
else
	# Le fichier est manquant, on telecharge pour mettre a jour
	temoin_autorun2="y"
fi

cd "$depot_sysrcd"
if [ "$temoin_autorun2" = "y" ]; then
	if [ -e autorun2 ]; then
		if [ -n "$version_autorun2_en_place" ]; then
			mv autorun2 autorun2.$version_autorun2_en_place
			autorun2_old=autorun2.$version_autorun2_en_place
		else
			tmpdate=$(stat -c '%y' autorun2|cut -d" " -f1,2|sed -e "s/ /_/g")
			mv autorun2 autorun2.old.$tmpdate
			autorun2_old=autorun2.old.$tmpdate
		fi
	fi

	wget $src/autorun2
	if [ "$?" != "0" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLERREUR"
		else
			echo "<span style='color:red'>"
		fi
		echo "ERREUR lors du telechargement de $src/autorun2"
		echo "ABANDON."
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
			echo "</pre>"
		fi

		# On retablit l'autorun2 precedent
		if [ -n "$autorun2_old" -a -e "$autorun2_old" ]; then
			rm -f autorun2
			mv $autorun2_old autorun2
		fi

		exit
	else
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "SUCCES du telechargement de $src/autorun2"
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi

		md5_telech=$(md5sum autorun2|cut -d" " -f1)
		md5_en_ligne=$(grep ";autorun2$" $tmp/versions.txt | cut -d";" -f2)
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

			# On retablit l'autorun2 precedent
			if [ -n "$autorun2_old" -a -e "$autorun2_old" ]; then
				rm -f autorun2
				mv $autorun2_old autorun2
			fi

		else
			SETMYSQL srcd_autorun2_vers "$version_autorun2_en_ligne" "version distante du script autorun" 7
# 			if [ -n "$version_autorun2_en_place" ]; then
# 				CHANGEMYSQL srcd_autorun2_vers "$version_autorun2_en_ligne"
# 				#echo "UPDATE params SET value='$version_autorun2_en_ligne' WHERE name='srcd_autorun2_vers';" | mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname
# 			else
# 				
# 				#echo "INSERT INTO params SET value='$version_autorun2_en_ligne', name='srcd_autorun2_vers';" | mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname
# 			fi
		fi
		cpt=$(($cpt+1))
	fi
fi

#===================================================================

# Pour parer des bugs suite a des suppressions de fichiers, on recupere la version meme si ce n'est pas utile
version_scripts_en_place="$srcd_scripts_vers"
version_scripts_en_ligne=$(grep ";scripts.tar.gz$" $tmp/versions.txt | cut -d";" -f1)
if [ -e "$depot_sysrcd/scripts.tar.gz" ]; then
	# On controle la version
	if [ "$version_scripts_en_place" = "$version_scripts_en_ligne" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "Le fichier scripts.tar.gz est deja le plus recent; On ne re-telecharge pas."
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi
		temoin_scripts="n"
	else
		# La version a change.
		temoin_scripts="y"
	fi
else
	# Le fichier est manquant, on telecharge pour mettre a jour
	temoin_scripts="y"
fi

if [ "$temoin_scripts" = "y" ]; then
	if [ -e scripts.tar.gz ]; then
		if [ -n "$version_srcd_en_place" ]; then
			mv scripts.tar.gz scripts.tar.gz.$version_scripts_en_place
			scripts_old=scripts.tar.gz.$version_scripts_en_place
		else
			tmpdate=$(stat -c '%y' autorun2|cut -d" " -f1,2|sed -e "s/ /_/g")
			mv scripts.tar.gz scripts.tar.gz.old.$tmpdate
			scripts_old=scripts.tar.gz.old.$tmpdate
		fi
	fi

	wget $src/scripts.tar.gz
	if [ "$?" != "0" ]; then
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLERREUR"
		else
			echo "<span style='color:red'>"
		fi
		echo "ERREUR lors du telechargement de $src/scripts.tar.gz"
		echo "ABANDON."
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
			echo "</pre>"
		fi

		# On retablit le scripts.tar.gz precedent
		if [ -n "$scripts_old" -a -e "$scripts_old" ]; then
			rm -f scripts.tar.gz
			mv $scripts_old scripts.tar.gz
		fi

		exit
	else
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLINFO"
		else
			echo "<span style='color:green'>"
		fi
		echo "SUCCES du telechargement de $src/scripts.tar.gz"
		if [ "$mode" = "cmdline" ]; then
			echo -e "$COLTXT"
		else
			echo "</span>"
		fi


		md5_telech=$(md5sum scripts.tar.gz|cut -d" " -f1)
		md5_en_ligne=$(grep ";scripts.tar.gz$" $tmp/versions.txt | cut -d";" -f2)
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

			# On retablit l'autorun2 precedent
			if [ -n "$scripts_old" -a -e "$scripts_old" ]; then
				rm -f scripts.tar.gz
				mv $scripts_old scripts.tar.gz
			fi
		else
			# la fcontion SETMYSQL fait un test si exist et fait un update si bersoin
			SETMYSQL srcd_scripts_vers "$version_scripts_en_ligne" "version de scripts.tar.gz pour System Rescue CD" 7
# 			if [ -n "$version_scripts_en_place" ]; then
# 				echo "UPDATE params SET value='$version_scripts_en_ligne' WHERE name='srcd_scripts_vers';" | mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname
# 			else
# 				echo "INSERT INTO params SET value='$version_scripts_en_ligne', name='srcd_scripts_vers';" | mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname
# 			fi
		fi

		cpt=$(($cpt+1))
	fi
fi

if [ "$temoin_sysrcd" = "y" ]; then
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLTXT"
	else
		echo "<b>"
	fi
	echo "Montage de l'ISO en loop..."
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLCMD"
	else
		echo "</b>"
	fi
	mnt_loop="/mnt/loop_${timedate}"
	mkdir -p $mnt_loop
	chmod 700 $mnt_loop
	mount -t iso9660 -o loop $tmp/systemrescuecd.iso $mnt_loop
	mkdir -p /tftpboot
	
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLTXT"
	else
		echo "<b>"
	fi
	echo "Copie des fichiers de l'ISO vers leur emplacement..."
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLCMD"
	else
		echo "</b>"
	fi
	cp -fv $mnt_loop/isolinux/rescuecd /tftpboot/
	cp -fv $mnt_loop/isolinux/altker32 /tftpboot/
	cp -fv $mnt_loop/isolinux/rescue64 /tftpboot/
	cp -fv $mnt_loop/isolinux/initram.igz /tftpboot/
	cp -fv $mnt_loop/sysrcd.dat $depot_sysrcd
	cp -fv $mnt_loop/sysrcd.md5 $depot_sysrcd
	
	# Nettoyage
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLTXT"
	else
		echo "<b>"
	fi
	echo "Demontage"
	if [ "$mode" = "cmdline" ]; then
		echo -e "$COLCMD"
	else
		echo "</b>"
	fi
	umount $mnt_loop
	
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
	rm -fr $mnt_loop
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
