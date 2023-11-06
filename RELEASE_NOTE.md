# Release Notes - Version 0.78.x

## New Features:
- **Dynamic Tag Visibility**: Tags in meetings now appear only when the mouse is active, enhancing a cleaner interface during discussions.
- **Unified Button Appearance**: Conference buttons now match the overall button color scheme and intelligently hide when the mouse is inactive.
- **Direct Dial-in for Open Conferences**: Open rooms now offer direct dial-in information within the conference for added convenience.
- **Filmstrip Toggle**: Users can now remove the filmstrip during a conference via server settings customization.
- **Whiteboard/Etherpad Removal**: Whiteboard removal feature added for all conferences on a virtual server, enhancing customization options.
- **Chat Enablement Control**: Virtual servers can now disable the chat feature through the Jitsi toolbar settings for more tailored experiences.

## Improvements:
- **Enhanced Docker Startup Script**: Docker startup script is now integrated into the Dockerfile for better efficiency.
- **Card Design Enhancement**: Cards now sport a lighter color for a more cohesive Material 3 design experience.
- **Material3 Ready Box Shadow**: Box shadow design now aligns with Material 3 standards for a more consistent visual aesthetic.
- **Improved Multiframe Closure**: Enhancements made for more robust and stable multiframe closures.
- **Enhanced Lobby Invitation Functionality**: Search results can now be navigated and selected using arrow keys and Enter for added user convenience.
- **Upgrade to TWIG3**: Platform updated to TWIG3 for improved performance and features.
- **Manifest Rotation**: Enabling rotation in the Jitsi-admin app for a better user experience.

## Bugs:
- MouseEnter on conference window to work on mobile devices
- Toggle Filmstripe only when is set in the server settings
- Add the server id bevor the room name. use md5 hashed id and server slug
- Remove the server id bevor the room name.

## Discussion:
We highly appreciate your feedback and continually endeavor to enhance our application with each release. Your insights, ideas, and any issues encountered in this new version are crucial in shaping the future of our platform.

Thank you for your continued support and for being an integral part of our community!

Best regards,
Your Jitsi-Admin Team at H2 Invent GmbH hsas