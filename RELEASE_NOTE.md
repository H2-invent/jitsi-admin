# Features, Improvements, and Bug Fixes in Jitsi Admin

## 1.5
### 🚀 Features:
* Added Transcription functionality. Uses OpenAI Whisper to transcribe recordings to text
* SIP dial-in now distinguishes between rooms with and without a lobby. `/api/v1/lobby/sip/room/{roomId}` returns the new field `lobby_enabled` and points to the matching follow-up endpoint: rooms with a lobby use `/api/v1/lobby/sip/protected/{roomId}` and require the personal pin, rooms without a lobby use the new `/api/v1/lobby/sip/open/{roomId}` and connect the caller directly, without a lobby entry and without a caller session
* `SIP_CALLER_SHOW_IN_FRONTEND` now also controls the dial-in mode, not just the display. While it is disabled, rooms **with** an active lobby answer `HANGUP` / `NO_PIN_CONFIGURED`, because without the personal pin the lobby cannot be passed. Rooms without a lobby are unaffected

### 🐛 Bug Fixes:
* Prevent server change for active meetings rooms
* Fix duplicate recording API calls
* Fix button layout/rendering in all lobby instances
* Fix incorrect address book contact name display
* Fix display webcam preview
* Fix broken render of address book panel after Ajax contact addition
* fix multiframe not showing absolute
* Fix not opening multiframe when moderator opens multiframe
* Fix Phone Modal to be able to close
* Fix sorting for conferences without date
* Fix Showing Closed and 1 participant in conference
* Fix wrong start time in dashboard

### ⭐ Improvements:
* Performance increase in Dashboard page loading time
* Add lobby moderator permission flag to the JWT
* Redesigned homepage
* Fix appointment modal
* Adressbook refactoring
* The caller api authorizes against the api key of the room's server instead of one global secret
* The personal sip pin is only sent in invitation mails and shown in the dashboard while `SIP_CALLER_SHOW_IN_FRONTEND` is enabled. The room number is no longer labelled as pin in both places

### ⚠️ Deprecations:
* `/api/v1/conferenceMapper` is deprecated. Use `/api/v1/lobby/sip/open/{roomId}` instead, which returns the same payload
* `/api/v1/lobby/sip/pin/{roomId}` is deprecated in favour of `/api/v1/lobby/sip/protected/{roomId}`. The old path keeps working and serves the same endpoint
* Authorizing the caller api with the global `SIP_CALLER_SECRET` is deprecated. It is still accepted so existing asterisk configurations keep working, but every use logs a warning. Configure the api key on the server instead
