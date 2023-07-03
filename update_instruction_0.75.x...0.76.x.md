## Update Instruction for Version 0.75.x ... 0.76.x

## Whats new
- Start/Pause Function in a Multiframe: Users can now start and stop conferences in a multiframe, providing more control over conference management.

- Automatic Pause of Conferences: When there are two conferences running simultaneously, the top conference will be active while all other conferences will automatically pause. This ensures efficient utilization of resources.

- Redesigned UI/UX: The user interface and user experience have been completely redesigned to enhance usability and visual appeal. Check out the new look in the screenshots below:
- Deputy Functionality: Users can now assign a deputy who can create conferences on their behalf. This is useful when the primary user is unavailable or wishes to delegate conference creation tasks.

- Deputies via LDAP: Deputies can be organized by a support team through LDAP integration, providing centralized management and streamlined administration.

### Improvements:
- Migration to Keycloak > 16 Ready: The application has been prepared for seamless migration to Keycloak version 16 or higher, ensuring compatibility with the latest authentication and authorization features.

- Upgrade to Symfony 6.2: The application has been upgraded to Symfony version 6.2, taking advantage of the latest enhancements and optimizations in the framework. Please note that PHP 8.1 is required for this upgrade.


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


### Update Docker installation from 0.75.x ... 0.76.x

1. Checkout latest Tag 
2. go into the jitsi-admin director e.g. `cd /var/jitsi-admin/`
3. Start the Docker install Script `bash installDocker.sh`
4. All settings should be correct, just hit enter to confirm.
5. Database is automatically upgraded
6. To customize the jitsi-admin to follow your CI-guidelines contact [H2-Invent GmbH](mailto:info@h2-invent.com)
   1. Example:![Screenshot customized jitsi-admin](docs/images/screenshot_CI.png)

