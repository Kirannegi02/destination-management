# Deploy on EC2 (http://51.20.115.26)

Goal:

| URL | What runs |
|-----|-----------|
| `http://51.20.115.26/` | React app (`transport-frontend` build) |
| `http://51.20.115.26/admin/login` | Laravel admin |
| `http://51.20.115.26/api/...` | Laravel API |

Apache **DocumentRoot** must be Laravel’s `public/` folder (on your server that may be named `public_html`).

---

## 1. Correct folder layout on the server

Laravel **must** sit one level **above** the web root. `public/index.php` loads `../vendor`, `../app`, etc.

**Recommended:**

```text
/home/ec2-user/dms/                 ← full Laravel project (app, vendor, .env, routes…)
/home/ec2-user/dms/public/         ← Apache DocumentRoot (index.php + React build)
```

If you already use `public_html` as the web root, either:

- Point Apache to `/home/ec2-user/dms/public`, **or**
- Symlink: `ln -sfn /home/ec2-user/dms/public /home/ec2-user/public_html`

**Wrong (will break admin/API):** only `public_html` on disk with no `app/`, `vendor/`, `.env` in the parent directory.

Upload/sync the **whole** project into `~/dms/` (git, zip, or rsync), not only `public/`.

---

## 2. Install stack (Amazon Linux 2023 / AL2)

```bash
# Amazon Linux 2023
sudo dnf install -y httpd php php-cli php-fpm php-mysqlnd php-mbstring \
  php-xml php-curl php-zip php-gd php-intl php-bcmath mariadb105

# Composer (if missing)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Enable and start services:

```bash
sudo systemctl enable --now httpd
sudo systemctl enable --now mariadb   # or mysql
```

Open port 80 in the **EC2 security group** (inbound HTTP from 0.0.0.0/0 or your IP).

---

## 3. Database

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE dms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dms'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL ON dms.* TO 'dms'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Import your dump:

```bash
mysql -u dms -p dms < ~/u778718045_dms.sql
```

---

## 4. Laravel `.env` on the server

```bash
cd ~/dms
cp .env.example .env
nano .env
```

Set at least:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://51.20.115.26

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dms
DB_USERNAME=dms
DB_PASSWORD=YOUR_STRONG_PASSWORD

CORS_ALLOWED_ORIGINS=http://51.20.115.26
```

Then:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

Permissions:

```bash
sudo chown -R apache:apache storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

If the app runs as `ec2-user` under Apache, adjust user/group to match your `httpd` config (`apache` or `nginx`).

---

## 5. Build React frontend (on your PC or on the server)

On a machine with Node 18+:

```bash
cd transport-frontend
cp .env.example .env
```

`.env` for production:

```env
VITE_API_BASE_URL=http://51.20.115.26/api
```

```bash
npm ci
npm run build
```

Copy build output **into Laravel `public/`** (keeps `index.php`):

```bash
# From repo root
cp -r transport-frontend/dist/* public/
```

On the server after upload:

```bash
cp -r ~/frontend/dist/* ~/dms/public/    # if you built into ~/frontend
```

You should have both `~/dms/public/index.php` and `~/dms/public/index.html`.

---

## 6. Apache virtual host

Create `/etc/httpd/conf.d/dms.conf` (see `deploy/apache-ec2.conf` in this repo):

```bash
sudo nano /etc/httpd/conf.d/dms.conf
# paste contents, fix paths if needed
sudo apachectl configtest
sudo systemctl restart httpd
```

**SELinux** (if enabled): allow httpd to read the app:

```bash
sudo chcon -R -t httpd_sys_rw_content_t /home/ec2-user/dms/storage
sudo chcon -R -t httpd_sys_rw_content_t /home/ec2-user/dms/bootstrap/cache
sudo setsebool -P httpd_read_user_content 1
sudo setsebool -P httpd_enable_homedirs 1
```

---

## 7. Verify

```bash
curl -I http://127.0.0.1/
curl -I http://127.0.0.1/admin/login
curl -I http://127.0.0.1/api/vehicles
```

In a browser:

- http://51.20.115.26/ → transport UI  
- http://51.20.115.26/admin/login → admin login page  

---

## 8. Troubleshooting

| Symptom | Fix |
|--------|-----|
| 403 Forbidden | DocumentRoot path wrong; SELinux; `AllowOverride All` |
| 500 on `/admin` | Missing `vendor/` or `.env`; run `composer install`; check `storage/logs/laravel.log` |
| Blank `/` | React build not copied to `public/`; missing `index.html` |
| API CORS errors | Set `CORS_ALLOWED_ORIGINS=http://51.20.115.26` and `php artisan config:clear` |
| `index.php` downloads instead of running | PHP not installed or not configured in Apache (`php-fpm` / `mod_php`) |

Logs:

```bash
sudo tail -f /var/log/httpd/error_log
tail -f ~/dms/storage/logs/laravel.log
```

---

## Quick map of your current EC2 home

| Path | Use |
|------|-----|
| `~/dms/` | Put full Laravel project here |
| `~/frontend/` | Optional: React source; run `npm run build` here |
| `~/public_html/` | Prefer symlink → `~/dms/public` or change Apache to `~/dms/public` |
| `~/u778718045_dms.sql` | Import into MySQL |

Do **not** serve the site only from a flat `public_html` zip unless the Laravel root (`app`, `vendor`, `.env`) is the **parent** of that folder.
