# Features, Improvements, and Bug Fixes in Jitsi Admin
## 1.3

### Features:
* **LookyLooky** The great new document sharing tool [LookyLooky GIthub Repo](https://github.com/H2-invent/lookylooky)
### Bug Fixes:
* remove header in sip trunk generation
### ⭐ Improvements
* Join a Videoconference without an camera and microphone
* Add Api to change Server of Room to use auto provisioner
* add @ server to livekit roomname
* add @ servername to jwt roomname claim
* add relay for events to other middlewares
* Add Chatwoot in conferences in sidebar

## 1.2
### 🚀 Features
* **Improved Side-Navigation in the Conference**:
    * Better overview of all Add-Ons during the conference
    * New invitation Modal inside the conference

### ⭐ Improvements
* Invite Participants faster with the new invitation modal from the new sidenavigation inside the conference
* Improve the Livekit Phone Number Mapping into the video conference
* New Docker Registry deployment
* Set name in public rooms via url
* Skip Lobby by URL parameter in public rooms
* Enable Mic and Camera by URL paramter

### 🐛 Bug Fixes
* Collapse the Side-Navigation on mobile devices
* Calendly disconnection fails when token was removed in advance in Calendly
* Open invitation modal in open conferences only on moderators
* Remove min-height 100vh from joinpage so it fits on mobile devices
* Remove Auto TLS in Mailer DSN in SMTP server
* Docker Image failed because of permission Issues. Thanks to @nkiss1980 for reporting the bug
* Allow to change server when disabled via theme or permissions
* Fix Docker Image Build to new Base Image 
* fix api to move room to other server
* remove langauges which are all over in the project
* On user room removal, room is now correctly removed from users favorite list

# Features, Improvements, and Bug Fixes related to the Livekit Integration
## 1.3
Nothing

## 1.2
### 🚀 Feature
* New start page for registered and unregistered conference participants
* Force a name for unregistered participants
* Preselection of camera, microphone, and background
* New device recognition
* Add Helm chart for deploying meetling on kubernetes with the new Docker Core Image --> [Helm Chart](https://reg.h2-invent.com/harbor/projects/16/repositories/meetling/artifacts-tab)

### ⭐ Improvements
* Join a Videoconference without an camera and microphone
* Add Api to change Server of Room to use auto provisioner
* add @ server to livekit roomname
* add @ servername to jwt roomname claim
* add relay for events to other middlewares
* Add Chatwoot in conferences in sidebar

### 🐛 Bug Fixes
* Fix new Lobby for Multiple Users
* Fix extra html not blocking other js
* Fix smtp error when email address is incorrect. This email shows now the reason why an email was not send

# Changes in the Managed Service
* All Livekit Server Conferences will run on the new Middleware
* Existing sponsored Jitsi Server will be moved to Livekit

# Get more information about updates
* You can use the **Github Notification** via E-Mail to get informed about new features in the next release
* Sign up for the **Jitsi Admin Mailing-List**, which we send out: [Sign Up](https://lists.h2-invent.com/forms/nfrm_weLJnLY5)
