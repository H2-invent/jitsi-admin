# Release Notes - Version 1.1.x

## New Features:
* **Livekit Recording Implementation**:
    * Recordings can be uploaded in chunks to Jitsi Admin.
    * Recordings can be started directly from a conference.
    * An email is sent after the recording is uploaded to your Jitsi Admin.
    * Recordings are stored in the PHP filesystem (Gaufrette).
    * Recordings can be removed after a set period of time.

* **Calendly Integration**:
    * Calendly can now be integrated, so your appointments from Calendly are automatically included in your conferences.

* **Ad-hoc Conference Creation**:
    * Added the ability to create an ad-hoc conference to start an immediate conference and invite others by sharing the link without scheduling.

## Improvements:
* Refactor of the lobby interface.
* Improved rendering speed for multi-frame displays.
* Enhanced toast notifications design.
* Upgraded to a new version of the MDBootstrap library with modern triggers.
* Better invitation and participant management without requiring text areas.
* Improved pause and play functionality for conferences.
* Upgraded to Symfony 7.1.
* Public conferences can now be shown without scheduling, just like Jitsi.
* Migrated to the latest cron library to match process signatures.
* Made the Webhook controller more robust for Livekit webhooks.

### Docker Improvements:
* Upgraded Keycloak to version 26.
* Moved more environment variables to the `.env` file instead of exporting them manually.

## Bug Fixes:
* Various bug fixes to improve stability and performance.

## Discussion:
We highly value your feedback and suggestions, which play a crucial role in shaping our platform. Your insights, ideas, and any encountered issues in this version are instrumental in guiding our future enhancements.

Thank you for your ongoing support and for being an essential part of our community!

Best regards,  
Your Jitsi-Admin Team at H2 Invent GmbH
