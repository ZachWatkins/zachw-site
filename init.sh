#!/bin/bash
read -r -d '' HELP << EOM
This script is used to initialize an AWS Lightsail instance (NGINX) for Laravel.
Usage:
    bash init.sh <domain_name> <ssh_user>
Example:
    bash init.sh example.com bitnami
EOM

DOMAIN_NAME=$1
if [ -z "$DOMAIN_NAME" ]; then
    echo "$HELP"
    exit 1
elif [ "$DOMAIN_NAME" = "-h" ] || [ "$DOMAIN_NAME" = "--help" ]; then
    echo "$HELP"
    exit 0
fi
DOMAIN_DIRECTORY=$(echo "$DOMAIN_NAME" | sed "s/\./-/g")

SSH_USER=$2
if [ -z "$SSH_USER" ]; then
    SSH_USER="bitnami"
fi

CURRENT_DIRECTORY=$(pwd)

# Locate the default SSH key. Location priority:
# 1. ./LightsailDefaultPrivateKey-*.pem
# 2. ~/.ssh/LightsailDefaultPrivateKey-*.pem
SSH_KEY=$(find . -maxdepth 1 -name "LightsailDefaultKey-*.pem" -print -quit)
if [ -z "$SSH_KEY" ]; then
    SSH_KEY=$(find ~/.ssh -maxdepth 1 -name "LightsailDefaultKey-*.pem" -print -quit)
    if [ -z "$SSH_KEY" ]; then
        echo "Could not find the default SSH key."
        exit 1
    fi
fi

# Generate a new SSH key for the same user for use as a deployment key.
# This is necessary because the deployment actions need the same access as SSH_USER.
mkdir -p ~/.ssh
DEPLOY_KEY_PATH="~/.ssh/${SSH_USER}-${DOMAIN_DIRECTORY}"
FIND_DEPLOY_KEY=$(find ~/.ssh -maxdepth 1 -name "${SSH_USER}-${DOMAIN_DIRECTORY}" -print -quit)
# If the deploy key already exists, don't generate a new one.
if [ -z "$FIND_DEPLOY_KEY" ]; then
    cd ~/.ssh
    ssh-keygen -t rsa -b 4096 -C "$SSH_USER@$DOMAIN_NAME" -f "${SSH_USER}-${DOMAIN_DIRECTORY}"
    cd "$CURRENT_DIRECTORY"
fi

# Upload files to the server needed to initialize the instance.
ssh -i "$SSH_KEY" "$SSH_USER@$DOMAIN_NAME" "mkdir -p /home/bitnami/stack/nginx/html/$DOMAIN_DIRECTORY"
APP_FOUND=$(ssh -i "$SSH_KEY" "$SSH_USER@$DOMAIN_NAME" "find /home/bitnami/stack/nginx/html/$DOMAIN_DIRECTORY -maxdepth 1 -name 'app.zip' -print -quit")
if [ -z "$APP_FOUND" ]; then
    scp -i "$SSH_KEY" app.zip "$SSH_USER@$DOMAIN_NAME:/home/bitnami/stack/nginx/html/$DOMAIN_DIRECTORY/app.zip"
fi
SSH_KEY_FOUND=$(ssh -i "$SSH_KEY" "$SSH_USER@$DOMAIN_NAME" "find ~/.ssh -maxdepth 1 -name '${SSH_USER}-${DOMAIN_DIRECTORY}.pub' -print -quit")
if [ -z "$SSH_KEY_FOUND" ]; then
    scp -i "$SSH_KEY" "$HOME/.ssh/${SSH_USER}-${DOMAIN_DIRECTORY}.pub" "$SSH_USER@$DOMAIN_NAME:/home/bitnami/.ssh/${SSH_USER}-${DOMAIN_DIRECTORY}.pub"
fi

read -r -d '' SSH_COMMAND << EOM
DEPLOY_KEY_INCLUDED=\$(cat ~/.ssh/authorized_keys | grep '$SSH_USER@$DOMAIN_NAME')
if [ -z "\$DEPLOY_KEY_INCLUDED" ]; then
    echo "Key not found. Adding to authorized_keys."
    cat /home/bitnami/.ssh/${SSH_USER}-${DOMAIN_DIRECTORY}.pub >> /home/bitnami/.ssh/authorized_keys
fi
cd /home/bitnami/stack/nginx/html/$DOMAIN_DIRECTORY/
if [ ! -e artisan ]; then
    unzip app.zip
fi
cd /home/bitnami/stack/nginx/conf/server_blocks/
if [ -e $DOMAIN_DIRECTORY-http-server-block.conf ]; then
    rm $DOMAIN_DIRECTORY-http-server-block.conf
fi
cp /home/bitnami/stack/nginx/html/$DOMAIN_DIRECTORY/nginx/default.conf $DOMAIN_DIRECTORY-http-server-block.conf
sed -i "s/example.com/$DOMAIN_NAME/g" $DOMAIN_DIRECTORY-http-server-block.conf
if [ -f $DOMAIN_DIRECTORY-https-server-block.conf ]; then
    rm $DOMAIN_DIRECTORY-https-server-block.conf
fi
cp /home/bitnami/stack/nginx/html/$DOMAIN_DIRECTORY/nginx/https.conf $DOMAIN_DIRECTORY-https-server-block.conf
sed -i "s/example.com/$DOMAIN_NAME/g" $DOMAIN_DIRECTORY-https-server-block.conf
cd /home/bitnami/stack/nginx/html/$DOMAIN_DIRECTORY/
sudo chown -R bitnami:daemon .
sudo chmod +x update.sh
sudo chmod -R 777 storage/framework/
if [ ! -f .env ]; then
    cp .env.example .env
fi
chmod +x artisan
php artisan key:generate
sudo /opt/bitnami/ctlscript.sh restart nginx
EOM

ssh -i "$SSH_KEY" "$SSH_USER@$DOMAIN_NAME" "$SSH_COMMAND"

read -r -d '' NEXT_STEPS << EOM
Next steps:
1. Log in via SSH and run this command to generate your SSL certificate: sudo /opt/bitnami/bncert-tool
2. Add the private SSH key to the GitHub repository as a deployment key. Location: $DEPLOY_KEY_PATH
3. Update the .env file with the correct database credentials, APP_URL, and other values.
EOM

echo "$NEXT_STEPS"
