[deutsch](README-dev_de.md)

# Jitsi Admin — Development Setup

This project uses [DDEV](https://ddev.com/) for local development. DDEV provides a containerized environment with PHP 8.x, MariaDB, a mail catcher, and everything needed to run Jitsi Admin locally.

## Prerequisites

Install DDEV by following the official instructions for your platform:

| Platform | Command |
|----------|---------|
| **macOS** | `brew install ddev/ddev/ddev` |
| **Linux / WSL2** | `curl -fsSL https://ddev.com/install.sh \| bash` |
| **Windows** | See [DDEV Windows Installation](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/#windows) |

Full installation docs: https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/

## Quick Start (one command)

```bash
ddev setup
```

This single command installs all PHP dependencies (`composer install`) and Node.js dependencies (`npm install`) inside the DDEV container. There is no `ddev setup` command by default — it is provided by the project's `.ddev/commands/web/setup` configuration in this repository.

## Step-by-Step Setup

If you prefer to run each step individually:

```bash
# 1. Start the DDEV containers
ddev start

# 2. Install PHP dependencies (Symfony, Doctrine, JMS I18n, etc.)
ddev composer install

# 3. Install frontend dependencies (Bootstrap, Font Awesome, Swiper, etc.)
ddev npm install

# 4. Build frontend assets with Webpack Encore
ddev npm run build
```

## Starting and Stopping

```bash
ddev start    # Start the development server
ddev stop     # Stop the containers (preserves database and files)
ddev restart  # Restart all containers (useful after config or env changes)
```

The project will be available at **https://jitsi-admin.ddev.site**.

The development email inbox (MailHog) is at **https://jitsi-admin.ddev.site:8026**.

## Useful Commands

| Command | Description |
|---------|-------------|
| `ddev list` | List all running DDEV projects and their status |
| `ddev describe` | Show detailed info about the current project (URLs, ports, services) |
| `ddev ssh` | Open a shell inside the web container |
| `ddev exec <cmd>` | Run a command inside the web container |
| `ddev logs` | View container logs |
| `ddev poweroff` | Stop all DDEV projects |

## Symfony Console Commands

All Symfony commands must be run through DDEV:

```bash
ddev php bin/console cache:clear                                # Clear the Symfony cache
ddev php bin/console cache:warmup                               # Warm up the cache for production
ddev php bin/console debug:router                               # List all routes
ddev php bin/console debug:translation de --domain=homepage     # Check homepage translations for German
ddev php bin/console debug:translation en --domain=homepage     # Check homepage translations for English
ddev php bin/console doctrine:schema:update --dump-sql          # Preview database schema changes
ddev php bin/console doctrine:migrations:migrate                # Run database migrations
```

## Rebuilding Frontend Assets

After modifying CSS or JavaScript files:

```bash
ddev npm run build       # Production build (minified, output in public/build/)
ddev npm run dev         # Development build with source maps (watches for changes)
ddev npm run watch       # Watch mode — automatically rebuilds on file changes
```

## Environment Variables

The `.env` file provides DDEV-compatible defaults. Local overrides go in `.env.local` (gitignored, never committed).

Important variables:

| Variable | Default | Description |
|----------|---------|-------------|
| `DEFAULT_LANGUAGE` | `en` | Default locale for the JMS I18n Routing bundle. Use `en` for English-first, `de` for German-first |
| `DATABASE_URL` | `mysql://db:db@db:3306/db` | Database connection (auto-configured by DDEV) |
| `MAILER_DSN` | `smtp://127.0.0.1:1025` | Mail catcher DSN (MailHog) |

The JMS I18n Routing bundle uses the `prefix_except_default` strategy:
- `/` → default language (English)
- `/de/` → German

## Translation Files

The homepage uses a custom translation domain `homepage`:

- `translations/custom/homepage+intl-icu.en.yaml` — English translations
- `translations/custom/homepage+intl-icu.de.yaml` — German translations

Template files use `{{ 'key'|trans({}, 'homepage') }}` to reference these translations.

## Project Structure

| Directory | Purpose |
|-----------|---------|
| `src/Controller/` | Symfony controllers (route handlers) |
| `src/Entity/` | Doctrine ORM entities |
| `templates/` | Twig templates (homepage: `templates/dashboard/start.html.twig`) |
| `assets/css/` | SCSS stylesheets |
| `assets/js/` | JavaScript entry points |
| `public/build/` | Compiled frontend assets (Webpack output) |
| `translations/` | Translation files (XLIFF for main app, YAML for homepage) |
| `config/` | Symfony configuration (routes, packages, services) |
| `migrations/` | Doctrine database migrations |

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Translations not updating | Run `ddev restart` to flush OPcache and PHP-FPM caches, or `ddev php bin/console cache:clear` |
| Assets not loading | Run `ddev npm run build` to regenerate Webpack output |
| "hp.meta.heading" shown as raw text | Translation domain not loaded — run `ddev restart` |
| Database connection errors | Run `ddev describe` to verify database credentials, then `ddev php bin/console doctrine:schema:update --force` |
| Port conflicts | Run `ddev describe` to see used ports; adjust in `.ddev/config.yaml` |
| Composer memory errors | Run `ddev composer install --no-dev` or `php -d memory_limit=-1 /usr/local/bin/composer install` |
