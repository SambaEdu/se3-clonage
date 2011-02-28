#!/bin/bash

# $Id$
# Auteur: Stephane Boireau
# Dernière modification: 08/11/2010

# Ajout en visudo:
# Cmnd_Alias SE3CLONAGE=/usr/share/se3/scripts/se3_tftp_boot_pxe.sh,/usr/share/se3/scripts/pxe_gen_cfg.sh,/usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh,/usr/share/se3/scripts/set_password_menu_tftp.sh,/usr/share/se3/scripts/se3_pxe_slitaz_cmdline.sh

if [ "$1" = "menu" ]; then
	cp /tftpboot/tftp_modeles_pxelinux.cfg/menu/* /tftpboot/pxelinux.cfg/
else
	cp /tftpboot/tftp_modeles_pxelinux.cfg/standard/* /tftpboot/pxelinux.cfg/
fi

if [ -n "$2" ]; then
	t=$(echo "$2" | sed -e "s|[0-9]||g")
	if [ -n "$t" ]; then
		tftp_delais_boot_pxe=8
	else
		tftp_delais_boot_pxe=$2
	fi
	sed -i "s|###TFTP_DELAIS_BOOT_PXE###|$tftp_delais_boot_pxe|g" /tftpboot/pxelinux.cfg/default
fi

depot_sysrcd="/var/www/sysresccd"
if [ -e "${depot_sysrcd}/sysrcd.dat" -a -e "${depot_sysrcd}/sysrcd.md5" -a -e "${depot_sysrcd}/autorun2" -a -e "${depot_sysrcd}/scripts.tar.gz" -a -e "/tftpboot/rescuecd" -a -e "/tftpboot/altker32" -a -e "/tftpboot/rescue64" ]; then
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
