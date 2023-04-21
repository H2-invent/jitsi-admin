## Update Instruction for Version 0.74.x ... 0.75.x

## Whats new
* Window in Window of conferences (Multiframing)
* Integrate any application you use in a frame and open it during the conference.
* Status view (online, offline, in a meeting)
* Whiteboard functionality
* New real websocket (replace Mercure Hub)
* New addressbook (Search, Index etc.)
* Send messages to waitung users in the Lobby
* Invite new participants directly from the conference
* New error pages
* Public Conference as you are used from original jitsi. Just type a name and share this link with your audience. But secured with a JWT.
* New installer to install the jitsi admin with one command

__Drop PHP7.4 support. Use PHP8.0 or better php8.1__
__The installer is now using nginx so please remove Apache when you use it__

__Node ^16.15 and npm ^8.5 have to be installed__

__Prequrequesits for the installer: jitsi-admin is installed in /var/www/jitsi-admin__
If not then copy your old `.env.local` file into `/var/www/jitsi-admin` to save your existing settings

1. 
```bash
cd ~
wget https://github.com/H2-invent/jitsi-admin/raw/master/install.sh
sudo bash install.sh
```
2. Start the Updater with `sudo bash install.sh` and follow the steps
3. If you want to use the whiteboard or/and etherpad integration set the paramter in the .env.local file
```
### WHITEBOARD If you want to integrate Whitebophir (https://github.com/lovasoa/whitebophir)
LAF_WHITEBOARD_FUNCTION=1
WHITEBOARD_URL=https://wbo.domain.de
WHITEBOARD_SECRET=MY_SECRET

### ETHERPAD If you want to inegrate Etherpad in the Jitsi-Admin
LAF_ETHERPAD_FUNCTION=1
ETHERPAD_URL=https://etherpad.domain.de
### <ETHERPAD
```
4. To customize the jitsi-admin to follow your CI-guidelines contact [H2-Invent GmbH](mailto:info@h2-invent.com)
    1. Example:![Screenshot customized jitsi-admin](docs/images/screenshot_CI.png)


### Update Docker installation from 0.74.x ... 0.75.x

1. Checkout latest Tag 
2. go into the jitsi-admin director e.g. `cd /var/jitsi-admin/`
3. Start the Docker install Script `bash installDocker.sh`
4. All settings should be correct, just hit enter to confirm.
5. You have now two more worker container, doing async stuff
6. Database is automatically upgraded
7. To customize the jitsi-admin to follow your CI-guidelines contact [H2-Invent GmbH](mailto:info@h2-invent.com)
   1. Example:![Screenshot customized jitsi-admin](docs/images/screenshot_CI.png)

