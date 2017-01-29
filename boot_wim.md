# mise en place d'un menu d'installation automatique W7/W10 basé sur MDT (wim)

reprise du méssage de Romain Ferry, commenté par Franck :


> Je vous détaille ici la solution mise en place :
>
>   * Pour utiliser l'image wim de démarrage MDT (près de 400Mo), j'utilise
>     wimboot (http://ipxe.org/wimboot). Je mets l'ensemble à disposition en
>     protocole HTTP (dans /var/www/se3/iPXE)
>       o /var/www/se3/iPXE/
>           + boot/
>               # bcd
>               # boot.sdi (récupérés tous les 2 dans le master MDT)
>           + sources/
>               # boot.wim (notre fichier généré par MDT : LiteTouchPE_x64.wim)
>           + wimboot (actuellement en version 2.4.1)
>           + boot.ipxe (script de démarrage)
>
>                 #!ipxe
>                 kernel wimboot
>                 initrd boot/bcd    BCD
>                 initrd boot/boot.sdi    boot.sdi
>                 initrd sources/boot.wim    boot.wim
>                 boot
on a une page dans se3-clonage qui permet de charger tel ou tel dispositif tel sysrescd, clonezilla, etc.... Il est donc possible d'ajouter un élément de plus à partir du moment ou l'on sait quoi télécharger et ou le déposer. Ensuite on complète la bdd en conséquence afin que la page de conf du tftp génère le bon menu tftp derrière. Les scripts sont ici
https://github.com/SambaEdu/se3-clonage/tree/master/sources/scripts

>
>   * Pour chaîner le démarrage, j'utilise  le kernel undionly.kpxe (généré depuis
>     https://rom-o-matic.eu/), que je place dans /tftpboot. Je crée un lien
>     symbolique sur le kernel : /tftpboot/undionly.0 pointant sur undionly.kpxe
>
>     La difficulté majeure réside ici : une fois le kernel undionly chargé, le
>     client refait une requête DHCP. Il faut donc que le client récupère
>     l'adresse du script boot.ipxe (http://IP-SE3:909/iPXE/boot.ipxe). 2
>     solutions s'offrent à nous.
>
>       o Intégrer l'adresse de ce script dans le kernel undionly.kpxe lors de sa
>         génération (ce que je fais)
>       o Ajouter une ligne de configuration dans le DHCP, ce qui permet
>         d'utiliser un undionly.kpxe complètement générique. Seul problème, cette
>         ligne "saute" à chaque fois que les paramètres DHCP sont modifiés ou que
>         le serveur est mis à jour (raisons du choix précédent) :
Dans ce cas, on peut modifier le script qui génère la conf. Il s'agit d'un script perl. Il est ici :
https://github.com/SambaEdu/se3-dhcp/blob/master/sources/scripts/makedhcpdconf


>           + Edition de /etc/dhcp/dhcpd.conf :
>             Après la ligne next-server…, on remplace la ligne
>              filename "pxelinux.0";
>             par :
>              if exists user-class and option user-class = "iPXE" {
>                  filename "http://IP:909/iPXE/boot.ipxe";
>              } else {
>                  filename "pxelinux.0";
>              }
>   * Je modifie /tftpboot/pxelinux.cfg/default, pour lui ajouter :
>
>         LABEL Lite Touch Install (MDT) - Masters Alsace -  CG 67 -  CG 68
>           MENU LABEL ^Lite Touch Install (MDT)
>           KERNEL undionly.0

Ce point peut être pris en charge par se3-clonage

>
>     Il est probable que cette entrée "saute" également lors de la modification
>     des paramètres TFTP du serveur SE3, mais je n'ai pas encore rencontré ce
>     problème.

Oui cela va sauter lors de la validation du formulaire de configuration au niveau de la conf du mot de passe et le délai de l'ecran de boot pxe.

C'est ce script qui gère cela de mémoire :
https://github.com/SambaEdu/se3-clonage/blob/master/sources/scripts/se3_pxe_menu_ou_pas.sh

C'est une partie que je connais assez bien, donc je peux aider sur l'intégration.

Il y aura aussi une page php à modifier pour gérer le téléchargement de l'image d'installation. C'est elle qui lance le script sudo qui fait les wget nécessaires.
