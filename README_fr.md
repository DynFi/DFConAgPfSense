# DFConAgOPNsense
Agent de Connexion DynFi Manager pour les pare-feux (firewalls) OPNsense®.
<br>

## Qu'est-ce que l'Agent de Connexion DynFi ?  
L'Agent de Connexion DynFi est un plugin développé pour les firewalls pfSense®-CE qui permet d'étendre les fonctionnalités de votre firewall en automatisant sa connexion auprès de notre [gestionnaire de pare-feu DynFi Manager](https://dynfi.com/dynfi-manager/). 
Grâce à ce Plugin, vos firewalls se provisionnent automatiquement auprès de votre Gestionnaire de Firewalls DynFi, simplifiant grandement le déploiement et la maintenance de votre flotte de pare-feu.  
<br>

## Comment fonctionne l'Agent de Connexion ?
L'agent de connexion se présente sous la forme d'un Plugin, il inclut une interface graphique permettant de piloter automatiquement la connexion du firewall vers le [DynFi Manager](https://dynfi.com/dynfi-manager/). 
  
Il utilise pour cela le programme [Auto SSH](https://www.freebsd.org/cgi/man.cgi?query=autossh&sektion=1&manpath=FreeBSD+13.0-RELEASE+and+Ports). 
Autossh est un outil permettant de s'assurer qu'une session SSH reste ouverte en permanence en la testant et en la relançant si nécessaire. 
Ceci permet à notre Agent de Connexion de maintenir une session ouverte en permanence avec le Gestionnaire DynFi Manager. 
  
Le code source du plugin pfSense® est présenté ici, une version compatible avec [OPNsense® est aussi disponible sur GitHub](https://github.com/DynFi/DFConAgOPNsense/).  
<br>  

## Sur DynFi Firewall l'agent est pré-installé
Le plugin DFConAg est inclus de façon native dans notre distribution de firewall dérivée d'OPNsense® : [DynFi Firewall](https://dynfi.com/dynfi-firewall/).  
<br>  

Vous pouvez [télécharger et installer](https://dynfi.com/download/) le pare-feu DynFi Firewall très simplement.  
<br>  

## Installer l'agent de connexion sur  pfSense®-CE :  
Il est très simple d'installer l'Agent de Connexion DynFi sur les firewalls pfSense-CE en suivant les instructions figurant ci-dessous :  
  
1. Connectez-vous à votre pare-feu à l'aide du compte "admin". 
2. Rendez-vous dans la section "Diagnostics >> Command Prompt".
3. Copier / coller la ligne ci-dessous
4. Cliquer sur "Execute"  
<br> 

```bash
  wget -O - https://dynfi.com/connection-agent/download/pfsense/dfconag-latest-installer.sh | sh  
```  
  
<br>  ![Installation de DynFi Connexion Agent sur pfSense](https://dynfi.com/img/DynFi_Manager/pfSense_connection_agent.png "Installer DynFi Connexion Agent sur pfSense-CE") 

Alternativement, il est aussi possible de directement exécuter cette commande depuis un shell SSH initiée avec l'utilisateur "admin". 
Copier / coller simplement la ligne ci-dessus et lancez la commande.   
<br> 

## Documentation complète de l'Agent de Connexion DynFi
Pour plus de détails sur le fonctionnement de l'Agent de Connexion, reportez-vous à la [documentation en ligne de DynFi Manager](https://dynfi.com/documentation/) 
  
