#!/bin/sh
#
## $Id$ ##
#
##### Permet de faire, l'activation ou la désactivation de tftp_boot #####

# Franck Molle - 12/2006...
LADATE=$(date +%x)
REPORT_FILE="/root/mailtoadmin"
echo "" > $REPORT_FILE
DEBUG="0"

# Script inetd etch ou lenny ?
INETD_SCRIPT="$(find /etc/init.d/ -name "*inetd*")"

if [ "$(cat /etc/debian_version)" == "4.0" ]; then
UDP="udp"
else
UDP="udp4"
fi
# 
# 
# TFTP_ETCH="tftp dgram udp wait nobody /usr/sbin/tcpd /usr/sbin/in.tftpd --tftpd-timeout 300 --retry-timeout 5     --mcast-port 1758 --mcast-addr 239.239.239.0-255 --mcast-ttl 1 --maxthread 100 --verbose=5  /tftpboot"
# 
# TFTP_LENNY="tftp dgram udp4 wait nobody /usr/sbin/tcpd /usr/sbin/in.tftpd --tftpd-timeout 300 --retry-timeout 5 --mcast-port 1758 --mcast-addr 239.239.239.0-255 --mcast-ttl 1 --maxthread 100 --verbose=5 /tftpboot"

MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE)
	#On envoie un mail à l'admin
	echo "$REPORT"  | mail -s "[SE3] Résultat de $0" $MAIL_ADMIN
fi
}

# 
# LINE_TEST()
# {
# ping -c1  www.google.fr >/dev/null
# if [ "$?" != "0" ]; then
# 	echo "Votre connexion internet ne semble pas fonctionnelle !!" | tee -a $REPORT_FILE
# 	MAIL_REPORT
# 	exit 1
# fi
# }
case "$1" in
	start)
	if [ -z "$(grep tftp /etc/inetd.conf)" ]; then 
	echo "tftp            dgram   $UDP     wait    nobody /usr/sbin/tcpd /usr/sbin/in.tftpd --tftpd-timeout 300 --retry-timeout 5     --mcast-port 1758 --mcast-addr 239.239.239.0-255 --mcast-ttl 1 --maxthread 100 --verbose=5  /tftpboot" >> /etc/inetd.conf
	else
	sed "s/\/var\/lib\/tftpboot/\/tftpboot/" -i /etc/inetd.conf 
	fi
	echo "Activation de atftpd" | tee -a $REPORT_FILE
	$INETD_SCRIPT restart
	;;
	
	stop)
	sed -e "/tftp/d" -i /etc/inetd.conf
	echo "Arrêt du serveur Tftp" | tee -a $REPORT_FILE
	killall in.tftpd 2>/dev/null
	$INETD_SCRIPT restart
	;;
	
	*)
	echo "Script permettant l'activation d'un serveur tftp avec une archive udpcast"
	echo "afin de cloner les postes clients sans cd :)"
	echo ""
	echo "Usage: $0 {start|stop}"; exit 1
	;;
esac
[ "$DEBUG" == "1" ] && MAIL_REPORT
exit 0