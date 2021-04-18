#!/bin/bash -xe
### Debug logs, disable when done.
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1
# Prep the OS
apt update
apt install -y default-mysql-client php-curl php-gd php-mbstring php-xml php-xmlrpc php-soap php-intl php-zip apache2 php libapache2-mod-php php-mysql
# Deploy latest version of wordpress and setup the apache
curl https://wordpress.org/latest.tar.gz | tar -xzvf - -C /var/www/html/
mv /var/www/html/wordpress/* /var/www/
echo '<VirtualHost *:80>' > /etc/apache2/sites-enabled/000-default.conf
echo 'DocumentRoot /var/www' >> /etc/apache2/sites-enabled/000-default.conf
echo '</VirtualHost>' >> /etc/apache2/sites-enabled/000-default.conf
mv /var/www/wp-config-sample.php /var/www/wp-config.php
mkdir -p /var/www/wp-content/uploads
chown -R www-data:www-data /var/www/
sed -i "s/localhost/${dbaddr}/"   /var/www/wp-config.php
sed -i "s/username_here/${dbuser}/" /var/www/wp-config.php
sed -i "s/password_here/${dbpass}/" /var/www/wp-config.php
sed -i "s/database_name_here/${dbname}/" /var/www/wp-config.php


### Override the default Hello World post
curl https://raw.githubusercontent.com/hunter86bg/terraform-aws/main/wp-content_install.php > /var/www/wp-content/install.php

### Restore the DB with the first web instance
### Next instance should not touch our DB
### Ugly , but should work
sleep $(/usr/bin/shuf -i10-30 -n1)


curl https://raw.githubusercontent.com/hunter86bg/terraform-aws/main/wp > /usr/local/bin/wp
chmod +x /usr/local/bin/wp
wp core install --path="/var/www" --url="http://${alb_dns}/" --title="Linux namespaces" --admin_user=${dbuser} --admin_password=${dbpass} --admin_email=hunter86_bg@av.bg --allow-root || true

##########################
### End of wordpress cli install
sleep $(/usr/bin/shuf -i10-30 -n1)

systemctl restart apache2


##########################
# DB optimize job
curl https://raw.githubusercontent.com/hunter86bg/terraform-aws/main/optimize.sh > /usr/local/bin/optimize.sh
chmod 755 /usr/local/bin/optimize.sh
sed -i "s/DBADDR/${dbaddr}/g" /usr/local/bin/optimize.sh
sed -i "s/USER/${dbuser}/g" /usr/local/bin/optimize.sh
sed -i "s/PASS/${dbpass}/g" /usr/local/bin/optimize.sh
sed -i "s/DATABASE/${dbname}/g" /usr/local/bin/optimize.sh

echo 'RANDOM_DELAY=45' >> /var/spool/cron/crontabs/root
echo '0 2 * * 7 /usr/local/bin/optimize.sh 2>&1 >> /var/log/optimize.log' >>  /var/spool/cron/crontabs/root

##########################

