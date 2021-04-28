[deutsch](README_de.md)

![Server](docs/images/header.png)
# Jitsi Manager

[![Contributor Covenant](https://img.shields.io/badge/Contributor%20Covenant-v2.0%20adopted-ff69b4.svg)](code_of_conduct.md)
[![Crowdin](https://badges.crowdin.net/jitsi-admin/localized.svg)](https://crowdin.com/project/jitsi-admin)

Jitsi Manager or Jitsi Admin is a tool to manage Jitsi conferences and server with JWT

### Known from

![Server](docs/images/ct-logo.png)



### Mailing List

If you want to be notified, if there are new updates or releases, you can subscribe to the __Jitsi Admin Update__ mailing list.
H2 invent will send out information to the mailing list concering releases, updates or new features.
This is __not__ a marketing-newsletter.

If you want to join the mailing list just click on the link [Mailing List](https://verteiler.h2-invent.com/?p=subscribe&id=1) and enter your email address.
We use a double-opt-in process where you will receive a confirmation email containing a link with with you confirm, that you want to join to mailing list.

It is always possible to leave the mailing list.

### Partners and Sponsors
<div style="text-align: center">

<img src="https://readi.de/wp-content/uploads/sites/5/2020/10/cropped-Logo-Simplified-mit-Text-e1602047847827.png" width="200px">
<br>
We cooperate with the city administrations of Baden-Baden, Bretten, Bruchsal, Bühl, Ettlingen, Gaggenau, Rastatt, Rheinstetten and Stutensee
</div>

## Translation
Please help us to improve our localiczation

[![Crowdin](https://badges.crowdin.net/jitsi-admin/localized.svg)](https://crowdin.com/project/jitsi-admin)
# Features

The following features are already part of Jitsi Administrator:

* Managing of conferences
* Managing of Jitsi servers with JWT enabled
* Adding participants to a conference
* Sending out emails to the participants
* Sending out email prior to the conference based on a Cron job

### The Dashboard

The Dashboard is the central view where all information to all conferences is displayed
![Dashboard](docs/images/dashboard-heading.jpg)

### The Servers

All servers can be managed centrally and different permissions can be configured.
Multiple Jitsi Servers can be combined to one Setup and managed acordingly
![Server](docs/images/server.jpg)

### Login

The login uses a SSO Server, e.g. Keycloak or other Identidy Providers
![Login](docs/images/login.jpg)

### Join of the conference

Guests are able to join a conference through a link received via email __without__ having a user account in Jitsi Manager.
The is a joining page where the conference ID, the email-address and the name are entered.
After that a JWT will be generated and the guest is able to join the conference.
![Join](docs/images/join.jpg)

User with a user account are able to join conferences directly via Jitsi Manager, either by using the web-page or the Jitsi Electron Desktop App.
![Join](docs/images/joint-internal.jpg)

More information can be found at https://jitsi-admin.de

# Getting Started

As some Composer dependencies need to be installed, it is advised to only install Jitsi Manager if you have shell access to you server.

* [Getting Started ](https://github.com/H2-invent/jitsi-admin/wiki/Get-Started-English)
* [Minimum Requirements](https://github.com/H2-invent/jitsi-admin/wiki/Minimum-server-requirements-English)
* [API Documentation (in German)](https://github.com/H2-invent/jitsi-admin/wiki/API-Endpoints)

# License

Currently Jitsi Admin is released under the [AGPL-3.0 License](https://www.gnu.org/licenses/agpl-3.0.en.html). Additional information can be found in the [LICENSE file](LICENSE).

# Installation
Download the version you want to install or clone the whole repository.
After that execute the following command
```javascript
bash install.sh
```
Follow the instruction in the command window.
# Update
Download the newest version or perform a checkout of the corespong tag.
After that execute the following command
```javascript
bash update.sh
```
