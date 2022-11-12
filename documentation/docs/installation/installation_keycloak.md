---
sidebar_position: 2
---

# Installation Keycloak

To login into the Jitsi-Admin the Keycloak SSO-Service is used.
This means the Jitsi-Admin has no own user authentication mechanism.

## Step 1 — Install JDK
Keycloak requires Java 8 or later versions to work. You can check and verify that Java is installed with the following command.

`$ java -version`

If java is not installed, you will see “java: command not found”. Run below commands to install Java.
```
$ sudo apt-get update
$ sudo apt-get install default-jdk -y
```
After installation, check if java is installed correctly by executing below command

`$ java -version`

## Step 2 — Download and Extract Keycloak Server

Check Keycloak downloads page for latest releases before downloading. For this tutorial, we will download Keycloak 18.0.1 Standalone Server Distribution.

We are going to install Keycloak to /opt directory, so we will download the Keycloak package to that location.

Change directory to /opt and download Keycloak to that directory.
```
$ cd /opt
$ sudo wget https://downloads.jboss.org/keycloak/6.0.1/keycloak-6.0.1.tar.gz
```
Extract the tar package and rename the extracted directory to keycloak. This will be Keycloak’s installation directory

$ sudo tar -xvzf keycloak-6.0.1.tar.gz
$ sudo mv keycloak-6.0.1 /opt/keycloak
