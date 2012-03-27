#!/bin/bash

# $Id$
#
# Script à lancer régulièrement pour scruter le /var/log/syslog
# de façon à trouver si un boot a été effectué sur le choix indiqué en
# /tftpboot/pxelinux.cfg/01-$MAC depuis la dernière action programmée.
# Si oui, on supprime le /tftpboot/pxelinux.cfg/01-$MAC pour ne pas relancer la tâche
#
# Auteur: Stephane Boireau
# Dernière modification: 05/02/2008
#
# Attention: Si on modifie le /etc/inetd.conf en ajoutant un --logfile /var/log/atftpd.log pour atftpd
#            et touch /var/log/atftpd.log && chown nobody:nogroup /var/log/atftpd.log
#            le fichier de conf à considérer n'est plus le même.
#            Ce changement permet d'alléger le syslog... et rendre les recherches grep moins lourdes,
#            mais chez moi la modif n'a pris qu'après un redémarrage de la machine.
#            (un killall -HUP inetd n'a pas suffit)
logfile=/var/log/syslog

# echo "*/2 * * * * root /usr/share/se3/sbin/controle_actions_tftp.sh" > /etc/cron.d/se3_action_tftp

test_mysql=$(ps aux | grep "/usr/sbin/mysqld" | grep "/var/run/mysqld/mysqld.pid" | grep -v "grep")
if [ -z "$test_mysql" ]; then
	# MySQL ne tourne pas, on ne va pas plus loin
	exit
fi

#Couleurs
COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu
COLTXT="\033[0;37m"     # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert
COLCMD="\033[1;37m"     # Blanc
COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan

ERREUR()
{
echo -e "$COLERREUR"
echo "ERREUR!"
echo -e "$1"
echo -e "$COLTXT"
exit 1
}

FICHDEBUG()
{
	active_debug="n"
	if [ "$active_debug" = "y" ]; then
		echo "$1" >> /var/log/atftpd_se3_actions.log
	fi
}

WWWPATH="/var/www"

if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f2 | cut -d \" -f2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 | cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 | cut -d \" -f 2`
else
	ERREUR "Fichier de configuration inaccessible, le script ne peut se poursuivre."
fi

#verif=$(echo "SHOW TABLES LIKE se3_tftp_action;" | mysql -h $dbhost -u$dbuser -p$dbpass $dbname)
#if [ -z "$verif" ]; then
if [ ! -e "/var/lib/mysql/se3db/se3_tftp_action.frm" ]; then
	# Aucune action n'a encore été initialisée et la table n'existe pas.
	exit
fi

FICHDEBUG "=============================="
FICHDEBUG "$(date)"
FICHDEBUG "SELECT DISTINCT mac FROM se3_tftp_action;"
#FICHDEBUG "SELECT DISTINCT mac FROM se3_tftp_action UNION SELECT DISTINCT mac FROM se3_tftp_rapports;"
#echo "SELECT DISTINCT mac FROM se3_tftp_action UNION SELECT DISTINCT mac FROM se3_tftp_rapports;" | mysql -h $dbhost -u$dbuser -p$dbpass $dbname | while read mac
echo "SELECT DISTINCT mac FROM se3_tftp_action;" | mysql -h $dbhost -u$dbuser -p$dbpass $dbname | while read mac
do
	# Pour passer la première ligne indiquant le nom des champs
	if [ "$mac" != "mac" -a ! -z "$mac" ]; then
		FICHDEBUG "mac=$mac"
		corrige_mac=$(echo "$mac" | tr ":[A-Z]" "\-[a-z]")
		FICHDEBUG "corrige_mac=$corrige_mac"
		FICHDEBUG "SELECT date FROM se3_tftp_action WHERE mac='$mac';"
		echo "SELECT date FROM se3_tftp_action WHERE mac='$mac';" | mysql -h $dbhost -u$dbuser -p$dbpass $dbname | while read date
		do
			# Pour passer la première ligne indiquant le nom des champs
			if [ "$date" != "date" -a ! -z "$date" ]; then
				FICHDEBUG "date=$date"
				grep atftp $logfile | sed -e "s/ \{2,\}/ /g" | grep -i "Serving pxelinux.cfg/01-${corrige_mac} to " | cut -d" " -f1-3 | while read A
				do
					FICHDEBUG "log_date=$A"
					date_pxeboot=$(date --date="$A" +%s)
					FICHDEBUG "date_pxeboot=$date_pxeboot"
					if [ ${date_pxeboot} -ge ${date} ]; then
						echo "DELETE FROM se3_tftp_action WHERE mac='$mac' AND date='$date';" | mysql -h $dbhost -u$dbuser -p$dbpass $dbname
						FICHDEBUG "DELETE FROM se3_tftp_action WHERE mac='$mac' AND date='$date';"
						rm /tftpboot/pxelinux.cfg/01-${corrige_mac}
						#mv /tftpboot/pxelinux.cfg/01-${corrige_mac} /tftpboot/pxelinux.cfg/01-${corrige_mac}_servi_le_$(echo "$A" | tr " :" "_\-")
					fi
				done
			fi
		done
	fi

	# Remarque: On ne devrait normalement ne faire qu'un tour au plus dans chaque boucle mac et date.
done

