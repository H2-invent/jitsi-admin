## Update Instruction for Version 0.78.x ... 0.79.x


## New Features:
- **Toggle Filmstripe**: A new button has been introduced in the conference interface, allowing users to toggle the Filmstripe view on or off easily.
- **Prefix Room Name**: Rooms now support a server hash prefix feature, enabling the loading of different Jitsi configurations during runtime.

## Improvements:
- **Autoupload to Dockerhub**: Jitsi admin now includes an automatic upload functionality to Docker Hub, streamlining the deployment process.
- **Addressbook in Dark Mode**: The Addressbook feature now comes with a transparent background when in dark mode, enhancing visibility and aesthetics.
- **Addressbook Filter Enhancement**: The address book's filtering mechanism has been improved to search the entire row, enhancing its usability beyond checkbox-only search.
- **Upgrade MDBootstrap**: Updated the MDBootstrap library to its latest version for improved performance and compatibility.
- **Enhanced Fullscreen Mode**: Made the Fullscreen mode in multiframe mode more robust, ensuring a smoother and more reliable experience.
- **Improved Multiframe Closing**: Users can now close a multiframe even if its internal content has not loaded, improving the overall user experience.

## Bugs:
nothing here

# Update
__Drop PHP8.0 support. Use PHP8.1 or better php8.2__
__The installer is now using nginx so please remove Apache when you use it__

__Node ^18.0 and npm ^9.5 have to be installed__

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


### Update Docker installation from 0.78.x ... 0.79.x

1. Checkout latest Tag 
2. go into the jitsi-admin director e.g. `cd /var/jitsi-admin/`
3. Start the Docker install Script `bash installDocker.sh`
4. All settings should be correct, just hit enter to confirm.
5. Database is automatically upgraded
6. To customize the jitsi-admin to follow your CI-guidelines contact [H2-Invent GmbH](mailto:info@h2-invent.com)
   1. Example:![Screenshot customized jitsi-admin](docs/images/screenshot_CI.png)

