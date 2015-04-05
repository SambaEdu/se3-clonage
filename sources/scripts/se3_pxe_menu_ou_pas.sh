#!/bin/bash

# $Id$
# Auteur: Stephane Boireau
# Dernière modification: 08/11/2010

# Ajout en visudo:
# Cmnd_Alias SE3CLONAGE=/usr/share/se3/scripts/se3_tftp_boot_pxe.sh,/usr/share/se3/scripts/pxe_gen_cfg.sh,/usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh,/usr/share/se3/scripts/set_password_menu_tftp.sh,/usr/share/se3/scripts/se3_pxe_slitaz_cmdline.sh

. /usr/share/se3/includes/config.inc.sh -d
. /usr/share/se3/includes/functions.inc.sh

if [ "$1" = "menu" ]; then
	SETMYSQL tftp_aff_menu_pxe "y" "affichage menu tftp " 7
	cp /tftpboot/tftp_modeles_pxelinux.cfg/menu/* /tftpboot/pxelinux.cfg/
else
	cp /tftpboot/tftp_modeles_pxelinux.cfg/standard/* /tftpboot/pxelinux.cfg/
fi

if [ -e "/tftpboot/pxelinux.cfg/divers.menu" ]; then
	sed -i "s|###divers###||" /tftpboot/pxelinux.cfg/maintenance.menu
fi

if [ -e "/tftpboot/pxelinux.cfg/perso.menu" ]; then
	sed -i "s|###perso###||" /tftpboot/pxelinux.cfg/maintenance.menu
fi

if [ "$3" = "random" ]; then
	tftp_pass_menu_pxe=$(makepasswd)
	SETMYSQL tftp_pass_menu_pxe "$tftp_pass_menu_pxe" "mot de passe boot pxe" 7
fi

if [ -n "$2" ]; then
	t=$(echo "$2" | sed -e "s|[0-9]||g")
	if [ -n "$t" ]; then
		tftp_delais_boot_pxe=6
	else
		tftp_delais_boot_pxe=$2
	fi
	SETMYSQL tftp_delais_boot_pxe "$tftp_delais_boot_pxe" "delais boot pxe" 7
	 
else
	[ -z "$tftp_delais_boot_pxe" ] && tftp_delais_boot_pxe=6
fi

sed -i "s|###TFTP_DELAIS_BOOT_PXE###|$tftp_delais_boot_pxe|g" /tftpboot/pxelinux.cfg/default


if [ -n "$tftp_pass_menu_pxe" ]; then
# 	pass_en_clair="$tftp_pass_menu_pxe"
	pass_sha1=$(/usr/share/se3/scripts/sha1pass "$tftp_pass_menu_pxe")

	sed -i "s|^#  MENU PASSWD ###TFTP_PASSWORD_MENU_PXE###|  MENU PASSWD ${pass_sha1}|" /tftpboot/pxelinux.cfg/default
fi

depot_sysrcd="/var/www/sysresccd"
if [ -e "${depot_sysrcd}/sysrcd.dat" -a -e "${depot_sysrcd}/sysrcd.md5" -a -e "${depot_sysrcd}/autorun2" -a -e "${depot_sysrcd}/scripts.tar.gz" -a -e "/tftpboot/rescue32" -a -e "/tftpboot/altker32" -a -e "/tftpboot/rescue64" ]; then
	sed -i "s|###SYSRESCCD###||" /tftpboot/pxelinux.cfg/default

	if [ -e /var/www/se3/includes/config.inc.php ]; then
		dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
		dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
		dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
		dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
	else
		echo "Fichier de conf inaccessible"
		exit 1
	fi

	se3ip=$(echo "SELECT value FROM params WHERE name='se3ip';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
	www_sysrcd_ip=$se3ip

	sed -i "s|###WWW_SYSRCD_IP###|${www_sysrcd_ip}|g" /tftpboot/pxelinux.cfg/default

	if [ -e /tftpboot/pxelinux.cfg/linux.menu ]; then
		sed -i "s|###SYSRESCCD###||" /tftpboot/pxelinux.cfg/linux.menu
		sed -i "s|###WWW_SYSRCD_IP###|${www_sysrcd_ip}|g" /tftpboot/pxelinux.cfg/linux.menu
	fi

	if [ -e /tftpboot/pxelinux.cfg/clonage.menu ]; then
		sed -i "s|###SYSRESCCD###||" /tftpboot/pxelinux.cfg/clonage.menu
		sed -i "s|###WWW_SYSRCD_IP###|${www_sysrcd_ip}|g" /tftpboot/pxelinux.cfg/clonage.menu
	fi
fi

	if [ -e /tftpboot/pxelinux.cfg/install.menu ]; then
		sed -i "s|###install-linux###||" /tftpboot/pxelinux.cfg/install.menu
		if [ -e /usr/share/se3/scripts/unattended_generate.sh ]; then
			sed -i "s|###install-win###||" /tftpboot/pxelinux.cfg/install.menu
		fi
	fi
	

depot_clonezilla="/var/se3/clonezilla"
if [ -e "${depot_clonezilla}/vmlinuz" -a -e "${depot_clonezilla}/initrd.img" -a -e "${depot_clonezilla}/filesystem.squashfs" ]; then
	sed -i "s|###CLONEZILLA###||" /tftpboot/pxelinux.cfg/default

	if [ -e /var/www/se3/includes/config.inc.php ]; then
		dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
		dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
		dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
		dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
	else
		echo "Fichier de conf inaccessible"
		exit 1
	fi

	se3ip=$(echo "SELECT value FROM params WHERE name='se3ip';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
	www_clonezilla_ip=$se3ip

	sed -i "s|###WWW_CLONEZILLA_IP###|${www_clonezilla_ip}|g" /tftpboot/pxelinux.cfg/default

	if [ -e /tftpboot/pxelinux.cfg/clonage.menu ]; then
		sed -i "s|###CLONEZILLA###||" /tftpboot/pxelinux.cfg/clonage.menu
		sed -i "s|###WWW_CLONEZILLA_IP###|${www_clonezilla_ip}|g" /tftpboot/pxelinux.cfg/clonage.menu
	fi
fi

depot_clonezilla64="/var/se3/clonezilla64"
if [ -e "${depot_clonezilla}/vmlinuz" -a -e "${depot_clonezilla}/initrd.img" -a -e "${depot_clonezilla}/filesystem.squashfs" ]; then
	sed -i "s|###CLONEZILLA64###||" /tftpboot/pxelinux.cfg/default

	if [ -e /var/www/se3/includes/config.inc.php ]; then
		dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
		dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
		dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
		dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
	else
		echo "Fichier de conf inaccessible"
		exit 1
	fi

	se3ip=$(echo "SELECT value FROM params WHERE name='se3ip';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
	www_clonezilla64_ip=$se3ip

	sed -i "s|###WWW_CLONEZILLA64_IP###|${www_clonezilla64_ip}|g" /tftpboot/pxelinux.cfg/default

	if [ -e /tftpboot/pxelinux.cfg/clonage.menu ]; then
		sed -i "s|###CLONEZILLA64###||" /tftpboot/pxelinux.cfg/clonage.menu
		sed -i "s|###WWW_CLONEZILLA64_IP###|${www_clonezilla64_ip}|g" /tftpboot/pxelinux.cfg/clonage.menu
	fi
fi


if [ -e /usr/share/se3/sbin/se3_verif_dispo_clonage.sh ]; then
	/usr/share/se3/sbin/se3_verif_dispo_clonage.sh >/dev/null
fi

exit 0
