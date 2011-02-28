#!/bin/bash

# $Id$
# Auteur: Stephane Boireau
# Derni�re modification: 06/02/2011

# Ajout en visudo:
# Cmnd_Alias SE3CLONAGE=/usr/share/se3/scripts/se3_tftp_boot_pxe.sh,/usr/share/se3/scripts/pxe_gen_cfg.sh

timestamp=$(date +%s)
timedate=$(date "+%Y-%m-%d %H:%M:%S")

case $1 in
	"sauve")
		#mac=$(echo "$2" | sed -e "s/:/-/g")
		#ip=$3
		#pc=$4
		#nom_image=$(echo "$5" | tr "[������������������������������]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		#src_part=$6
		#dest_part=$7
		#auto_reboot=$8
		#delais_reboot=$9
		#del_old_svg=${10}

		mac=$(echo "$*" | sed -e "s| |\n|g"|grep "^mac="|cut -d"=" -f2 | sed -e "s/:/-/g")
		ip=$(echo "$*" | sed -e "s| |\n|g"|grep "^ip="|cut -d"=" -f2)
		pc=$(echo "$*" | sed -e "s| |\n|g"|grep "pc="|cut -d"=" -f2)
		nom_image=$(echo "$*" | sed -e "s| |\n|g"|grep "^nom_image="|cut -d"=" -f2 | tr "[������������������������������]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		src_part=$(echo "$*" | sed -e "s| |\n|g"|grep "^src_part="|cut -d"=" -f2)
		dest_part=$(echo "$*" | sed -e "s| |\n|g"|grep "dest_part="|cut -d"=" -f2)
		auto_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "auto_reboot="|cut -d"=" -f2)
		delais_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "delais_reboot="|cut -d"=" -f2)
		del_old_svg=$(echo "$*" | sed -e "s| |\n|g"|grep "del_old_svg="|cut -d"=" -f2)

		if [ "$auto_reboot" != "y" -a "$auto_reboot" != "halt" ]; then
			auto_reboot="n"
		fi

		verif=$(echo "$delais_reboot" | sed -e "s/[0-9]//g")
		if [ "x$verif" != "x" ]; then
			delais_reboot=60
		fi

		ajout=""
		if [ -n "$del_old_svg" ]; then
			ajout=" del_old_svg=$del_old_svg"
		fi

		fich=/tftpboot/pxelinux.cfg/01-$mac

		chaine_modules=""
		if [ -e /var/www/se3/includes/config.inc.php ]; then
			dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
			dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
			dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
			dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`

			tmp_mac=$(echo "$mac"|tr "-" ":")
			tmp_module=($(echo "SELECT valeur FROM se3_tftp_infos WHERE mac='$tmp_mac';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname| tr "[A-Z]" "[a-z]"))
			nbmodules=${#tmp_module[*]}

			if [ $nbmodules -gt 0 ]; then
				chaine_modules="modprobe="
				index=0
				while [ $index -lt $nbmodules ]
				do
					if [ $index -gt 0 ]; then
						chaine_modules="$chaine_modules,"
					fi
					chaine_modules="${chaine_modules}${tmp_module[$index]}"
					index=$(($index+1))
				done
				chaine_modules="$chaine_modules "
			fi
		fi

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label distribution SliTaz:
label taz
   kernel bzImage
   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal sound=no screen=text

# Label de sauvegarde:
label tazsvg
   kernel bzImage" > $fich

		if [ -z "$nom_image" ]; then
			echo "   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal screen=text sound=no src_part=$src_part dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot ${ajout} work=/root/bin/sauve_part.sh ${chaine_modules}" >> $fich
		else
			echo "   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal screen=text sound=no src_part=$src_part dest_part=$dest_part nom_image=$nom_image auto_reboot=$auto_reboot delais_reboot=$delais_reboot ${ajout} work=/root/bin/sauve_part.sh ${chaine_modules}" >> $fich
		fi

		echo "
# Choix de boot par d�faut:
default tazsvg

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"restaure")
		#mac=$(echo "$2" | sed -e "s/:/-/g")
		#ip=$3
		#pc=$4
		#nom_image=$(echo "$5" | tr "[������������������������������]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		#src_part=$6
		#dest_part=$7
		#auto_reboot=$8
		#delais_reboot=$9

		mac=$(echo "$*" | sed -e "s| |\n|g"|grep "^mac="|cut -d"=" -f2 | sed -e "s/:/-/g")
		ip=$(echo "$*" | sed -e "s| |\n|g"|grep "^ip="|cut -d"=" -f2)
		pc=$(echo "$*" | sed -e "s| |\n|g"|grep "pc="|cut -d"=" -f2)
		nom_image=$(echo "$*" | sed -e "s| |\n|g"|grep "^nom_image="|cut -d"=" -f2| tr "[������������������������������]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		src_part=$(echo "$*" | sed -e "s| |\n|g"|grep "^src_part="|cut -d"=" -f2)
		dest_part=$(echo "$*" | sed -e "s| |\n|g"|grep "dest_part="|cut -d"=" -f2)
		auto_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "auto_reboot="|cut -d"=" -f2)
		delais_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "delais_reboot="|cut -d"=" -f2)

		#if [ "$auto_reboot" != "y" ]; then
		if [ "$auto_reboot" != "y" -a "$auto_reboot" != "halt" ]; then
			auto_reboot="n"
		fi

		verif=$(echo "$delais_reboot" | sed -e "s/[0-9]//g")
		if [ "x$verif" != "x" ]; then
			delais_reboot=60
		fi

		fich=/tftpboot/pxelinux.cfg/01-$mac

		chaine_modules=""
		if [ -e /var/www/se3/includes/config.inc.php ]; then
			dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
			dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
			dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
			dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`

			tmp_mac=$(echo "$mac"|tr "-" ":")
			tmp_module=($(echo "SELECT valeur FROM se3_tftp_infos WHERE mac='$tmp_mac';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname| tr "[A-Z]" "[a-z]"))
			nbmodules=${#tmp_module[*]}

			if [ $nbmodules -gt 0 ]; then
				chaine_modules="modprobe="
				index=0
				while [ $index -lt $nbmodules ]
				do
					if [ $index -gt 0 ]; then
						chaine_modules="$chaine_modules,"
					fi
					chaine_modules="${chaine_modules}${tmp_module[$index]}"
					index=$(($index+1))
				done
				chaine_modules="$chaine_modules "
			fi
		fi

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label distribution SliTaz:
label taz
   kernel bzImage
   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal sound=no screen=text

# Label de restauration:
label tazrst
   kernel bzImage" > $fich

		if [ -z "$nom_image" ]; then
			echo "   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal screen=text sound=no src_part=$src_part dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=/root/bin/restaure_part.sh ${chaine_modules}" >> $fich
		else
			echo "   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal screen=text sound=no src_part=$src_part dest_part=$dest_part nom_image=$nom_image auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=/root/bin/restaure_part.sh ${chaine_modules}" >> $fich
		fi

		echo "
# Choix de boot par d�faut:
default tazrst

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"sysresccd_sauve")
		#mac=$(echo "$2" | sed -e "s/:/-/g")
		#ip=$3
		#pc=$4
		#nom_image=$(echo "$5" | tr "[������������������������������]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		#src_part=$6
		#dest_part=$7
		#auto_reboot=$8
		#delais_reboot=$9
		#del_old_svg=${10}

		mac=$(echo "$*" | sed -e "s| |\n|g"|grep "^mac="|cut -d"=" -f2 | sed -e "s/:/-/g")
		ip=$(echo "$*" | sed -e "s| |\n|g"|grep "^ip="|cut -d"=" -f2)
		pc=$(echo "$*" | sed -e "s| |\n|g"|grep "pc="|cut -d"=" -f2)
		nom_image=$(echo "$*" | sed -e "s| |\n|g"|grep "^nom_image="|cut -d"=" -f2 | tr "[������������������������������]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		src_part=$(echo "$*" | sed -e "s| |\n|g"|grep "^src_part="|cut -d"=" -f2)
		dest_part=$(echo "$*" | sed -e "s| |\n|g"|grep "dest_part="|cut -d"=" -f2)
		auto_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "auto_reboot="|cut -d"=" -f2)
		delais_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "delais_reboot="|cut -d"=" -f2)
		del_old_svg=$(echo "$*" | sed -e "s| |\n|g"|grep "del_old_svg="|cut -d"=" -f2)

		kernel=$(echo "$*" | sed -e "s| |\n|g"|grep "^kernel="|cut -d"=" -f2)

		if [ "$auto_reboot" != "y" -a "$auto_reboot" != "halt" ]; then
			auto_reboot="n"
		fi

		verif=$(echo "$delais_reboot" | sed -e "s/[0-9]//g")
		if [ "x$verif" != "x" ]; then
			delais_reboot=60
		fi

		ajout=""
		if [ -n "$del_old_svg" ]; then
			ajout=" del_old_svg=$del_old_svg"
		fi

		if [ -e /var/www/se3/includes/config.inc.php ]; then
			dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
			dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
			dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
			dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
		else
			echo "Fichier de conf inaccessible"
			exit 1
		fi

		# A FAIRE: Pouvoir mettre le sysrcd.dat sur un autre serveur web
		se3ip=$(echo "SELECT value FROM params WHERE name='se3ip';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
		www_sysrcd_ip=$se3ip

		fich=/tftpboot/pxelinux.cfg/01-$mac

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label de sauvegarde:
label sysrcdsvg
    kernel $kernel
    #initrd initram.igz" > $fich
# A revoir: On peut avoir besoin de altker32,... au lieu de rescuecd

# kernel rescuecd
# initrd initram.igz
# APPEND scandelay=1 setkmap=fr autoruns=0 ar_nowait vga=791
# 

		if [ -z "$nom_image" ]; then
			echo "   append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait dodhcp src_part=$src_part dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=sauve_part.sh ${ajout}" >> $fich
		else
			echo "   append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait dodhcp src_part=$src_part dest_part=$dest_part nom_image=$nom_image auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=sauve_part.sh ${ajout}" >> $fich
		fi

		echo "
# Choix de boot par d�faut:
default sysrcdsvg

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"sysresccd_restaure")
		#mac=$(echo "$2" | sed -e "s/:/-/g")
		#ip=$3
		#pc=$4
		#nom_image=$(echo "$5" | tr "[������������������������������]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		#src_part=$6
		#dest_part=$7
		#auto_reboot=$8
		#delais_reboot=$9

		mac=$(echo "$*" | sed -e "s| |\n|g"|grep "^mac="|cut -d"=" -f2 | sed -e "s/:/-/g")
		ip=$(echo "$*" | sed -e "s| |\n|g"|grep "^ip="|cut -d"=" -f2)
		pc=$(echo "$*" | sed -e "s| |\n|g"|grep "pc="|cut -d"=" -f2)
		nom_image=$(echo "$*" | sed -e "s| |\n|g"|grep "^nom_image="|cut -d"=" -f2 | tr "[������������������������������]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		src_part=$(echo "$*" | sed -e "s| |\n|g"|grep "^src_part="|cut -d"=" -f2)
		dest_part=$(echo "$*" | sed -e "s| |\n|g"|grep "dest_part="|cut -d"=" -f2)
		auto_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "auto_reboot="|cut -d"=" -f2)
		delais_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "delais_reboot="|cut -d"=" -f2)

		kernel=$(echo "$*" | sed -e "s| |\n|g"|grep "^kernel="|cut -d"=" -f2)

		#if [ "$auto_reboot" != "y" ]; then
		if [ "$auto_reboot" != "y" -a "$auto_reboot" != "halt" ]; then
			auto_reboot="n"
		fi

		verif=$(echo "$delais_reboot" | sed -e "s/[0-9]//g")
		if [ "x$verif" != "x" ]; then
			delais_reboot=60
		fi

		if [ -e /var/www/se3/includes/config.inc.php ]; then
			dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
			dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
			dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
			dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
		else
			echo "Fichier de conf inaccessible"
			exit 1
		fi

		# A FAIRE: Pouvoir mettre le sysrcd.dat sur un autre serveur web
		se3ip=$(echo "SELECT value FROM params WHERE name='se3ip';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
		www_sysrcd_ip=$se3ip

		fich=/tftpboot/pxelinux.cfg/01-$mac

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label de restauration:
label sysrcdrst
    kernel rescuecd
    #initrd initram.igz" > $fich

		if [ -z "$nom_image" ]; then
			echo "   append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait dodhcp src_part=$src_part dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=restaure_part.sh" >> $fich
		else
			echo "   append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait dodhcp src_part=$src_part dest_part=$dest_part nom_image=$nom_image auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=restaure_part.sh" >> $fich
		fi

		echo "
# Choix de boot par d�faut:
default sysrcdrst

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"udpcast_emetteur")
		#mac=$(echo "$2" | sed -e "s/:/-/g")
		# IP ou dhcp
		# Comme on d�marre en PXE, on note l'IP pour info dans le CFG, mais on fonctionne en DHCP sur UDPCAST
		#ip=$3
		#mask=$4
		#pc=$4
		#compr=$5
		#port=$6
		#enableDiskmodule=$7
		#diskmodule=$8
		#netmodule=$9
		#disk=${10}
		#auto_reboot=${11}
		#udpcparam=${12}
		#urlse3=${13}
		#num_op=${14}
		#dhcp=${15}
		#dhcp_iface=${16}


		mac=$(echo "$*" | sed -e "s| |\n|g"|grep "^mac="|cut -d"=" -f2 | sed -e "s/:/-/g")
		ip=$(echo "$*" | sed -e "s| |\n|g"|grep "^ip="|cut -d"=" -f2)
		pc=$(echo "$*" | sed -e "s| |\n|g"|grep "pc="|cut -d"=" -f2)
		compr=$(echo "$*" | sed -e "s| |\n|g"|grep "compr="|cut -d"=" -f2)
		port=$(echo "$*" | sed -e "s| |\n|g"|grep "port="|cut -d"=" -f2)
		enableDiskmodule=$(echo "$*" | sed -e "s| |\n|g"|grep "enableDiskmodule="|cut -d"=" -f2)
		diskmodule=$(echo "$*" | sed -e "s| |\n|g"|grep "diskmodule="|cut -d"=" -f2)
		netmodule=$(echo "$*" | sed -e "s| |\n|g"|grep "netmodule="|cut -d"=" -f2)
		disk=$(echo "$*" | sed -e "s| |\n|g"|grep "disk="|cut -d"=" -f2)
		auto_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "auto_reboot="|cut -d"=" -f2)
		#udpcparam=$(echo "$*" | sed -e "s| |\n|g"|grep "udpcparam="|cut -d"=" -f2)
		udpcparam=$(echo "$*" | sed -e "s| |\n|g"|grep "udpcparam="|sed -e "s|^udpcparam=||"|tr "_" " ")
		urlse3=$(echo "$*" | sed -e "s| |\n|g"|grep "urlse3="|cut -d"=" -f2)
		num_op=$(echo "$*" | sed -e "s| |\n|g"|grep "num_op="|cut -d"=" -f2)
		dhcp=$(echo "$*" | sed -e "s| |\n|g"|grep "dhcp="|cut -d"=" -f2)
		dhcp_iface=$(echo "$*" | sed -e "s| |\n|g"|grep "dhcp_iface="|cut -d"=" -f2)


		if echo "$disk" | grep "^/dev/" ; then
			disk=$(echo "$disk" | sed -e "s|^/dev/||g")
		fi
		disk="/dev/$disk"


		# --min-wait t
		#    Even when the necessary amount of receivers do have connected, still wait until t seconds since first receiver connection have passed.
		#�--max-wait t
		#    When not enough receivers have connected (but at least one), start anyways when t seconds since first receiver connection have pased.
		# --start-timeout sec
		#    receiver aborts at start if it doesn't see a sender within this many seconds. Furthermore, the sender needs to start transmission of data within this delay. Once transmission is started, the timeout no longer applies.


		#disk=/dev/hda1
		#netmodule=AUTO
		#udpcparam=--min-receivers=1

		if [ "$auto_reboot" != "always" -a "$auto_reboot" != "success" ]; then
			auto_reboot="never"
		fi

		fich=/tftpboot/pxelinux.cfg/01-$mac

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label d'emission:
label u1auto
    kernel vmlu26" > $fich

		if [ "$dhcp" != "no" ]; then
			if [ ! -z "$diskmodule" ]; then
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=snd disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			else
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=snd disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			fi
		else
			netmask=$(/sbin/ifconfig ${dhcp_iface} |/bin/grep "inet " |/usr/bin/cut -d":" -f4 |/usr/bin/cut -d' '  -f1)

			if [ ! -z "$diskmodule" ]; then
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=no ip=$ip netmask=$netmask compr=$compr port=$port umode=snd disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			else
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=no ip=$ip netmask=$netmask compr=$compr port=$port umode=snd disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			fi
		fi

		echo "# Choix de boot par d�faut:
default u1auto

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"udpcast_recepteur")
		#mac=$(echo "$2" | sed -e "s/:/-/g")
		# IP ou dhcp
		# Comme on d�marre en PXE, on note l'IP pour info dans le CFG, mais on fonctionne en DHCP sur UDPCAST
		#ip=$3
		#mask=$4
		#pc=$4
		#compr=$5
		#port=$6
		#enableDiskmodule=$7
		#diskmodule=$8
		#netmodule=$9
		#disk=${10}
		#auto_reboot=${11}
		#udpcparam=${12}
		#urlse3=${13}
		#num_op=${14}
		#dhcp=${15}
		#dhcp_iface=${16}

		mac=$(echo "$*" | sed -e "s| |\n|g"|grep "^mac="|cut -d"=" -f2 | sed -e "s/:/-/g")
		ip=$(echo "$*" | sed -e "s| |\n|g"|grep "^ip="|cut -d"=" -f2)
		pc=$(echo "$*" | sed -e "s| |\n|g"|grep "pc="|cut -d"=" -f2)
		compr=$(echo "$*" | sed -e "s| |\n|g"|grep "compr="|cut -d"=" -f2)
		port=$(echo "$*" | sed -e "s| |\n|g"|grep "port="|cut -d"=" -f2)
		enableDiskmodule=$(echo "$*" | sed -e "s| |\n|g"|grep "enableDiskmodule="|cut -d"=" -f2)
		diskmodule=$(echo "$*" | sed -e "s| |\n|g"|grep "diskmodule="|cut -d"=" -f2)
		netmodule=$(echo "$*" | sed -e "s| |\n|g"|grep "netmodule="|cut -d"=" -f2)
		disk=$(echo "$*" | sed -e "s| |\n|g"|grep "disk="|cut -d"=" -f2)
		auto_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "auto_reboot="|cut -d"=" -f2)
		#udpcparam=$(echo "$*" | sed -e "s| |\n|g"|grep "udpcparam="|cut -d"=" -f2)
		udpcparam=$(echo "$*" | sed -e "s| |\n|g"|grep "udpcparam="|sed -e "s|^udpcparam=||"|tr "_" " ")
		urlse3=$(echo "$*" | sed -e "s| |\n|g"|grep "urlse3="|cut -d"=" -f2)
		num_op=$(echo "$*" | sed -e "s| |\n|g"|grep "num_op="|cut -d"=" -f2)
		dhcp=$(echo "$*" | sed -e "s| |\n|g"|grep "dhcp="|cut -d"=" -f2)
		dhcp_iface=$(echo "$*" | sed -e "s| |\n|g"|grep "dhcp_iface="|cut -d"=" -f2)


		if echo "$disk" | grep "^/dev/" ; then
			disk=$(echo "$disk" | sed -e "s|^/dev/||g")
		fi
		disk="/dev/$disk"

		# --min-wait t
		#    Even when the necessary amount of receivers do have connected, still wait until t seconds since first receiver connection have passed.
		#�--max-wait t
		#    When not enough receivers have connected (but at least one), start anyways when t seconds since first receiver connection have pased.
		# --start-timeout sec
		#    receiver aborts at start if it doesn't see a sender within this many seconds. Furthermore, the sender needs to start transmission of data within this delay. Once transmission is started, the timeout no longer applies.


		#disk=/dev/hda1
		#netmodule=AUTO
		#udpcparam=--min-receivers=1

		if [ "$auto_reboot" != "always" -a "$auto_reboot" != "success" ]; then
			auto_reboot="never"
		fi

		fich=/tftpboot/pxelinux.cfg/01-$mac

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label de reception:
label u2auto
    kernel vmlu26" > $fich

		if [ "$dhcp" != "no" ]; then
			if [ ! -z "$diskmodule" ]; then
				#echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule udpcparam=\"$udpcparam\"
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			else
				#echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule udpcparam=\"$udpcparam\"
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			fi
		else
			netmask=$(/sbin/ifconfig ${dhcp_iface} |/bin/grep "inet " |/usr/bin/cut -d":" -f4 |/usr/bin/cut -d' '  -f1)

			if [ ! -z "$diskmodule" ]; then
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=no ip=$ip netmask=$netmask compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			else
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=no ip=$ip netmask=$netmask compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			fi
		fi

		echo "# Choix de boot par d�faut:
default u2auto

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;


	"sysresccd_udpcast_emetteur")
		#mac=$(echo "$2" | sed -e "s/:/-/g")
		# IP ou dhcp
		# Comme on d�marre en PXE, on note l'IP pour info dans le CFG, mais on fonctionne en DHCP sur UDPCAST
		#ip=$3
		#mask=$4
		#pc=$4
		#compr=$5
		#port=$6
		# Non utilises avec SysRescCD
			#enableDiskmodule=$7
			#diskmodule=$8
			#netmodule=$9
		#disk=${10}
		#auto_reboot=${11}
		#udpcparam=${12}
		#urlse3=${13}
		#num_op=${14}
		#dhcp=${15}
		#dhcp_iface=${16}


		mac=$(echo "$*" | sed -e "s| |\n|g"|grep "^mac="|cut -d"=" -f2 | sed -e "s/:/-/g")
		ip=$(echo "$*" | sed -e "s| |\n|g"|grep "^ip="|cut -d"=" -f2)
		pc=$(echo "$*" | sed -e "s| |\n|g"|grep "pc="|cut -d"=" -f2)
		compr=$(echo "$*" | sed -e "s| |\n|g"|grep "compr="|cut -d"=" -f2)
		port=$(echo "$*" | sed -e "s| |\n|g"|grep "port="|cut -d"=" -f2)
		disk=$(echo "$*" | sed -e "s| |\n|g"|grep "disk="|cut -d"=" -f2)
		auto_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "auto_reboot="|cut -d"=" -f2)
		#udpcparam=$(echo "$*" | sed -e "s| |\n|g"|grep "udpcparam="|cut -d"=" -f2)
		udpcparam=$(echo "$*" | sed -e "s| |\n|g"|grep "udpcparam="|sed -e "s|^udpcparam=||"|tr "_" " ")
		urlse3=$(echo "$*" | sed -e "s| |\n|g"|grep "urlse3="|cut -d"=" -f2)
		num_op=$(echo "$*" | sed -e "s| |\n|g"|grep "num_op="|cut -d"=" -f2)
		dhcp=$(echo "$*" | sed -e "s| |\n|g"|grep "dhcp="|cut -d"=" -f2)
		dhcp_iface=$(echo "$*" | sed -e "s| |\n|g"|grep "dhcp_iface="|cut -d"=" -f2)

		kernel=$(echo "$*" | sed -e "s| |\n|g"|grep "kernel="|cut -d"=" -f2)


		if echo "$disk" | grep "^/dev/" ; then
			disk=$(echo "$disk" | sed -e "s|^/dev/||g")
		fi
		#disk="/dev/$disk"


		# --min-wait t
		#    Even when the necessary amount of receivers do have connected, still wait until t seconds since first receiver connection have passed.
		#�--max-wait t
		#    When not enough receivers have connected (but at least one), start anyways when t seconds since first receiver connection have pased.
		# --start-timeout sec
		#    receiver aborts at start if it doesn't see a sender within this many seconds. Furthermore, the sender needs to start transmission of data within this delay. Once transmission is started, the timeout no longer applies.


		#disk=/dev/hda1
		#netmodule=AUTO
		#udpcparam=--min-receivers=1

		if [ "$auto_reboot" != "always" -a "$auto_reboot" != "success" ]; then
			auto_reboot="never"
		fi

		# On pourra peut-etre remplacer par une autre machine pour heberger le serveur web requis pour le telechargement... pour alleger la charge pesant sur SE3

		if [ -e /var/www/se3/includes/config.inc.php ]; then
			dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
			dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
			dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
			dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
		else
			echo "Fichier de conf inaccessible"
			exit 1
		fi

		# A FAIRE: Pouvoir mettre le sysrcd.dat sur un autre serveur web
		se3ip=$(echo "SELECT value FROM params WHERE name='se3ip';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
		www_sysrcd_ip=$se3ip

		fich=/tftpboot/pxelinux.cfg/01-$mac

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label d'emission:
label srcdu1
    kernel rescuecd
    #initrd initram.igz" > $fich
# A revoir: On peut avoir besoin de altker32,... au lieu de rescuecd

# kernel rescuecd
# initrd initram.igz
# APPEND scandelay=1 setkmap=fr autoruns=0 ar_nowait vga=791
# 

		if [ "$dhcp" != "no" ]; then
			if [ ! -z "$diskmodule" ]; then
				echo "    append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait dodhcp work=udpcast3.sh compr=$compr port=$port umode=snd disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			else
				echo "    append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait dodhcp work=udpcast3.sh compr=$compr port=$port umode=snd disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			fi
		else
			netmask=$(/sbin/ifconfig ${dhcp_iface} |/bin/grep "inet " |/usr/bin/cut -d":" -f4 |/usr/bin/cut -d' '  -f1)

			#ethx=ip
			if [ ! -z "$diskmodule" ]; then
				echo "    append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait work=udpcast3.sh ip=$ip netmask=$netmask compr=$compr port=$port umode=snd disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			else
				echo "    append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait work=udpcast3.sh ip=$ip netmask=$netmask compr=$compr port=$port umode=snd disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			fi
		fi

		echo "# Choix de boot par d�faut:
default srcdu1

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"sysresccd_udpcast_recepteur")
		#mac=$(echo "$2" | sed -e "s/:/-/g")
		# IP ou dhcp
		# Comme on d�marre en PXE, on note l'IP pour info dans le CFG, mais on fonctionne en DHCP sur UDPCAST
		#ip=$3
		#mask=$4
		#pc=$4
		#compr=$5
		#port=$6
		# Non utilises avec SysRescCD
			#enableDiskmodule=$7
			#diskmodule=$8
			#netmodule=$9
		#disk=${10}
		#auto_reboot=${11}
		#udpcparam=${12}
		#urlse3=${13}
		#num_op=${14}
		#dhcp=${15}
		#dhcp_iface=${16}


		mac=$(echo "$*" | sed -e "s| |\n|g"|grep "^mac="|cut -d"=" -f2 | sed -e "s/:/-/g")
		ip=$(echo "$*" | sed -e "s| |\n|g"|grep "^ip="|cut -d"=" -f2)
		pc=$(echo "$*" | sed -e "s| |\n|g"|grep "pc="|cut -d"=" -f2)
		compr=$(echo "$*" | sed -e "s| |\n|g"|grep "compr="|cut -d"=" -f2)
		port=$(echo "$*" | sed -e "s| |\n|g"|grep "port="|cut -d"=" -f2)
		disk=$(echo "$*" | sed -e "s| |\n|g"|grep "disk="|cut -d"=" -f2)
		auto_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "auto_reboot="|cut -d"=" -f2)
		#udpcparam=$(echo "$*" | sed -e "s| |\n|g"|grep "udpcparam="|cut -d"=" -f2)
		udpcparam=$(echo "$*" | sed -e "s| |\n|g"|grep "udpcparam="|sed -e "s|^udpcparam=||"|tr "_" " ")
		urlse3=$(echo "$*" | sed -e "s| |\n|g"|grep "urlse3="|cut -d"=" -f2)
		num_op=$(echo "$*" | sed -e "s| |\n|g"|grep "num_op="|cut -d"=" -f2)
		dhcp=$(echo "$*" | sed -e "s| |\n|g"|grep "dhcp="|cut -d"=" -f2)
		dhcp_iface=$(echo "$*" | sed -e "s| |\n|g"|grep "dhcp_iface="|cut -d"=" -f2)

		kernel=$(echo "$*" | sed -e "s| |\n|g"|grep "kernel="|cut -d"=" -f2)



		if echo "$disk" | grep "^/dev/" ; then
			disk=$(echo "$disk" | sed -e "s|^/dev/||g")
		fi
		#disk="/dev/$disk"


		# --min-wait t
		#    Even when the necessary amount of receivers do have connected, still wait until t seconds since first receiver connection have passed.
		#�--max-wait t
		#    When not enough receivers have connected (but at least one), start anyways when t seconds since first receiver connection have pased.
		# --start-timeout sec
		#    receiver aborts at start if it doesn't see a sender within this many seconds. Furthermore, the sender needs to start transmission of data within this delay. Once transmission is started, the timeout no longer applies.


		#disk=/dev/hda1
		#netmodule=AUTO
		#udpcparam=--min-receivers=1

		if [ "$auto_reboot" != "always" -a "$auto_reboot" != "success" ]; then
			auto_reboot="never"
		fi

		# On pourra peut-etre remplacer par une autre machine pour heberger le serveur web requis pour le telechargement... pour alleger la charge pesant sur SE3
		if [ -e /var/www/se3/includes/config.inc.php ]; then
			dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
			dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
			dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
			dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
		else
			echo "Fichier de conf inaccessible"
			exit 1
		fi

		# A FAIRE: Pouvoir mettre le sysrcd.dat sur un autre serveur web
		se3ip=$(echo "SELECT value FROM params WHERE name='se3ip';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
		www_sysrcd_ip=$se3ip

		fich=/tftpboot/pxelinux.cfg/01-$mac

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label de reception:
label srcdu2
    kernel rescuecd
    #initrd initram.igz" > $fich
# A revoir: On peut avoir besoin de altker32,... au lieu de rescuecd

		if [ "$dhcp" != "no" ]; then
			if [ ! -z "$diskmodule" ]; then
				#echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule udpcparam=\"$udpcparam\"
				echo "    append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait dodhcp work=udpcast3.sh compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			else
				#echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule udpcparam=\"$udpcparam\"
				echo "    append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait dodhcp work=udpcast3.sh compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			fi
		else
			netmask=$(/sbin/ifconfig ${dhcp_iface} |/bin/grep "inet " |/usr/bin/cut -d":" -f4 |/usr/bin/cut -d' '  -f1)

			if [ ! -z "$diskmodule" ]; then
				echo "    append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait ip=$ip netmask=$netmask work=udpcast3.sh compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			else
				echo "    append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait ip=$ip netmask=$netmask work=udpcast3.sh compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=\"$udpcparam\"
	" >> $fich
			fi
		fi

		echo "# Choix de boot par d�faut:
default srcdu2

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"rapport")
		#mac=$(echo "$2" | sed -e "s/:/-/g")
		#ip=$3
		#pc=$4
			#nom_image=$(echo "$5" | tr "[������������������������������]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
			#src_part=$6
			#dest_part=$7
		#auto_reboot=$5
		#delais_reboot=$6

		mac=$(echo "$*" | sed -e "s| |\n|g"|grep "^mac="|cut -d"=" -f2 | sed -e "s/:/-/g")
		ip=$(echo "$*" | sed -e "s| |\n|g"|grep "^ip="|cut -d"=" -f2)
		pc=$(echo "$*" | sed -e "s| |\n|g"|grep "pc="|cut -d"=" -f2)
		auto_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "auto_reboot="|cut -d"=" -f2)
		delais_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "delais_reboot="|cut -d"=" -f2)

		#if [ "$auto_reboot" != "y" ]; then
		if [ "$auto_reboot" != "y" -a "$auto_reboot" != "halt" ]; then
			auto_reboot="n"
		fi

		verif=$(echo "$delais_reboot" | sed -e "s/[0-9]//g")
		if [ "x$verif" != "x" ]; then
			delais_reboot=60
		fi

		fich=/tftpboot/pxelinux.cfg/01-$mac

		chaine_modules=""
		if [ -e /var/www/se3/includes/config.inc.php ]; then
			dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
			dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
			dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
			dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`

			tmp_mac=$(echo "$mac"|tr "-" ":")
			tmp_module=($(echo "SELECT valeur FROM se3_tftp_infos WHERE mac='$tmp_mac';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname| tr "[A-Z]" "[a-z]"))
			nbmodules=${#tmp_module[*]}

			if [ $nbmodules -gt 0 ]; then
				chaine_modules="modprobe="
				index=0
				while [ $index -lt $nbmodules ]
				do
					if [ $index -gt 0 ]; then
						chaine_modules="$chaine_modules,"
					fi
					chaine_modules="${chaine_modules}${tmp_module[$index]}"
					index=$(($index+1))
				done
				chaine_modules="$chaine_modules "
			fi
		fi

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label distribution SliTaz:
label taz
   kernel bzImage
   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal sound=no screen=text

# Label de rapport
label tazrap
   kernel bzImage" > $fich

		echo "   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal sound=no screen=text auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=/root/bin/rapport.sh ${chaine_modules}" >> $fich

		echo "
# Choix de boot par d�faut:
default tazrap

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"sysresccd_rapport")
		#mac=$(echo "$2" | sed -e "s/:/-/g")
		#ip=$3
		#pc=$4
			#nom_image=$(echo "$5" | tr "[������������������������������]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
			#src_part=$6
			#dest_part=$7
		#auto_reboot=$5
		#delais_reboot=$6

		mac=$(echo "$*" | sed -e "s| |\n|g"|grep "^mac="|cut -d"=" -f2 | sed -e "s/:/-/g")
		ip=$(echo "$*" | sed -e "s| |\n|g"|grep "^ip="|cut -d"=" -f2)
		pc=$(echo "$*" | sed -e "s| |\n|g"|grep "pc="|cut -d"=" -f2)
		auto_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "auto_reboot="|cut -d"=" -f2)
		delais_reboot=$(echo "$*" | sed -e "s| |\n|g"|grep "delais_reboot="|cut -d"=" -f2)

		kernel=$(echo "$*" | sed -e "s| |\n|g"|grep "kernel="|cut -d"=" -f2)

		#if [ "$auto_reboot" != "y" ]; then
		if [ "$auto_reboot" != "y" -a "$auto_reboot" != "halt" ]; then
			auto_reboot="n"
		fi

		verif=$(echo "$delais_reboot" | sed -e "s/[0-9]//g")
		if [ "x$verif" != "x" ]; then
			delais_reboot=60
		fi

		if [ -e /var/www/se3/includes/config.inc.php ]; then
			dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
			dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
			dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
			dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
		else
			echo "Fichier de conf inaccessible"
			exit 1
		fi

		# A FAIRE: Pouvoir mettre le sysrcd.dat sur un autre serveur web
		se3ip=$(echo "SELECT value FROM params WHERE name='se3ip';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
		www_sysrcd_ip=$se3ip

		fich=/tftpboot/pxelinux.cfg/01-$mac

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label de rapport
label sysrcdrap
    kernel $kernel
    #initrd initram.igz" > $fich

		echo "append initrd=initram.igz scandelay=5 setkmap=fr netboot=http://$www_sysrcd_ip/sysresccd/sysrcd.dat autoruns=2 ar_source=http://$www_sysrcd_ip/sysresccd/ ar_nowait dodhcp auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=rapport.sh" >> $fich

		echo "
# Choix de boot par d�faut:
default sysrcdrap

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"unattend_xp")
		mac=$(echo "$2" | sed -e "s/:/-/g")
		ip=$3
		pc=$4

		# on regenere unattend.csv
		/usr/share/se3/scripts/unattended_generate.sh -u > /dev/null

		fich=/tftpboot/pxelinux.cfg/01-$mac

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label d'install unattend:
label install
    kernel bzImageunattend
    # Add options (z_user=..., z_path=..., etc.) to this line.
    append initrd=initrdunattend

# Choix de boot par d�faut:
default install

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;
	"memtest")

		mac=$(echo "$2" | sed -e "s/:/-/g")
		ip=$3
		pc=$4

		fich=/tftpboot/pxelinux.cfg/01-$mac

		echo "# Script de boot de la machine $pc
# MAC=$mac
# IP= $ip
# Date de generation du fichier: $timedate
# Timestamp: $timestamp

# Echappatoires pour booter sur le DD:
label 0
   localboot 0x80
label a
   localboot 0x00
label q
   localboot -1
label disk1
   localboot 0x80
label disk2
  localboot 0x81

# Label de memtest
label memtest
  kernel memtp

# Choix de boot par d�faut:
default memtest

# On boote apr�s 6 secondes:
timeout 60

# Permet-on � l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich

	;;
esac
