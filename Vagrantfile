# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = '2'

@script = <<SCRIPT
# Install dependencies
apt-get install python-software-properties
add-apt-repository ppa:ondrej/php
apt-get update
apt-get install -y apache2 git curl php7.3 php7.3-bcmath php7.3-bz2 php7.3-cli php7.3-curl php7.3-intl php7.3-json
apt-get install -y php7.3-mbstring php7.3-opcache php7.3-soap php7.3-sqlite3 php7.3-xml php7.3-xsl php7.3-zip
apt-get install -y libapache2-mod-php7.3 php7.3-pdo php7.3-mysql mysql-server mysql-client

mysql -e "CREATE DATABASE IF NOT EXISTS doctrine CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER If NOT EXISTS 'doctrine'@'%' IDENTIFIED BY '123';"
mysql -e "GRANT ALL PRIVILEGES ON doctrine.* TO 'doctrine'@'%';"

# Configure Apache
echo '<VirtualHost *:80>
	DocumentRoot /var/www/public
	AllowEncodedSlashes On

	<Directory /var/www/public>
		Options +Indexes +FollowSymLinks
		DirectoryIndex index.php index.html
		Order allow,deny
		Allow from all
		AllowOverride All
	</Directory>

	ErrorLog ${APACHE_LOG_DIR}/error.log
	CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf
a2enmod rewrite
service apache2 restart

if [ -e /usr/local/bin/composer ]; then
    /usr/local/bin/composer self-update
else
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Reset home directory of vagrant user
if ! grep -q "cd /var/www" /home/vagrant/.profile; then
    echo "cd /var/www" >> /home/vagrant/.profile
fi

cd /var/www
composer install
SCRIPT

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = 'bento/ubuntu-18.04'
  config.vm.network "forwarded_port", guest: 80, host: 8080
  config.vm.synced_folder '.', '/var/www', mount_options: ["sec=ntlmssp"]
  config.vm.provision 'shell', inline: @script

  config.vm.provider "hyperv" do |vb|
    #vb.customize ["modifyvm", :id, "--memory", "1024"]
    #vb.customize ["modifyvm", :id, "--name", "ZF Application - Ubuntu 16.04"]

  end
end
