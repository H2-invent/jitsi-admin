#!/bin/sh

# This script is run whenever the main application container is started and
# Runs only AFTER the container has been successfully initialized (see ./02-symfony-init.sh)
# ALWAYS RUNS - cannot be disabled.

set -euo pipefail

echo "[i] Bootstrapping Symfony..."
php bin/console cache:clear
php bin/console cache:warmup