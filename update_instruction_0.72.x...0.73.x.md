## Update Instruction for Version 0.72.x to 0.73.x

1. Install new jitsi-admin files `php compser.phar install`
2. Install new NPM packages `npm install`
3. Migrate Database to the latest version: `php bin/console doc:mig:mig`
4. Start a Worker Service to do all the async work.
   1. We added a systemd file to the repo which can be used as a blueprint.
   2. The systemd file is stored in `jitsi-admin_messenger.service`
   3. __Check the directorys in the file if they are matching your installation__
   4. To use the file in your system do:
      1. Copy the file to the systemd directory`cp jitsi-admin_messenger.service /etc/systemd/system/jitsi-admin_messenger.service`
      2. reload system daemon `sudo systemctl daemon-reload`
      3. Start new Service `service start jitsi-admin_messenger`
      4. Enable service to restart automatialy `service enable jitsi-admin_messenger`
5. If you want to send your emails async then uncomment the following line in `config/packages/messenger.yaml` file
   1. ```
      #config/packages/messenger.yaml
      framework:
      ...
        messenger:
        ...
            rounting:
            Symfony\Component\Mailer\Messenger\SendEmailMessage:  async
      ```
   2. Rebuild js and css `npm run build`
   3. To customize the jitsi-admin to follow you CI-guidlines contact [H2-Invent GmbH](mailto:info@h2-invent.com)
      1. Example:![](docs/images/screenshot_CI.png)