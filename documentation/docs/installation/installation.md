---
sidebar_position: 1
---

# Overview

We break down the installation of the jisti-admin in seperate steps.
We only explain the installation for a brand new __Debian 11__ installation. 

For any other linux distributions you have to transfer the instruction to your needs.

:::danger Security Advise
The installation is not hardened.
:::

Prerequisites:
Two Servers with Debian 11:
1. Keycloak-Server
2. Jitsi-Admin Server
3. URL for the Jisti-Admin (eg.jitsi-admin.de). Pointing to the IP address of the jitsi-admin server
4. URL for the mercure hub (eg.mercure.jitsi-admin.de). Pointing to the IP address of the jitsi-admin server
5. URL for the Keycloak-Server (e.g. keykloak.jisti-admin.de) Pointing to the IP address of the keycloak server


These installation steps are:
1. Installation of Keycloak and Setting up the Realm
2. Installation of the Jitsi-Admin
3. Setting up Jitsi-Admin Keycloak-Realm
4. Setting up  SMTP-Settings
5. Installation of the mercure hub
6. Configuration of the mercure Hub
7. Installation of Asynchronous Services
8. Generating a Jitsi-Server for all Users