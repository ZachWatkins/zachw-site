# Set up an AWS Lightsail instance

**Issue Let's Encrypt certificate for Lightsail**

```shell
DOMAIN=example.com
WILDCARD=*.$DOMAIN
sudo certbot -d $DOMAIN -d $WILDCARD --manual --preferred-challenges dns certonly
```

**Create SSH Key (locally) for deploy user to run in GitHub Actions**

```shell
ssh-keygen -t rsa -b 4096 -C "$USER@$DOMAIN" -f ~/.ssh/$USER
```

**Declare SSH secrets in GitHub repository environment**

1. `HOST` - Hostname or IP address of Lightsail instance
2. `USERNAME` - Username of deploy user
3. `DEPLOYKEY` - Private key of deployment user (from `~/.ssh/$USER`)

**Create deploy user**

```shell
sudo adduser $USER
sudo passwd $USER
sudo usermod -aG sudo $USER
su - $USER
mkdir ~/.ssh
chmod 700 ~/.ssh
touch ~/.ssh/authorized_keys
nano ~/.ssh/authorized_keys
# paste contents of $USER.pub
chmod 600 ~/.ssh/authorized_keys
exit
```
