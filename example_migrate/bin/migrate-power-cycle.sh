#!/usr/bin/env bash

CALLPATH=`dirname $0`
source "$CALLPATH/framework.sh"

# Change these values as needed locally for the migration you're working on.
TYPE=${1-chapter}
GROUP=${2-ccf_web_pages}
MODULE=${3-ccf_migrate}
export NOOP=0

# Stop the migration in case the process is stuck. Rarely needed, but fast.
cmd "fin exec vendor/bin/drush migrate-stop $TYPE"
# Reset the status in case the process crashed. Needed whenever a fatal error occurs.
cmd "fin exec vendor/bin/drush migrate-reset-status $TYPE"
# Re-import deployment migration configuration. Because I'm making changes there a lot.
cmd "fin exec vendor/bin/drush cim -y --partial --source=modules/custom/$MODULE/config/install/"
# Rollback any existing migrated content. WARNING: Without specifying ids for the migration process this nukes all migrated content.
cmd "fin exec vendor/bin/drush migrate-rollback $TYPE"
# Pull in all the items exposed from the source environment.
cmd "fin exec vendor/bin/drush migrate-import $TYPE"
