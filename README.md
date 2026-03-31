# gamesense-forum

FluxBB-derived GameSense forum with:

- invite/bootstrap registration flow
- integrated AJAX Chat shoutbox on the forum index
- local Docker setup for development
- seeded SQL dump for fresh installs

## Fresh install behavior

On a fresh database import:

- `Guest` is `uid 0`
- there is no pre-seeded admin account
- the first user who registers becomes admin `uid 1`
- the first registration does not require an invite
- later registrations go back to invite-only

## Debian 13 installation

This section is for a native Debian 13 install with either:

- Apache + `libapache2-mod-php`
- Nginx + `php-fpm`

The forum does not require Apache specifically.

### 1. Install system packages

Choose one web stack.

#### Option A: Apache

```bash
sudo apt update
sudo apt install -y \
  git \
  apache2 \
  mariadb-server \
  libapache2-mod-php \
  php-cli \
  php-mysql \
  php-curl \
  php-gd \
  php-mbstring \
  php-xml \
  php-zip \
  unzip
```

#### Option B: Nginx

```bash
sudo apt update
sudo apt install -y \
  git \
  nginx \
  mariadb-server \
  php-fpm \
  php-cli \
  php-mysql \
  php-curl \
  php-gd \
  php-mbstring \
  php-xml \
  php-zip \
  unzip
```

### 2. Clone the repo

```bash
sudo git clone https://github.com/6a6179/gamesense-forum.git /var/www/gamesense-forum
cd /var/www/gamesense-forum
```

### 3. Prepare writable directories

```bash
sudo mkdir -p forums/img/avatars
sudo find forums/cache -maxdepth 1 -type f -name 'cache_*.php' -delete
sudo chown -R www-data:www-data forums/cache forums/img/avatars
sudo chmod -R u+rwX,go-rwx forums/cache forums/img/avatars
```

### 4. Create the database

```bash
sudo mysql <<'SQL'
CREATE DATABASE gamesense_forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gamesense'@'localhost' IDENTIFIED BY 'change_this_password';
GRANT ALL PRIVILEGES ON gamesense_forum.* TO 'gamesense'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### 5. Import the schema and seed data

```bash
mysql -u gamesense -p gamesense_forum < sql.sql
```

After import, set the board URL to the real public URL:

```bash
sudo mysql gamesense_forum <<'SQL'
UPDATE gs_config
SET conf_value = 'https://forum.example.com/forums'
WHERE conf_name = 'o_base_url';
SQL
```

If you do not plan to configure reCAPTCHA immediately, disable it:

```bash
sudo mysql gamesense_forum <<'SQL'
UPDATE gs_config
SET conf_value = '0'
WHERE conf_name = 'recaptcha_enabled';
SQL
```

### 6. Configure the web server

Choose one of the following.

#### Option A: Apache

Create `/etc/apache2/sites-available/gamesense-forum.conf`:

```apache
<VirtualHost *:80>
    ServerName forum.example.com
    DocumentRoot /var/www/gamesense-forum

    <Directory /var/www/gamesense-forum>
        AllowOverride All
        Require all granted
    </Directory>

    SetEnv FORUM_DB_HOST localhost
    SetEnv FORUM_DB_NAME gamesense_forum
    SetEnv FORUM_DB_USER gamesense
    SetEnv FORUM_DB_PASSWORD change_this_password
    SetEnv FORUM_DB_PREFIX gs_

    SetEnv FORUM_COOKIE_NAME pun_cookie_gamesense
    SetEnv FORUM_COOKIE_DOMAIN
    SetEnv FORUM_COOKIE_PATH /
    SetEnv FORUM_COOKIE_SECURE 0
    SetEnv FORUM_COOKIE_SEED replace_this_with_a_long_random_secret

    ErrorLog ${APACHE_LOG_DIR}/gamesense-forum-error.log
    CustomLog ${APACHE_LOG_DIR}/gamesense-forum-access.log combined
</VirtualHost>
```

Enable the site and reload Apache:

```bash
sudo a2enmod rewrite
sudo a2ensite gamesense-forum
sudo systemctl reload apache2
```

If you terminate TLS on Apache, set:

```apache
SetEnv FORUM_COOKIE_SECURE 1
```

#### Option B: Nginx

Debian 13 ships `php-fpm` as the default FPM package. On a default install this is PHP 8.4, so the usual socket path is `/run/php/php8.4-fpm.sock`.

Create `/etc/nginx/sites-available/gamesense-forum`:

```nginx
server {
    listen 80;
    server_name forum.example.com;
    root /var/www/gamesense-forum;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;

        fastcgi_param FORUM_DB_HOST localhost;
        fastcgi_param FORUM_DB_NAME gamesense_forum;
        fastcgi_param FORUM_DB_USER gamesense;
        fastcgi_param FORUM_DB_PASSWORD change_this_password;
        fastcgi_param FORUM_DB_PREFIX gs_;

        fastcgi_param FORUM_COOKIE_NAME pun_cookie_gamesense;
        fastcgi_param FORUM_COOKIE_DOMAIN "";
        fastcgi_param FORUM_COOKIE_PATH /;
        fastcgi_param FORUM_COOKIE_SECURE 0;
        fastcgi_param FORUM_COOKIE_SEED replace_this_with_a_long_random_secret;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable the site and reload Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/gamesense-forum /etc/nginx/sites-enabled/gamesense-forum
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl enable --now nginx php8.4-fpm
sudo systemctl reload nginx
```

If your PHP-FPM socket path is different, inspect `/run/php/` and adjust `fastcgi_pass` accordingly.

If you terminate TLS on Nginx, set:

```nginx
fastcgi_param FORUM_COOKIE_SECURE 1;
```

### 7. Complete first boot

Open:

- `https://forum.example.com/forums/`
- `https://forum.example.com/forums/register.php`

On a clean install, register the first account through `register.php`. That account becomes the initial admin automatically.

## Local Docker development

The repo includes a local Docker setup for development.

### Start

```bash
cp .env.example .env
docker compose up -d
```

Open:

- `http://localhost:8080/forums/`
- `http://localhost:8080/forums/register.php`

Local database defaults:

- host: `127.0.0.1`
- port: `3307`
- database: `gamesense_forum`
- user: `gamesense`
- password: `gamesense_local_pw`

### Stop

```bash
docker compose down
```

### Reset the local database

```bash
docker compose down -v
docker compose up -d
```

## Notes

- `forums/config.php` reads database and cookie settings from environment variables.
- The shoutbox is already integrated and lives under `forums/chat/`.
- If you change the board URL or move the install, clear generated cache files in `forums/cache/cache_*.php`.
