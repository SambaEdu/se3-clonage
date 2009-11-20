#!/usr/bin/make -f
# Makefile for se3_clonage

all:

install:
    # Add here commands to install the package into debian/slis-core.
    # Install des fichiers de configuration et de cron.
	cp -R tftpboot $(DESTDIR)/
	cp -R scripts/* $(DESTDIR)/usr/share/se3/scripts/
	cp -R sbin/* $(DESTDIR)/usr/share/se3/sbin/
	cp -R www/* $(DESTDIR)/var/www/se3/
	cp conf/se3_action_tftp_cron $(DESTDIR)/etc/cron.d/
	cp -R doc_post_clonage $(DESTDIR)/var/se3/Progs/install
	cp -R paquet_post-clonage $(DESTDIR)/var/se3/Progs/install
	cp -R quitte_se3 $(DESTDIR)/var/se3/Progs/install
clean:
