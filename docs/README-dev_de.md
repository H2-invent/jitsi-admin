[english](README-dev.md)

# Jitsi Admin — Entwicklungsumgebung

Dieses Projekt verwendet [DDEV](https://ddev.com/) für die lokale Entwicklung. DDEV stellt eine containerisierte Umgebung mit PHP 8.x, MariaDB, einem Mail-Catcher (MailHog) und allem Notwendigen bereit, um Jitsi Admin lokal auszuführen.

## Voraussetzungen

Installieren Sie DDEV gemäß der offiziellen Anleitung für Ihre Plattform: https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/

## Schnellstart

```bash
ddev setup
```

Dieser einzelne Befehl installiert alle PHP-Abhängigkeiten (`composer install`) und Node.js-Abhängigkeiten (`npm install`) innerhalb des DDEV-Containers. Es gibt standardmäßig keinen `ddev setup`-Befehl — dieser wird durch die Projektkonfiguration in `.ddev/commands/web/setup` bereitgestellt.

**Schritt für Schritt** (falls Sie jeden Schritt einzeln ausführen möchten):

```bash
ddev start                      # DDEV-Container starten
ddev composer install           # PHP-Abhängigkeiten installieren
ddev npm install                # Frontend-Abhängigkeiten installieren
ddev npm run build              # Frontend-Assets mit Webpack Encore bauen
```

## Starten und Beenden

```bash
ddev start    # Entwicklungsserver starten
ddev stop     # Container anhalten (Datenbank und Dateien bleiben erhalten)
ddev restart  # Alle Container neu starten (nützlich nach Konfigurations- oder Umgebungsänderungen)
```

Das Projekt ist erreichbar unter **https://jitsi-admin.ddev.site**.

Das Entwicklungs-E-Mail-Postfach (MailHog) ist unter **https://jitsi-admin.ddev.site:8026** erreichbar.

## Nützliche Befehle

| Befehl | Beschreibung |
|--------|-------------|
| `ddev list` | Alle laufenden DDEV-Projekte und deren Status auflisten |
| `ddev describe` | Detaillierte Informationen zum aktuellen Projekt anzeigen (URLs, Ports, Dienste) |
| `ddev ssh` | Eine Shell im Web-Container öffnen |
| `ddev exec <cmd>` | Einen Befehl im Web-Container ausführen |
| `ddev logs` | Container-Logs anzeigen |
| `ddev poweroff` | Alle DDEV-Projekte beenden |

Für detailliertere Anweisungen siehe die [DDEV-Dokumentation](https://ddev.readthedocs.io/).
