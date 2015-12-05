#!/bin/bash

# $Id$
# Auteur: Stephane Boireau
# Dernière modification: 14/10/2010

# Ajout en visudo:
# Cmnd_Alias SE3CLONAGE=/usr/share/se3/scripts/se3_tftp_boot_pxe.sh,/usr/share/se3/scripts/pxe_gen_cfg.sh,/usr/share/se3/scripts/se3_pxe_menu_ou_pas.sh,/usr/share/se3/scripts/set_password_menu_tftp.sh,/usr/share/se3/scripts/se3_pxe_slitaz_cmdline.sh

for i in default linux.menu clonage.menu
do
	if [ -e /tftpboot/pxelinux.cfg/$i ]; then
		sed -i "s|###TFTP_SLITAZ_CMDLINE###|$1|g" /tftpboot/pxelinux.cfg/$i
	fi
done
