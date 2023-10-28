# Release Notes - Version 0.77.x

## New Features:
- **Default End-to-End Encryption (E2E):** Enhance your security and privacy with the ability to enable End-to-End Encryption by default. Each server can now configure this feature individually, giving you control over the level of encryption applied to your communications.
- **Transparent Background for Videoconference Tags:** Responding to your feedback, we've introduced a feature that allows you to set tags in videoconferences with a transparent background. This creates a more immersive videoconference experience by maximizing the display area, making your meetings more engaging and effective.
    - Set `LAF_SHOW_TAG_TRANSPARENT_BACKGROUND=1` to display tags in the conference with a transparent background.
- **UI Improvement:** The application now adopts a Flat-UI and Material Design 3 approach with a green background indicating an ongoing meeting, enhancing user experience.
- **Quick Actions:** Added quick actions to swiftly change the camera settings.
- **Conference Mapper:** The conference mapper now selects the user and adds the username instead of the caller ID into the JWT sent to the prosody.
- **Limit Participants in a Conference:** Added the ability to set a maximum number of participants in a conference, imposing a hard limit for better management.
- **IP Restriction:** Admins can now set IP and IP range restrictions for accessing conferences on a Jitsi admin server.
- **Pro Features:** Opened up Pro features for all users, expanding access and functionality.
- **Multiframe Border Color:** Multiframe border color now matches the tag background color for a cohesive visual experience.
- **Add Chrome Plugin API:** Introduced the initial API for the new Chrome browser plugin, enhancing plugin integration.
- **Send Protocol:** Send the conference protocol from the options menu to all participants, improving communication and documentation.
- **Jitsi-Event-Sync Relais Function:** Introduced a Jitsi-Event-Sync relay function for internal purposes, aiding conference mapper requests to another Jitsi admin, saving Jitsi event sync stats.
- **Adding Category for Each Server:** Categories for a meeting can now be bound to a specific server, streamlining category selection based on the chosen server.
- **Enterprise functionality for all:** We open the enterprise functionality to all users. So in the server menu you will see a new enterprise feature button. Feel free to play with it
  - Set a SMTP-server for each jitsi server
  - Set the background-color and background image for every server join page
  - Enable jitsi-admin API
  - Add data privacy url for each server
  - Set jigasi settings for each jitsi server and use the jitsi admin conference mapper
  
## Improvements:
- Updated mdbootstrap to the latest version for enhanced performance and compatibility.
- Added WebSocket in Docker behind the `/ws` route instead of a subdomain, improving routing efficiency.
- Migrated all routing paramconverters into new paramconverters for a more streamlined and efficient application.
- Added the option to show "Add New Participants" in the dropdown option menu, simplifying participant management.
- Implemented the ability to add a category via a command, allowing for settings to be sent directly via the command as parameters.

## Bugs:
- Phone popover is now staying open until the user clicks the phone icon again
- the lobby waiting room is closing now faster
- Sending the protocoll button is now also available on series
- fix special character in the SMTP password when using installDocker.sh file
- Trim email when user is searched and added to the search result list
- Send protocoll can be disabled
- Fix iCal Database Bridge Bug
- Fix IP-check to check all ips in the coma seperated list
- Indexer remove all special characters in special fields
- Search user by mobile phonenumber is not more robust
- Remove newline and whitespace after email

## Discussion:
We highly value your input and continuously strive to improve our application with each release. We invite you to join the discussion and share your ideas, suggestions, or any issues you've encountered in this new version. Your feedback is invaluable in helping us shape the future of our platform.

Thank you for your unwavering support, and for being an essential part of our community!

Best regards,

Your Meeting Team at H2 Invent GmbH
