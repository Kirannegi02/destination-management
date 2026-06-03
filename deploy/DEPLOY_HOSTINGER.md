# Deploy to Hostinger via GitHub Actions

Pushing to the `main` branch runs [`.github/workflows/deploy-hostinger.yml`](../.github/workflows/deploy-hostinger.yml), which:

1. Installs PHP dependencies (`composer install --no-dev`)
2. Builds `transport-frontend` and copies the build into `public/`
3. Uploads the project to Hostinger over **SSH/rsync**
4. Runs `php artisan migrate`, cache commands, and `storage:link` on the server

Live site reference: `https://esmo-2025.org` (API at `/api`, admin at `/admin`).

---

## Prerequisites on Hostinger

1. **SSH access** — hPanel → **Advanced** → **SSH Access** → enable and note host, port, username.
2. **PHP 8.2+** — hPanel → **Advanced** → **PHP Configuration** for the domain.
3. **Composer** on the server (SSH in and run `composer -V`; if missing, install per Hostinger docs or upload `vendor` only on first deploy).
4. **MySQL** database and `.env` on the server (never commit `.env`; the workflow excludes it).

### Correct folder layout

Laravel must live **above** the web root. Document root must point at Laravel’s `public/` folder.

**Option A (recommended):** project in a private folder, web root = `public/`

```text
/home/USER/domains/esmo-2025.org/
  laravel/              ← HOSTINGER_DEPLOY_PATH (app, vendor, .env, routes…)
  laravel/public/       ← set as Document Root in hPanel (or symlink public_html → here)
```

**Option B:** Hostinger default `public_html` is the web root

```text
/home/USER/domains/esmo-2025.org/
  app/, vendor/, .env, routes/…   ← parent of public_html
  public_html/                    ← same contents as Laravel public/ (index.php + React build)
```

If you use Option B, set `HOSTINGER_DEPLOY_PATH` to the **parent** of `public_html` (the folder that contains `app/`, `vendor/`, `.env`).

In hPanel → **Domains** → **Manage** → **Advanced** → **Document Root**, point to the directory that contains `index.php` and the React `index.html`.

---

## One-time server setup (SSH)

```bash
# Replace with your deploy path
cd /home/USER/domains/esmo-2025.org/laravel

# Copy env (edit DB_*, APP_KEY, APP_URL=https://esmo-2025.org, etc.)
cp .env.example .env   # or upload your existing .env manually
php artisan key:generate

# Writable dirs
chmod -R ug+rwx storage bootstrap/cache

php artisan storage:link
php artisan migrate --force
```

Ensure `.env` has at least:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://esmo-2025.org
DB_CONNECTION=mysql
# … your Hostinger MySQL credentials
```

---

## GitHub repository secrets

Repo → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

| Secret | Example / notes |
|--------|------------------|
| `HOSTINGER_SSH_HOST` | `ssh.esmo-2025.org` or IP from hPanel SSH page |
| `HOSTINGER_SSH_PORT` | `65002` (Hostinger often uses non-22; check hPanel) |
| `HOSTINGER_SSH_USER` | `u778718045` |
| `HOSTINGER_SSH_PRIVATE_KEY` | Full private key (PEM), including `BEGIN`/`END` lines |
| `HOSTINGER_DEPLOY_PATH` | Absolute path, e.g. `/home/u778718045/domains/esmo-2025.org/laravel` |
| `VITE_API_BASE_URL` | `https://esmo-2025.org/api` (baked into React at build time) |

### SSH key for GitHub Actions

On your PC:

```bash
ssh-keygen -t ed25519 -C "github-actions-deploy" -f hostinger_deploy -N ""
```

1. Add **`hostinger_deploy.pub`** in hPanel → SSH Access → **SSH keys**.
2. Paste contents of **`hostinger_deploy`** (private) into GitHub secret `HOSTINGER_SSH_PRIVATE_KEY`.

Test manually:

```bash
ssh -p PORT -i hostinger_deploy USER@HOST
```

---

## Branch and manual deploy

- Automatic deploy: push to **`main`**. To use another branch, edit `branches` in `deploy-hostinger.yml`.
- Manual deploy: GitHub → **Actions** → **Deploy to Hostinger** → **Run workflow**.

---

## What is not overwritten

Rsync **excludes** server-only data:

- `.env`
- `storage/app/public` (uploads)
- `storage/logs`, framework cache/session/view files

The workflow uses `--delete` so removed files in git are removed on the server; keep backups before large refactors.

---

## Troubleshooting

| Issue | What to check |
|-------|----------------|
| Workflow fails on rsync | SSH host/port/user/key; `HOSTINGER_DEPLOY_PATH` exists and is writable |
| 500 after deploy | `storage` / `bootstrap/cache` permissions; run `php artisan config:clear` via SSH |
| Admin/API 404 | Document root must be Laravel `public/`, not project root |
| React calls wrong API | `VITE_API_BASE_URL` secret; rebuild requires a new deploy |
| Migrations fail | DB credentials in server `.env`; run `php artisan migrate` once via SSH to see errors |
| WAF blocks API | See `public/.htaccess` ModSecurity rules |

---

## No SSH? (FTP-only plans)

If SSH is unavailable, use an FTP deploy action instead of rsync (slower, no `artisan` on deploy). Prefer upgrading to a Hostinger plan with SSH for Laravel. Ask if you need an `ftp-deploy` workflow variant.
