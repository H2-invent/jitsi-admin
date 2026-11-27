#!/bin/sh

# This script is run before starting the main container and prepares Symfony.
# Note that this script is run in a separate init container + volume from the main application: Only changes to the DB are saved, changes to the filesystem won't have any permanent effect.
# Can be disabled in the respective env (Docker compose / Helm values) by setting APP_RUN_INIT to false, default: true.

set -euo pipefail

if [ "${APP_RUN_INIT:-false}" = "true" ]; then
    echo "[i] Initializing Symfony..."
    php bin/console doc:mig:mig --no-interaction
    php bin/console app:setup:initial
fi