# Features, Improvements, and Bug Fixes in Jitsi Admin

## 1.6
### 🚀 Features:
* Add E2EE to server and conference setting and JWT
* Revert layout change for Manage Participants dialog and implemented associated Ajax functionality

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
* Change Drag and Drop Lib back to inteact js because it is more robust
* SIP dial-in via the lobby using Livekit
