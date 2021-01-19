# Jitsi Manager


[![Contributor Covenant](https://img.shields.io/badge/Contributor%20Covenant-v2.0%20adopted-ff69b4.svg)](code_of_conduct.md)

Jitsi Manager oder Jitsi Admin zum Verwalten von Jitsi Konferenzen und Server mit JWT


# Funktionen
Folgende Funktionen sind bereits im Jitsi Administrator integriert:
* Verwalten von Konferenzen
* Verwalten von Jitsi Servern mit JWT Funktion
* Hinzufügen von Teilnehmern zu einer Konferenz
* Verschicken von Emails an die Teilnehmer
* Verschicken von Emails vor der Konferenz über einen CronJob

### Das Dashboard
Alle Informationen zu den Konferenzen stehen auf dem zentralen Dashboard
![Dashboard](docs/images/dashboard-heading.jpg)


### Die Server
Alle Server können zentral verwaltet werden und unterschiedliche Berechtigungen verteilt werden.
Es können mehre Jitsi Server in einer Installation verwaltet werden.
![Server](docs/images/server.jpg)

### Login
Der Login erfolgt über einen SSO Server, z.B. Keycloak oder weitere Identidy Provider
![Login](docs/images/login.jpg)

### Join der Konferenz
Gäste können über einen Link in der Email der Konferenz beitreten __ohne__ einen Benutzer im Jitsi Manager zu haben.
Den Gästen steht eine Seite zum Beitreten bereit. Es wird nach der Konferenz ID, der Email Adresse und dem Namen gefragt.
Danach wird ein JWT erstellt mit dem der Gast der Konferenz beitreten kann.
![Join](docs/images/join.jpg)

Benutzer können direkt aus dem Jitsi Manager heraus der Konferenz beitreten, entweder über den Browser oder über die Jitsi Electron Desktop App auf dem PC.
![Join](docs/images/joint-internal.jpg)

Mehr Informationen auf https://jitsi-admin.de
# Get Started
Auf Grund der Composer Abhängigkeiten wird ein Webspace für den Betrieb der Webanwendung nicht empfohlen. Enterprise bedeutet, dass der Jitsi Admin als Manadatenlösung und interne Webanwendung betrieben werden kann. Die Verwaltung, Updates und Wartung muss durch die Zuständige und Verantwortliche Person durchgeführt werden.



* [Anleitung im Wiki](https://github.com/H2-invent/jitsi-admin/wiki/Get-Started)
* [Mindestanforderungen](https://github.com/H2-invent/jitsi-admin/wiki/Mindestanforderungen-an-den-Server)

# API
Mit der API ist es möglich Konferenzen durch eine andere Anwendung erzeugen zu lassen.

Es muss in die Datenbank ap_key manuel eine client_iD und ein client_secret eingeben werden.
Bei jedem request an die API muss der der API Secret über den parameter clientSecret mitgegeben werden.
Es sollten nur Zugriffe von einem Backend-Server auf den Jitsi-Admin durchgeführt werden.


### Infos zu einer Konferenz (GET /api/v1/{uidReal})
  
#### Request
```  
http://localhost:8000/api/v1/57d4d52d3c1f38c28e9f101f031a631f
```  
#### Response
```json  
{
"error": false,
"teilnehmer": [
"teilnehmer@email.de"
],
"start": "2021-02-02CET13:00:00",
"end": "2021-02-02CET13:45:00",
"duration": 45,
"name": "testAPINEW",
"moderator": "email@moderator.de",
"server": "serverurl",
"joinBrowser": "http://localhost:8000/room/join/b/84",
"joinApp": "http://localhost:8000/room/join/a/84"
}
```   
### Eine Konferenz erstellen (POST /api/v1/room)
  
  #### Request:
```  
http://localhost:8000/api/v1/room
?email=email@moderator.com
&name=testAPINEW
&duration=70
&server=serverURL
&start=2021-02-01T13:00
&clientSecret=secret
&keycloakId=id des Users
```  
  #### Response:
```json  
{
"error": false,
"uid": "57d4d52d3c1f38c28e9f101f031a631f",
"text": "Meeting erfolgreich angelegt"
}
```  
### Eine Konferenz bearbeiten (PUT /api/v1/room)
  #### Request:
```  
http://localhost:8000/api/v1/room
?name=testAPINEW
&duration=45
&server=serverURL
&start=2021-02-02T13:00
&clientSecret=secret
&uid=57d4d52d3c1f38c28e9f101f031a631f
```  
  #### Response:
```json  
{
"error": false,
"uid": "57d4d52d3c1f38c28e9f101f031a631f",
"text": "Meeting erfolgreich geändert"
}
```
### Eine Konferenz Löschen (DELETE /api/v1/room)
  #### Request:
```  
http://localhost:8000/api/v1/room
?uid=57d4d52d3c1f38c28e9f101f031a631f
&clientSecret=secret
```  
  #### Response
```json  
{
"error": false,
"text": "Erfolgreich gelöscht"
}
```
### Einen Teilnehmen zu einer Konferenz hinzufügen (POST /api/v1/user)
  #### Request:
```  
http://localhost:8000/api/v1/user
?uid=57d4d52d3c1f38c28e9f101f031a631f
&email=test@local.de
&clientSecret=secret
```  
  #### Response
```json  
{
"uid": "57d4d52d3c1f38c28e9f101f031a631f",
"user": "test@local.desd",
"error": false,
"text": "Teilnehmer test@local.desd erfolgreich hinzugefügt"
}
```
### Einen Teilnahmen von einer Konferenz löschen (DELETE / api/v1/user)
  #### Request:
```
http://localhost:8000/api/v1/user
?uid=57d4d52d3c1f38c28e9f101f031a631f
&email=test@local.de
&clientSecret=secret
```  
  #### Response
```json  
{
"uid": "57d4d52d3c1f38c28e9f101f031a631f",
"user": "test@local.desd",
"error": false,
"text": "Teilnehmer test@local.desd erfolgreich gelöscht"
}
```
### Infos zu einem User (GET /api/v1/serverInfo)
#### Request
```
http://localhost:8000/api/v1/serverInfo
?email=user@userToCHeck.de
&keycloakId=id
&clientSecret=secret
```
#### Response
```json
{
"server": [
"url1",
"url2",
"url3"
],
"email": "user@userToCHeck.de",
"error": false
}
```
# Lizenz
Die aktuelle Version von Jitsi Admin wird unter der AGPL-3.0 License bereitgestellt. Weitere Informationen finden Sie in der LICENSE Datei in diesem Repo.

