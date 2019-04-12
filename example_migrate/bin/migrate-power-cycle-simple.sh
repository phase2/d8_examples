#!/usr/bin/env bash

# The migration module is 'example_migrate'. The migration is 'example_nodes'.  Replace these as needed.

# Stop the migration in case the process is stuck. Rarely needed, but fast.
cmd "drush migrate-stop example_nodes"
# Reset the status in case the process crashed. Needed whenever a PHP fatal error occurs.
cmd "drush migrate-reset-status example_nodes"
# Re-import deployment migration configuration. Because you're making changes there a lot.
cmd "drush cim -y --partial --source=modules/custom/example_migrate/config/install/"
# Rollback any existing migrated content.
cmd "drush migrate-rollback example_nodes"
# Pull in all the items exposed from the source environment.
cmd "drush migrate-import example_nodes"
