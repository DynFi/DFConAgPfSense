# DFConAgPfSense
DynFi Manager Connection Agent Plugin for the pfSense® firewalls.  
<br>

## What is the DynFi Connection Agent?  
The Connection Agent (ConAg) is a plugin available for pfSense® that extends the functionality of your firewall by automating its connection to [DynFi Manager](https://dynfi.com/dynfi-manager). 
[DynFi Manager](https://dynfi.com/dynfi-manager/) is the only central management solution targeted for pfSense®-CE and OPNsense® firewall devices. 

Thanks to DFConAgPfSense your firewalls will auto provision themselves to your DynFi Manager, greatly simplifying the deployment and maintenance of your firewall fleet.  
<br>

## How does the Connection Agent work?
The Connection Agent is a plugin that includes a Graphical User Interface (GUI) to automatically drive the connection to [DynFi Manager](https://dynfi.com/dynfi-manager/). 
  
It uses the [Auto SSH program](https://www.freebsd.org/cgi/man.cgi?query=autossh&sektion=1&manpath=FreeBSD+13.0-RELEASE+and+Ports). 
Autossh is a tool to ensure that an SSH session stays open at all times by testing it and restarting it if necessary. 
This allows our Connection Agent to keep a session open at all times with the DynFi Manager. 

The source code for the pfSense® plugin is available hereunder. 
It integrates seamlessly with the pfSense®-CE systems.  
If you have choosed the OPNsense® distribution as your main firewall distribution, please use our [OPNsense® plugin](https://github.com/DynFi/DFConAgOPNsense/). 
<br>

## On DynFi Firewall the agent is pre-installed
If you are looking for a native FreeBSD® based firewall distribution that **natively supports DynFi Manager**, please consider using our [DynFi Firewall](https://dynfi.com/dynfi-firewall/) distribution. 
It is Open Source and readily available.  
<br>

[DynFi Firewall](https://dynfi.com/dynfi-firewall/) firewall **natively includes the DFConAg**.
You can [download and install](https://dynfi.com/download/) the DynFi Firewall very easily.  
<br>  

## Install the connection agent on pfSense:
It is very easy to install the DynFi Connection Agent on pfSense-CE firewalls by following the instructions below:  
  
1. Log into your firewall using the "admin" account. 
2. Go to the "Diagnostics >> Command Prompt" section.
3. Copy / paste the line below
4. Click on "Execute."  
<br> 

```bash
  wget -O - https://dynfi.com/connection-agent/download/pfsense/dfconag-latest-installer.sh | sh  
```  
  
<br> 

![Installing DynFi Connexion Agent on pfSense](https://dynfi.com/img/DynFi_Manager/pfSense_connection_agent.png "Install DynFi Connexion Agent on pfSense-CE") 

Alternatively, it is also possible to run this command directly from an SSH connection on your firewall with the user "admin". 
Simply copy/paste the above line and run the command.   
<br> 

## Documentation for DynFi Connection Agent
You can access the [DynFi Manager Connection Agent documentation here](https://dynfi.com/documentation/) 
  
