#!/bin/bash

# $Id$
# Auteur: Stephane Boireau
# Dernière modification: 30/10/2008

# Ajout en visudo:
# Cmnd_Alias SE3CLONAGE=/usr/share/se3/scripts/se3_tftp_boot_pxe.sh,/usr/share/se3/scripts/pxe_gen_cfg.sh

timestamp=$(date +%s)
timedate=$(date "+%Y-%m-%d %H:%M:%S")

case $1 in
	"sauve")
		mac=$(echo "$2" | sed -e "s/:/-/g")
		ip=$3
		pc=$4
		nom_image=$(echo "$5" | tr "[ÀÄÂÉÈÊËÎÏÔÖÙÛÜÇçàäâéèêëîïôöùûü]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		src_part=$6
		dest_part=$7
		auto_reboot=$8
		delais_reboot=$9

		if [ "$auto_reboot" != "y" -a "$auto_reboot" != "halt" ]; then
			auto_reboot="n"
		fi

		verif=$(echo "$delais_reboot" | sed -e "s/[0-9]//g")
		if [ "x$verif" != "x" ]; then
			delais_reboot=60
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

# Label distribution SliTaz:
label taz
   kernel bzImage
   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal sound=no screen=text

# Label de sauvegarde:
label tazsvg
   kernel bzImage" > $fich

		if [ -z "$nom_image" ]; then
			echo "   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal screen=text sound=no src_part=$src_part dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=/root/bin/sauve_part.sh" >> $fich
		else
			echo "   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal screen=text sound=no src_part=$src_part dest_part=$dest_part nom_image=$nom_image auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=/root/bin/sauve_part.sh" >> $fich
		fi

		echo "
# Choix de boot par défaut:
default tazsvg

# On boote après 6 secondes:
timeout 60

# Permet-on à l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"restaure")
		mac=$(echo "$2" | sed -e "s/:/-/g")
		ip=$3
		pc=$4
		nom_image=$(echo "$5" | tr "[ÀÄÂÉÈÊËÎÏÔÖÙÛÜÇçàäâéèêëîïôöùûü]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		src_part=$6
		dest_part=$7
		auto_reboot=$8
		delais_reboot=$9

		#if [ "$auto_reboot" != "y" ]; then
		if [ "$auto_reboot" != "y" -a "$auto_reboot" != "halt" ]; then
			auto_reboot="n"
		fi

		verif=$(echo "$delais_reboot" | sed -e "s/[0-9]//g")
		if [ "x$verif" != "x" ]; then
			delais_reboot=60
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

# Label distribution SliTaz:
label taz
   kernel bzImage
   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal sound=no screen=text

# Label de restauration:
label tazrst
   kernel bzImage" > $fich

		if [ -z "$nom_image" ]; then
			echo "   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal screen=text sound=no src_part=$src_part dest_part=$dest_part auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=/root/bin/restaure_part.sh" >> $fich
		else
			echo "   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal screen=text sound=no src_part=$src_part dest_part=$dest_part nom_image=$nom_image auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=/root/bin/restaure_part.sh" >> $fich
		fi

		echo "
# Choix de boot par défaut:
default tazrst

# On boote après 6 secondes:
timeout 60

# Permet-on à l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"udpcast_emetteur")
		mac=$(echo "$2" | sed -e "s/:/-/g")
		# IP ou dhcp
		# Comme on démarre en PXE, on note l'IP pour info dans le CFG, mais on fonctionne en DHCP sur UDPCAST
		ip=$3
		#mask=$4
		pc=$4

		compr=$5
		port=$6

		enableDiskmodule=$7
		diskmodule=$8
		netmodule=$9

		disk=${10}
		if echo "$disk" | grep "^/dev/" ; then
			disk=$(echo "$disk" | sed -e "s|^/dev/||g")
		fi
		disk="/dev/$disk"

		auto_reboot=${11}

		udpcparam=${12}

		# --min-wait t
		#    Even when the necessary amount of receivers do have connected, still wait until t seconds since first receiver connection have passed.
		# --max-wait t
		#    When not enough receivers have connected (but at least one), start anyways when t seconds since first receiver connection have pased.
		# --start-timeout sec
		#    receiver aborts at start if it doesn't see a sender within this many seconds. Furthermore, the sender needs to start transmission of data within this delay. Once transmission is started, the timeout no longer applies.

		urlse3=${13}
		num_op=${14}

		dhcp=${15}
		dhcp_iface=${16}

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

		echo "# Choix de boot par défaut:
default u1auto

# On boote après 6 secondes:
timeout 60

# Permet-on à l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"udpcast_recepteur")
		mac=$(echo "$2" | sed -e "s/:/-/g")
		# IP ou dhcp
		# Comme on démarre en PXE, on note l'IP pour info dans le CFG, mais on fonctionne en DHCP sur UDPCAST
		ip=$3
		#mask=$4
		pc=$4

		compr=$5
		port=$6

		enableDiskmodule=$7
		diskmodule=$8
		netmodule=$9

		disk=${10}
		if echo "$disk" | grep "^/dev/" ; then
			disk=$(echo "$disk" | sed -e "s|^/dev/||g")
		fi
		disk="/dev/$disk"

		auto_reboot=${11}

		udpcparam=${12}

		# --min-wait t
		#    Even when the necessary amount of receivers do have connected, still wait until t seconds since first receiver connection have passed.
		# --max-wait t
		#    When not enough receivers have connected (but at least one), start anyways when t seconds since first receiver connection have pased.
		# --start-timeout sec
		#    receiver aborts at start if it doesn't see a sender within this many seconds. Furthermore, the sender needs to start transmission of data within this delay. Once transmission is started, the timeout no longer applies.

		urlse3=${13}
		num_op=${14}

		dhcp=${15}
		dhcp_iface=${16}

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
				#echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule udpcparam=$udpcparam
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=$udpcparam
	" >> $fich
			else
				#echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule udpcparam=$udpcparam
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=yes compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=$udpcparam
	" >> $fich
			fi
		else
			netmask=$(/sbin/ifconfig ${dhcp_iface} |/bin/grep "inet " |/usr/bin/cut -d":" -f4 |/usr/bin/cut -d' '  -f1)

			if [ ! -z "$diskmodule" ]; then
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=no ip=$ip netmask=$netmask compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule diskmodule=$diskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=$udpcparam
	" >> $fich
			else
				echo "    append initrd=udprd root=01:00 persoparams=oui lang=FR kbmap=FR dhcp=no ip=$ip netmask=$netmask compr=$compr port=$port umode=rcv disk=$disk auto_reboot=$auto_reboot enableDiskmodule=$enableDiskmodule netmodule=$netmodule remontee_info=y page_remontee=${urlse3}/tftp/remontee_udpcast.php mac=$mac num_op=${num_op} udpcparam=$udpcparam
	" >> $fich
			fi
		fi

		echo "# Choix de boot par défaut:
default u2auto

# On boote après 6 secondes:
timeout 60

# Permet-on à l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich
	;;

	"rapport")
		mac=$(echo "$2" | sed -e "s/:/-/g")
		ip=$3
		pc=$4
		#nom_image=$(echo "$5" | tr "[ÀÄÂÉÈÊËÎÏÔÖÙÛÜÇçàäâéèêëîïôöùûü]" "[AAAEEEEIIOOUUUCcaaaeeeeiioouuu]" | sed -e "s/[^A-Za-z0-9_.]//g")
		#src_part=$6
		#dest_part=$7
		auto_reboot=$5
		delais_reboot=$6

		#if [ "$auto_reboot" != "y" ]; then
		if [ "$auto_reboot" != "y" -a "$auto_reboot" != "halt" ]; then
			auto_reboot="n"
		fi

		verif=$(echo "$delais_reboot" | sed -e "s/[0-9]//g")
		if [ "x$verif" != "x" ]; then
			delais_reboot=60
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

# Label distribution SliTaz:
label taz
   kernel bzImage
   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal sound=no screen=text

# Label de rapport
label tazrap
   kernel bzImage" > $fich

		echo "   append initrd=rootfs.gz rw root=/dev/null lang=fr_FR kmap=fr-latin1 vga=normal sound=no screen=text auto_reboot=$auto_reboot delais_reboot=$delais_reboot work=/root/bin/rapport.sh" >> $fich

		echo "
# Choix de boot par défaut:
default tazrap

# On boote après 6 secondes:
timeout 60

# Permet-on à l'utilisateur de choisir l'option de boot?
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

# Choix de boot par défaut:
default install

# On boote après 6 secondes:
timeout 60

# Permet-on à l'utilisateur de choisir l'option de boot?
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

# Choix de boot par défaut:
default memtest

# On boote après 6 secondes:
timeout 60

# Permet-on à l'utilisateur de choisir l'option de boot?
# Si on ne permet pas, le timeout n'est pas pris en compte.
prompt 1
" >> $fich

	;;
esac
