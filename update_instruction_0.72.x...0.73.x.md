## Update Instruction for Version 0.72.x ... 0.73.x
1. Go into the directory where your jitsi-admin is installed e.g. `cd /var/www/html`
2. Install new jitsi-admin files `php compser.phar install`
3. Install new NPM packages `npm install`
4. __(Optional)__ Backup your database
5. Migrate Database to the latest version: `php bin/console doc:mig:mig`
6. Install a Worker Service to do all the async work.
   1. We added a systemd file to the repo which can be used as a blueprint.
   2. The systemd file is stored in [`jitsi-admin_messenger.service`](jitsi-admin_messenger.service)
   3. The systemd file is build for a standard linux installation
   4. __Check the directory's in the file if they are matching your installation__
   5. To use the file in your installation:
      1. Copy the file to the systemd directory`cp jitsi-admin_messenger.service /etc/systemd/system/jitsi-admin_messenger.service`
      2. reload system daemon `sudo systemctl daemon-reload`
      3. Start new Service `service start jitsi-admin_messenger`
      4. Enable service to restart automatialy `service enable jitsi-admin_messenger`
7. __(Optional)__ If you want to send your emails async then uncomment the following line in `config/packages/messenger.yaml` file
   1. ```
      #config/packages/messenger.yaml
      framework:
      ...
        messenger:
        ...
            rounting:
            Symfony\Component\Mailer\Messenger\SendEmailMessage:  async
      ```
8. Rebuild js and css `npm run build`
9. Clean cache `php bin/console cache:clear`
10. Set the permission `sudo chown -R www-data:www-data var/`
11. Check your email settings with the command `php bin/console app:email:test <serverId> <email@domain.de>`. You should receive a test email
12. To customize the jitsi-admin to follow your CI-guidelines contact [H2-Invent GmbH](mailto:info@h2-invent.com)
    1. Example:![Screenshot customized jitsi-admin](docs/images/screenshot_CI.png)

### Update Docker installation from 0.72.x ... 0.73.x

1. Checkout latest Tag 
2. go into the jitsi-admin director e.g. `cd /var/jitsi-admin/`
3. Shutdown existing jitsi-admin installation `docker-compose down`
4. Start the Docker install Script `bash installDocker.sh`
5. All settings should be correct, just hit enter to confirm.
6. You have now two more worker container, doing async stuff
7. Database is automatically upgraded
8. To customize the jitsi-admin to follow your CI-guidelines contact [H2-Invent GmbH](mailto:info@h2-invent.com)
   1. Example:![Screenshot customized jitsi-admin](docs/images/screenshot_CI.png)