Requirements
- apache2 (nginx support coming soon)
- php5.x+
- mysql extension (other dbs will be eventually supported)
- curl extension

# Debian-ish help

apt install apache2 libapache2-mod-php php-mysql php-curl mysql-server git
a2enmod rewrite

Change AllowOverride to All
/etc/apache2/apache2.conf
```
<Directory /var/www/>
	Options Indexes FollowSymLinks
	AllowOverride All
	Require all granted
</Directory>
```

cd /var/www
mv html old
git clone https://gitgud.io/odilitime/lynxphp.git
ln -s lynxphp/frontend html
cd lynxphp/frontend
ln -s ../backend

then you need to make config_HOSTNAME.php in frontend and backend

You'll likely need to go into mysql, create the db and user:
```
create database lynxphp;
grant all on lynxphp.* to x@localhost identified by 'y';
flush privileges;
```

then backend config will need some database settings set
```
define('DB_HOST', 'localhost');
define('DB_USER', 'x');
define('DB_PWD', 'y');
define('DB_NAME', 'lynxphp');
```

also you'll need to make these directories and make sure they're web server writable (chown www-data or chmod 777)
- backend/storage
- backend/storage/tmp
- backend/storage/boards

# Installation Help

Frontend now has an install.php you can run to check your installation, to see if you have the requirements and have followed the install instructions correctly.
