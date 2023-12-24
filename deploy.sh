#!/bin/bash
read -r -d '' HELP << EOM
This script is used to deploy a Laravel application to an AWS Lightsail instance (NGINX).
Usage:
    bash deploy.sh <domain-name>
EOM

APPNAME="laravel"
SSH_USER="bitnami"
DOMAIN_NAME=$1
if [ -z "$DOMAIN_NAME" ]; then
    echo "$HELP"
    exit 1
fi

DEPLOY_KEY_PATH="$HOME/.ssh/${SSH_USER}-${APPNAME}"

# Upload application files.
ssh -i "$DEPLOY_KEY_PATH" "$SSH_USER@$DOMAIN_NAME" "mkdir -p /opt/bitnami/nginx/html/$APPNAME"
scp -i "$DEPLOY_KEY_PATH" app.zip "$SSH_USER@$DOMAIN_NAME:/opt/bitnami/nginx/html/$APPNAME/app.zip"

read -r -d '' SSH_COMMAND << EOM
cd /opt/bitnami/nginx/html/$APPNAME/
unzip app.zip
rm app.zip
sudo chmod +x artisan
sudo chown -R bitnami:daemon .
sudo chmod -R 777 storage/framework/
sudo chmod +x update.sh
./update.sh
EOM

ssh -i "$SSH_KEY" "$SSH_USER@$DOMAIN_NAME" "$SSH_COMMAND"
