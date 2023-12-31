#!/bin/bash
# This script is used to bundle the application into a .zip file.
# It is used by the build pipeline to create the artifact.
# The script assumes that the application has already been tested.
DIR=$(pwd)
STARTTIME=$(date +%s)
NOW=$(date +"%Y-%m-%d-%H-%M-%S")
LOGFILE="app-zip-$NOW.log"

# Build front-end assets.
if [ ! -d "$DIR/node_modules" ]; then
  npm install
fi
if [ ! -d "$DIR/public/build" ]; then
  npm run build
fi

# Install production dependencies if vendor folder or autoloader is missing.
if [ ! -d "$DIR/vendor" ]; then
  echo "Installing production dependencies..."
  composer install --optimize --classmap-authoritative --no-dev --no-interaction --no-scripts --no-plugins
elif [ ! -f "$DIR/vendor/autoload.php" ]; then
  echo "Generating autoload files..."
  composer dump-autoload --optimize --classmap-authoritative --no-dev --no-interaction --no-scripts --no-plugins
fi

# Packaged files and directories.
PATHS=(
    "app/"
    "bootstrap/"
    "config/"
    "database/"
    "nginx/"
    "public/"
    "resources/"
    "routes/"
    "storage/app/public/.gitignore"
    "storage/framework/cache/data/.gitignore"
    "storage/framework/sessions/.gitignore"
    "storage/framework/views/.gitignore"
    "storage/logs/.gitignore"
    "vendor/autoload.php"
    "vendor/composer/"
    "vendor/bin/"
    "artisan"
    "composer.json"
    "update.sh"
    ".env.example"
)

# Add Composer production dependencies to the PATHS list.
# This is done as a build time optimization to minimize the payload.
IGNORED_FILENAMES=(
    "phpunit.xml"
    ".editorconfig"
    "CONTRIBUTING.md"
    "SECURITY.md"
    "CHANGELOG.md"
    "CHANGES.txt"
    "README.md"
    "readme.md"
    "UPGRADE.md"
    "INFO.md"
    "composer.lock"
    ".phpstorm.meta.php"
    ".readthedocs.yml"
    "docker-compose.yml"
    "conventional-commits.json"
)
IGNORED_PATHS=(
    "vendor/doctrine/inflector/docs"
    "vendor/psr/http-message/docs"
)
IGNORED_FILES=()
COMPOSER_PATHS=$(composer show --path --no-dev --no-interaction --no-scripts --no-plugins | awk '{print $2}' | sed "s|$DIR/||" | sed "s|$|/|")
for COMPOSER_PATH in $COMPOSER_PATHS; do
  FILES=$(ls -A "$COMPOSER_PATH")
  for FILE in $FILES; do
    if [[ " ${IGNORED_FILENAMES[@]} " =~ " $FILE " ]]; then
      IGNORED_FILES+=("$COMPOSER_PATH$FILE")
      continue
    elif [[ "$COMPOSER_PATH$FILE" == *"vendor/"*".github/"* ]]; then
      IGNORED_FILES+=("$COMPOSER_PATH$FILE")
      continue
    elif [[ "$COMPOSER_PATH$FILE" == *"vendor/"*".gitignore" ]]; then
      IGNORED_FILES+=("$COMPOSER_PATH$FILE")
      continue
    elif [[ "$COMPOSER_PATH$FILE" == *"vendor/"*".gitkeep" ]]; then
      IGNORED_FILES+=("$COMPOSER_PATH$FILE")
      continue
    elif [[ "$COMPOSER_PATH$FILE" == *"vendor/"*".favicon" ]]; then
      IGNORED_FILES+=("$COMPOSER_PATH$FILE")
      continue
    else
      for IGNORED_PATH in "${IGNORED_PATHS[@]}"; do
        if [[ "$COMPOSER_PATH$FILE" == *"$IGNORED_PATH"* ]]; then
          IGNORED_FILES+=("$COMPOSER_PATH$FILE")
          continue 2
        fi
      done
    fi
    PATHS+=("$COMPOSER_PATH$FILE")
  done
done

# Zip paths and output a log to a file.
if [ -f app.zip ]; then
  rm app.zip
fi
zip -r -D -l -1 -v app.zip "${PATHS[@]}" > "$LOGFILE.tmp" 2>&1

# Add information to the zip log file.
ENDTIME=$(date +%s)
RUNTIME=$((ENDTIME - STARTTIME))
echo "Runtime: $RUNTIME seconds"
RESULT=$(tail -n 1 "$LOGFILE.tmp")

{
  echo "================================"
  echo "APPLICATION MANIFEST ==========="
  echo "================================"
  echo "> Runtime: $RUNTIME seconds"
  echo "> Results: $RESULT"
  echo "> Excluded dependency files:"
  for IGNORED_FILE in "${IGNORED_FILES[@]}"; do
    echo "  - $IGNORED_FILE"
  done
  echo "================================"
  echo "CONTENTS ======================="
  echo "================================"
  cat "$LOGFILE.tmp"
} > "$LOGFILE"

# Add the log file to the zip.
zip -u -1 app.zip "$LOGFILE" > /dev/null 2>&1

# Remove the temporary log file.
rm "$LOGFILE.tmp"
