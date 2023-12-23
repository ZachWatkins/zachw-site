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
APPNAME="laravel"

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
DEPLOY_KEY_PATH="~/.ssh/${SSH_USER}-${APPNAME}"
FIND_DEPLOY_KEY=$(find ~/.ssh -maxdepth 1 -name "${SSH_USER}-${APPNAME}" -print -quit)
# If the deploy key already exists, don't generate a new one.
if [ -z "$FIND_DEPLOY_KEY" ]; then
    cd ~/.ssh
    ssh-keygen -t rsa -b 4096 -C "$SSH_USER@$DOMAIN_NAME" -f "${SSH_USER}-${APPNAME}"
    cd "$CURRENT_DIRECTORY"
fi
echo "Deploy key location: $DEPLOY_KEY_PATH"

# Upload deployment public key.
SSH_KEY_FOUND=$(ssh -i "$SSH_KEY" "$SSH_USER@$DOMAIN_NAME" "find ~/.ssh -maxdepth 1 -name '${SSH_USER}-${APPNAME}.pub' -print -quit")
if [ -z "$SSH_KEY_FOUND" ]; then
    scp -i "$SSH_KEY" "$HOME/.ssh/${SSH_USER}-${APPNAME}.pub" "$SSH_USER@$DOMAIN_NAME:/home/bitnami/.ssh/${SSH_USER}-${APPNAME}.pub"
fi
# Upload application files.
ssh -i "$SSH_KEY" "$SSH_USER@$DOMAIN_NAME" "mkdir -p /opt/bitnami/nginx/html/$APPNAME"

read -r -d '' SSH_COMMAND << EOM
DEPLOY_KEY_INCLUDED=\$(cat ~/.ssh/authorized_keys | grep '$SSH_USER@$DOMAIN_NAME')
if [ -z "\$DEPLOY_KEY_INCLUDED" ]; then
    cat /home/bitnami/.ssh/${SSH_USER}-${APPNAME}.pub >> /home/bitnami/.ssh/authorized_keys
fi
cd /opt/bitnami/nginx/conf/server_blocks
cp sample-server-block.conf.disabled $APPNAME-server-block.conf
cp sample-https-server-block.conf.disabled $APPNAME-https-server-block.conf
sudo /opt/bitnami/ctlscript.sh restart nginx
cd /opt/bitnami/nginx/html
sudo chown -R bitnami:daemon $APPNAME
EOM

ssh -i "$SSH_KEY" "$SSH_USER@$DOMAIN_NAME" "$SSH_COMMAND"

read -r -d '' NEXT_STEPS << EOM
Next steps:
1. Log in via SSH and run this command to generate your SSL certificate:
sudo /opt/bitnami/bncert-tool
2. Add the private SSH key to the GitHub repository as a deployment key. Location: $DEPLOY_KEY_PATH
3. Point your custom domain name to this server's static IP address.
4. Run the deploy.sh script to deploy the application.
EOM

echo "$NEXT_STEPS"
