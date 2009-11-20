#!/bin/bash

# $Id$
# Script destin� � faire le m�nage dans les t�ches planifi�es pour ne pas lancer plusieurs fois la m�me r�cup�ration de rapport de sauvegarde/restauration/rapport

if [ -z "$1" ]; then
	echo "USAGE: Passer en param�tre l'identifiant de la machine"
	echo "       o� l'identifiant est le champ id de la table 'se3_dhcp'."
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
