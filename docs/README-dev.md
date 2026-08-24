[deutsch](README-dev_de.md)

# Jitsi Admin — Development Setup

This project uses [DDEV](https://ddev.com/) for local development. DDEV provides a containerized environment with PHP 8.x, MariaDB, a mail catcher (MailHog), and everything needed to run Jitsi Admin locally.

## Prerequisites

Install DDEV by following the official instructions for your platform: https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/

## Quick Start

```bash
ddev setup
```

This single command installs all PHP dependencies (`composer install`) and Node.js dependencies (`npm install`) inside the DDEV container. There is no `ddev setup` command by default — it is provided by the project's `.ddev/commands/web/setup` configuration in this repository.

**Step by step** (if you prefer to run each step individually):

```bash
ddev start                      # Start the DDEV containers
ddev composer install           # Install PHP dependencies
ddev npm install                # Install frontend dependencies
ddev npm run build              # Build frontend assets with Webpack Encore
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

For more detailed instructions, refer to the [DDEV documentation](https://ddev.readthedocs.io/).
