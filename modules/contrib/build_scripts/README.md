# Build scripts

Build scripts is a module that adds functionality to run scripts from within Drupal user interface (for example to build a static site). This module is built for running Gatsby builds, but is very customizable and can be adapted to many different purposes. The module communicates with [external build daemon](https://github.com/oikeuttaelaimille/builder) and does nothing without it.

Our motivation for this project is that we want a button to our site that runs a script which builds a static Gatsby site from our Drupal data. Additionally we want it to support separate testing environment that our editors can use to check how their work looks on the Gatsby site.

This module has configurable environments that are passed to a shell script with current Drupal language as arguments. The shell script does most of the work.

## Installation (Gatsby)

1. Install [build daemon](https://github.com/oikeuttaelaimille/builder) to you server according to README.
2. Create a shell script that is responsible for the build:

   ### Gatsby reference script

   ```bash
   #!/usr/bin/env bash

   if [ ! $# -eq 2 ]; then
       printf "Invalid arguments\n" >&2
       exit 1
   fi

   ENVIRONMENT="$1"
   LANGUAGE="$2"
   LOCKFILE="/tmp/build-$LANGUAGE.lock" # Only one build per language is allowed
   GATSBY_DIR="/var/www/gatsby/$LANGUAGE/"
   WWW_DIR="/var/www/frontend/$LANGUAGE/$ENVIRONMENT/"

   echo "starting build for $ENVIRONMENT environment"
   cd "$GATSBY_DIR"

   # Better format for logging Gatsby commands:
   export CI='true'

   # Prevent building multiple environments simultaneously.
   if ! mkdir "$LOCKFILE"; then
       printf "Failed to acquire lock.\n" >&2
       exit 1
   fi

   trap "rm -rf $LOCKFILE" EXIT # remove the lock on exit

   if npx gatsby build && rsync -a --delete "$GATSBY_DIR/public/" "$WWW_DIR"; then
       echo "Build succeeded"
   else
       echo "Build failed :("
       exit 1
   fi
   ```
