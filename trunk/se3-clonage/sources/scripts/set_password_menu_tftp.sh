#!/bin/bash

# $Id$
# Auteur: Stephane Boireau
# Dernière modification: 02/11/2010

# Ajout en visudo:
# Cmnd_Alias SE3CLONAGE=/usr/share/se3/scripts/se3_pxe_slitaz_cmdline.sh,/usr/share/se3/scripts/set_password_menu_tftp.sh,/usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh,/usr/share/se3/scripts/se3_tftp_boot_pxe.sh,/usr/share/se3/scripts/pxe_gen_cfg.sh,/usr/share/se3/scripts/recup_rapport.php,/usr/share/se3/scripts/se3_tftp_menage_atq.sh

if [ -e /var/www/se3/includes/config.inc.php ]; then
	dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible"
	exit 1
fi

tftp_delais_boot_pxe=$(echo "SELECT value FROM params WHERE name='tftp_delais_boot_pxe';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
if [ -z "$tftp_delais_boot_pxe" ]; then
	tftp_delais_boot_pxe=8
else
	# Normalement, on a du enregistrer une valeur entiere, mais bon...
	t=$(echo "$tftp_delais_boot_pxe" | sed -e "s|[0-9]||g")
	if [ -n "$t" ]; then
		tftp_delais_boot_pxe=8
	fi
fi

/usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh 'menu' $tftp_delais_boot_pxe

# Dependance pour sha1pass
# apt-get -y install libdigest-sha1-perl

if [ -n "$1" ]; then
	pass_en_clair="$1"
	pass_sha1=$(/usr/share/se3/scripts/sha1pass "${pass_en_clair}")

	sed -i "s|^#  MENU PASSWD ###TFTP_PASSWORD_MENU_PXE###|  MENU PASSWD ${pass_sha1}|" /tftpboot/pxelinux.cfg/default
fi
