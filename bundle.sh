#!/bin/bash
# This script is used to bundle the application into a .zip file.
# It is used by the build pipeline to create the artifact.
# It is also used by the deploy pipeline to deploy the artifact.
# The script assumes that the application has already been built and tested.
DIR=$(pwd)
STARTTIME=$(date +%s)
NOW=$(date +"%Y-%m-%d-%H-%M-%S")
LOGFILE="app-zip-$NOW.log"

# Packaged files and directories.
PATHS=(
  "app/"
  "bootstrap/"
  "config/"
  "database/"
  "public/"
  "resources/"
  "routes/"
  "storage/app/public/.gitignore"
  "storage/framework/cache/data/.gitignore"
  "storage/framework/sessions/.gitignore"
  "storage/framework/views/.gitignore"
  "storage/logs/.gitignore"
  "artisan"
  "composer.json"
  "install.sh"
  "update.sh"
)

# Add Composer production dependencies to the PATHS list.
# This is done as a build time optimization to minimize the payload.
IGNORED_PATHS=(
    "phpunit.xml"
    ".editorconfig"
)
IGNORED_FILES=()
COMPOSER_PATHS=$(composer show --path --no-dev | awk '{print $2}' | sed "s|$DIR/||" | sed "s|$|/|")
for COMPOSER_PATH in $COMPOSER_PATHS; do
  FILES=$(ls -A "$COMPOSER_PATH")
  for FILE in $FILES; do
    # If the file name ends with ".md" and does not start with "LICEN" or "licen", skip it.
    if [[ "$FILE" =~ \.md$ ]] && ! [[ "$FILE" =~ ^[Ll][Ii][Cc][Ee][Nn] ]]; then
      IGNORED_FILES+=("$COMPOSER_PATH$FILE")
      continue
    fi
    # If the file name is in the IGNORED_PATHS list, skip it.
    if [[ " ${IGNORED_PATHS[@]} " =~ " $FILE " ]]; then
      IGNORED_FILES+=("$COMPOSER_PATH$FILE")
      continue
    fi
    PATHS+=("$COMPOSER_PATH$FILE")
  done
done

# Zip paths and output a log to a file.
zip -r -D -l -1 -v app.zip ${PATHS[@]} > "$LOGFILE.tmp" 2>&1

# Add information to the zip log file.
ENDTIME=$(date +%s)
RUNTIME=$((ENDTIME - STARTTIME))
echo "Runtime: $RUNTIME seconds"
RESULT=$(tail -n 1 "$LOGFILE.tmp")

echo "================================" > "$LOGFILE"
echo "= Application Package Manifest =" >> "$LOGFILE"
echo "================================" >> "$LOGFILE"
echo "> Runtime: $RUNTIME seconds" >> "$LOGFILE"
echo "> Results: $RESULT" >> "$LOGFILE"
echo "> Excluded dependency files:" >> "$LOGFILE"
for IGNORED_FILE in "${IGNORED_FILES[@]}"; do
  echo "  - $IGNORED_FILE" >> "$LOGFILE"
done
echo "================================" >> "$LOGFILE"
cat "$LOGFILE.tmp" >> "$LOGFILE"
rm "$LOGFILE.tmp"

# Add the log file to the zip.
zip -u -1 app.zip "$LOGFILE" > /dev/null 2>&1
