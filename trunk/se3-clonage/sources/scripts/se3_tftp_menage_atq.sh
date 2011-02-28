#!/bin/bash

# $Id$
# Script destiné à faire le ménage dans les tâches planifiées pour ne pas lancer plusieurs fois la même récupération de rapport de sauvegarde/restauration/rapport

if [ -z "$1" ]; then
	echo "USAGE: Passer en paramètre l'identifiant de la machine"
	echo "       où l'identifiant est le champ id de la table 'se3_dhcp'."
	exit
fi

dossier="/etc/se3/www-tools/tftp/$1";

if [ -e "$dossier/at.txt" ]; then
	num=$(grep "job " $dossier/at.txt | cut -d" " -f2)
	if atq | grep "^$num" > /dev/null; then
		atrm $num
	fi
	chown www-se3 $dossier/at.txt
fi
