# Features, Improvements, and Bug Fixes in Jitsi Admin

## 1.5
### 🚀 Features:
* Added Transcription functionality. Uses OpenAI Whisper to transcribe recordings to text
* Add E2EE to server and conference setting and JWT
* Revert layout change for Manage Participants dialog and implemented associated Ajax functionality
* SIP dial-in now distinguishes between rooms with and without a lobby. `/api/v1/lobby/sip/room/{roomId}` returns the new field `lobby_enabled` and points to the matching follow-up endpoint: rooms with a lobby use `/api/v1/lobby/sip/protected/{roomId}` and require the personal pin, rooms without a lobby use the new `/api/v1/lobby/sip/open/{roomId}` and connect the caller directly, without a lobby entry and without a caller session
* SIP_CALLER_SHOW_IN_FRONTEND` now also controls the dial-in mode, not just the display. While it is disabled, rooms **with** an active lobby answer `HANGUP` / `NO_PIN_CONFIGURED`, because without the personal pin the lobby cannot be passed. Rooms without a lobby are unaffected
* SIP dial-in is blocked for conferences with active end-to-end encryption. The dashboard shows the dial-in numbers struck through and explains why the dial-in is not possible
* Add E2EE to server and conference setting and JWT (1.5.13)

### 🐛 Bug Fixes:
* Prevent server change for active meetings rooms
* Fix duplicate recording API calls
* Fix button layout/rendering in all lobby instances
* Fix incorrect address book contact name display
* Fix broken render of address book panel after Ajax contact addition
* Fix stopping a second recording and recording file storage
* Fix loki log level not honoring config
* Fix renaming database columns in migrations to work on all database platforms (1.5.1)
* Fix deleting contacts in the address book (1.5.2)
* Fix multiframe not showing absolute (1.5.3)
* Fix initial server command (1.5.3)
* Fix display webcam preview (1.5.4)
* Fix not opening multiframe when moderator opens multiframe (1.5.5)
* Fix small issues in transcription service, conference sidebar and layout (1.5.7)
* Fix Phone Modal to be able to close (1.5.8)
* Fix sorting for conferences without date (1.5.8)
* Fix Showing Closed and 1 participant in conference (1.5.8)
* Fix iCal export by reverting the query to its original state without deputies (1.5.9)
* Fix wrong start time in dashboard (1.5.10)
* Fix minimizing the multiframe (1.5.15)
* Fix double directory when uploading theme assets (1.5.16)

### ⭐ Improvements:
* Performance increase in Dashboard page loading time
* Add lobby moderator permission flag to the JWT
* Redesigned homepage
* Fix appointment modal
* Adressbook refactoring
* Change Drag and Drop Lib back to inteact js because it is more robust
* SIP dial-in via the lobby using Livekit
* The caller api authorizes against the api key of the room's server instead of one global secret
* The personal sip pin is only sent in invitation mails and shown in the dashboard while `SIP_CALLER_SHOW_IN_FRONTEND` is enabled. The room number is no longer labelled as pin in both places
* Removed Matomo dependency
* Socket.io cluster support for the websocket server
* Refactored analytics service (1.5.6)
* SIP dial-in via the lobby using Livekit (1.5.12)
* Change Drag and Drop Lib back to interact.js because it is more robust (1.5.13)
* Add and remove address book favorites via Ajax (1.5.13)
* Revert layout change for Manage Participants dialog and implemented associated Ajax functionality (1.5.14)

### ⚠️ Deprecations:
* `/api/v1/conferenceMapper` is deprecated. Use `/api/v1/lobby/sip/open/{roomId}` instead, which returns the same payload
* `/api/v1/lobby/sip/pin/{roomId}` is deprecated in favour of `/api/v1/lobby/sip/protected/{roomId}`. The old path keeps working and serves the same endpoint
* Authorizing the caller api with the global `SIP_CALLER_SECRET` is deprecated. It is still accepted so existing asterisk configurations keep working, but every use logs a warning. Configure the api key on the server instead
