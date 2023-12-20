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
cd ~/.ssh
DEPLOY_KEY_PATH="~/.ssh/${SSH_USER}-${DOMAIN_DIRECTORY}"
ssh-keygen -t rsa -b 4096 -C "$SSH_USER@$DOMAIN_NAME" -f "${SSH_USER}-${DOMAIN_DIRECTORY}"

# Upload files to the server needed to initialize the instance.
ssh -i "$SSH_KEY" "$SSH_USER@$DOMAIN_NAME" "mkdir -p ~/.ssh && chmod 700 ~/.ssh && touch ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys && mkdir -p /opt/bitnami/nginx/html/$DOMAIN_DIRECTORY"
scp -i "$SSH_KEY" "~/.ssh/${SSH_USER}-${DOMAIN_DIRECTORY}.pub" "$SSH_USER@$DOMAIN_NAME:~/.ssh/${SSH_USER}-${DOMAIN_DIRECTORY}.pub"
scp -i "$SSH_KEY" app.zip "$SSH_USER@$DOMAIN_NAME:/opt/bitnami/nginx/html/$DOMAIN_DIRECTORY/app.zip"

read -r -d '' SSH_COMMAND << EOM
set -e
DEPLOY_KEY_EXISTS=\$(cat ~/.ssh/authorized_keys | grep '$SSH_USER@$DOMAIN_NAME')
if [ -z "\$DEPLOY_KEY_EXISTS" ]; then
    cat ~/.ssh/${SSH_USER}_${DOMAIN_NAME}.pub >> ~/.ssh/authorized_keys
fi
cd /opt/bitnami/nginx/conf/server_blocks/
if [ -f $DOMAIN_DIRECTORY-http-server-block.conf ]; then
    rm $DOMAIN_DIRECTORY-http-server-block.conf
fi
cp sample-server-block.conf.disabled $DOMAIN_DIRECTORY-http-server-block.conf
SEARCH="server_name _;"
REPLACE="server_name $DOMAIN_NAME www.$DOMAIN_NAME;\n\treturn 301 https://\$host\$request_uri;"
sed -i "s/\$SEARCH/\$REPLACE/g" $DOMAIN_DIRECTORY-http-server-block.conf

if [ -f $DOMAIN_DIRECTORY-https-server-block.conf ]; then
    rm $DOMAIN_DIRECTORY-https-server-block.conf
fi
cp sample-https-server-block.conf.disabled $DOMAIN_DIRECTORY-https-server-block.conf
sed -i "s/server_name _;/server_name $DOMAIN_NAME www.$DOMAIN_NAME;/g" $DOMAIN_DIRECTORY-https-server-block.conf
cd /opt/bitnami/nginx/html/$DOMAIN_DIRECTORY/
unzip app.zip
rm app.zip
chmod +x artisan
chmod +x update.sh
sudo chmod -R 777 storage/frameworks/
if [ ! -f .env ]; then
    cp .env.example .env
fi
php artisan key:generate
php artisan migrate --force
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
