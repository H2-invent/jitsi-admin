[english](README-dev.md)

# Jitsi Admin — Entwicklungsumgebung

Dieses Projekt verwendet [DDEV](https://ddev.com/) für die lokale Entwicklung. DDEV stellt eine containerisierte Umgebung mit PHP 8.x, MariaDB, einem Mail-Catcher und allem Notwendigen bereit, um Jitsi Admin lokal auszuführen.

## Voraussetzungen

Installieren Sie DDEV gemäß der offiziellen Anleitung für Ihre Plattform:

| Plattform | Befehl |
|-----------|--------|
| **macOS** | `brew install ddev/ddev/ddev` |
| **Linux / WSL2** | `curl -fsSL https://ddev.com/install.sh \| bash` |
| **Windows** | Siehe [DDEV Windows-Installation](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/#windows) |

Vollständige Installationsanleitung: https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/

## Schnellstart (ein Befehl)

```bash
ddev setup
```

Dieser einzelne Befehl installiert alle PHP-Abhängigkeiten (`composer install`) und Node.js-Abhängigkeiten (`npm install`) innerhalb des DDEV-Containers. Es gibt standardmäßig keinen `ddev setup`-Befehl — dieser wird durch die Projektkonfiguration in `.ddev/commands/web/setup` bereitgestellt.

## Schritt-für-Schritt-Einrichtung

Falls Sie jeden Schritt einzeln ausführen möchten:

```bash
# 1. DDEV-Container starten
ddev start

# 2. PHP-Abhängigkeiten installieren (Symfony, Doctrine, JMS I18n, etc.)
ddev composer install

# 3. Frontend-Abhängigkeiten installieren (Bootstrap, Font Awesome, Swiper, etc.)
ddev npm install

# 4. Frontend-Assets mit Webpack Encore bauen
ddev npm run build
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

## Symfony-Konsolenbefehle

Alle Symfony-Befehle müssen über DDEV ausgeführt werden:

```bash
ddev php bin/console cache:clear                                # Symfony-Cache leeren
ddev php bin/console cache:warmup                               # Cache für Produktion aufwärmen
ddev php bin/console debug:router                               # Alle Routen auflisten
ddev php bin/console debug:translation de --domain=homepage     # Homepage-Übersetzungen für Deutsch prüfen
ddev php bin/console debug:translation en --domain=homepage     # Homepage-Übersetzungen für Englisch prüfen
ddev php bin/console doctrine:schema:update --dump-sql          # Datenbank-Schemaänderungen als Vorschau anzeigen
ddev php bin/console doctrine:migrations:migrate                # Datenbank-Migrationen ausführen
```

## Frontend-Assets neu bauen

Nach Änderungen an CSS- oder JavaScript-Dateien:

```bash
ddev npm run build       # Produktions-Build (minimiert, Ausgabe in public/build/)
ddev npm run dev         # Entwicklungs-Build mit Source Maps (überwacht Änderungen)
ddev npm run watch       # Watch-Modus — baut automatisch bei Dateiänderungen neu
```

## Umgebungsvariablen

Die `.env`-Datei enthält DDEV-kompatible Standardwerte. Lokale Überschreibungen kommen in `.env.local` (gitignored, wird nie committet).

Wichtige Variablen:

| Variable | Standard | Beschreibung |
|----------|----------|-------------|
| `DEFAULT_LANGUAGE` | `en` | Standardsprache für das JMS I18n Routing-Bundle. `en` für Englisch als Standard, `de` für Deutsch als Standard |
| `DATABASE_URL` | `mysql://db:db@db:3306/db` | Datenbankverbindung (automatisch durch DDEV konfiguriert) |
| `MAILER_DSN` | `smtp://127.0.0.1:1025` | Mail-Catcher-DSN (MailHog) |

Das JMS I18n Routing-Bundle verwendet die `prefix_except_default`-Strategie:
- `/` → Standardsprache (Englisch)
- `/de/` → Deutsch

## Übersetzungsdateien

Die Homepage verwendet eine eigene Übersetzungsdomäne `homepage`:

- `translations/custom/homepage+intl-icu.en.yaml` — Englische Übersetzungen
- `translations/custom/homepage+intl-icu.de.yaml` — Deutsche Übersetzungen

Template-Dateien verwenden `{{ 'key'|trans({}, 'homepage') }}`, um auf diese Übersetzungen zuzugreifen.

## Projektstruktur

| Verzeichnis | Zweck |
|-------------|-------|
| `src/Controller/` | Symfony-Controller (Routen-Handler) |
| `src/Entity/` | Doctrine-ORM-Entitäten |
| `templates/` | Twig-Templates (Homepage: `templates/dashboard/start.html.twig`) |
| `assets/css/` | SCSS-Stylesheets |
| `assets/js/` | JavaScript-Einstiegspunkte |
| `public/build/` | Kompilierte Frontend-Assets (Webpack-Ausgabe) |
| `translations/` | Übersetzungsdateien (XLIFF für die Hauptanwendung, YAML für die Homepage) |
| `config/` | Symfony-Konfiguration (Routen, Pakete, Dienste) |
| `migrations/` | Doctrine-Datenbankmigrationen |

## Fehlerbehebung

| Problem | Lösung |
|---------|--------|
| Übersetzungen werden nicht aktualisiert | `ddev restart` ausführen, um OPcache und PHP-FPM-Caches zu leeren, oder `ddev php bin/console cache:clear` |
| Assets werden nicht geladen | `ddev npm run build` ausführen, um die Webpack-Ausgabe neu zu generieren |
| „hp.meta.heading" wird als Rohtext angezeigt | Übersetzungsdomäne nicht geladen — `ddev restart` ausführen |
| Datenbank-Verbindungsfehler | `ddev describe` ausführen, um die Datenbank-Zugangsdaten zu überprüfen, dann `ddev php bin/console doctrine:schema:update --force` |
| Port-Konflikte | `ddev describe` ausführen, um die verwendeten Ports zu sehen; in `.ddev/config.yaml` anpassen |
| Composer-Speicherfehler | `ddev composer install --no-dev` oder `php -d memory_limit=-1 /usr/local/bin/composer install` ausführen |
