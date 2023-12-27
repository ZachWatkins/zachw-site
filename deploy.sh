#!/bin/bash
read -r -d '' HELP << EOM
This script is used to deploy a Laravel application to an AWS Lightsail instance (NGINX).
Usage:
    bash deploy.sh <ssh-user=bitnami> <domain-name> <deploy-key-path=\$HOME/.ssh/{ssh-user}-{app-name}> <app-name=laravel>
EOM

SSH_USER=$1
DOMAIN_NAME=$2
DEPLOY_KEY_PATH=$3
APPNAME=$4
if [ -z "$SSH_USER" ]; then
    SSH_USER="bitnami"
fi
if [ -z "$DOMAIN_NAME" ]; then
    echo "$HELP"
    exit 1
fi
if [ -z "$DEPLOY_KEY_PATH" ]; then
    DEPLOY_KEY_PATH="$HOME/.ssh/${SSH_USER}-${APPNAME}"
    if [ ! -f "$DEPLOY_KEY_PATH" ]; then
        echo "Could not find deploy key at $DEPLOY_KEY_PATH"
        echo "$HELP"
        exit 1
    fi
fi

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
EOM

ssh -i "$SSH_KEY" "$SSH_USER@$DOMAIN_NAME" "$SSH_COMMAND"
