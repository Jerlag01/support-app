# support-app – installation guide

How to install the helpdesk on a new server, from an empty machine to a working HTTPS site. The helpdesk has three parts, all in NL/FR/EN:

- the client portal at `/` (tickets, knowledge base at `/kb`),
- the agent panel at `/agent`,
- the admin panel at `/admin`.

- **Stack:** Laravel 13, Filament 5, PHP 8.4, MySQL/MariaDB, nginx, Vite/Tailwind for the front-end build.
- **Example names in this guide:** domain `support.example.be`, install path `/var/www/support-app`, database `support_app`, database user `support_app`. Replace them with your own values.
- All commands run as `root` unless they start with `sudo -u www-data`.

> **Do not install this on top of an existing osTicket.** It uses its own database and its own folder. If osTicket currently runs on the target domain, install this next to it on a test domain first and switch the domain over afterwards (section 10).

---

## 1. Requirements

| Component | Version / note |
|---|---|
| OS | Ubuntu 24.04 LTS or Debian 12 (other Linux works, package names may differ) |
| PHP | **8.4** (8.3 minimum) with FPM |
| PHP extensions | bcmath, ctype, curl, dom, fileinfo, gd, intl, mbstring, mysql (pdo_mysql), openssl, tokenizer, xml, zip |
| Database | MySQL 8.0+ or MariaDB 10.6+ |
| Web server | nginx |
| Composer | 2.x |
| Node.js | 20+ with npm – only needed to build the front-end assets (can be done on another machine) |
| Other | git, unzip, certbot (Let's Encrypt), time sync (NTP) – the authenticator-app codes depend on a correct server clock |
| Mail | An SMTP account: new tickets are e-mailed to the agents of the department, replies and status changes to the client |
| Network | Outbound internet during the build: `npm run build` downloads fonts from fonts.bunny.net |

`intl` is required by Filament (the agent and admin panels will not load without it).

---

## 2. Install the server packages

### 2.1 PHP 8.4

```bash
apt update
apt install -y software-properties-common ca-certificates curl unzip git
add-apt-repository -y ppa:ondrej/php        # Debian: use https://packages.sury.org/php instead
apt update
apt install -y php8.4-fpm php8.4-cli php8.4-bcmath php8.4-curl php8.4-gd php8.4-intl \
               php8.4-mbstring php8.4-mysql php8.4-xml php8.4-zip php8.4-opcache
php8.4 -v
php8.4 -m | grep -E 'intl|pdo_mysql|mbstring|gd|zip'
```

### 2.2 nginx, database, Composer, Node.js, certbot

```bash
apt install -y nginx mysql-server certbot python3-certbot-nginx     # or: mariadb-server

# Composer
curl -sS https://getcomposer.org/installer | php8.4 -- --install-dir=/usr/local/bin --filename=composer
composer --version

# Node.js 22 LTS (only on the machine that builds the assets)
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
node -v && npm -v
```

---

## 3. Database

```bash
mysql -u root
```

```sql
CREATE DATABASE support_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'support_app'@'localhost' IDENTIFIED BY 'CHANGE-ME-strong-password';
GRANT ALL PRIVILEGES ON support_app.* TO 'support_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

If the database runs on another server, create the user as `'support_app'@'<web server IP>'` and allow the web server through the database firewall (port 3306).

> Use a database of its own. Never point this app at the osTicket database, and never run `migrate:fresh` against a shared database: it drops **all** tables in it.

---

## 4. Get the code

The app lives in the `support-app` folder of the repository.

```bash
mkdir -p /var/www
cd /var/www
git clone https://github.com/JLTechologies/Stock-Rewrite.git stock-rewrite
ln -s /var/www/stock-rewrite/support-app /var/www/support-app     # or move/copy the folder
```

The repository is private: use a GitHub deploy key or a personal access token for the clone.

Not in git (and therefore created during the install): `.env`, `vendor/`, `node_modules/`, `public/build/`, `public/storage` and everything stored in `storage/`.

---

## 5. Configuration (`.env`)

```bash
cd /var/www/support-app
cp .env.example .env
nano .env
```

Set at least these values:

```dotenv
APP_NAME="Support"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://support.example.be
APP_LOCALE=nl
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=nl_BE

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=support_app
DB_USERNAME=support_app
DB_PASSWORD="CHANGE-ME-strong-password"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

# Mail: can stay on "log" here; the real SMTP settings are entered in /admin
# (Settings > E-mail) and override these.
MAIL_MAILER=log
MAIL_FROM_ADDRESS="support@example.be"
MAIL_FROM_NAME="${APP_NAME}"
```

Notes:
- `QUEUE_CONNECTION=sync`: notification e-mails are sent while the request runs, so no queue worker is needed.
- `SESSION_SECURE_COOKIE=true` requires HTTPS (step 10). While testing over plain HTTP, set it to `false`.
- `APP_KEY` is filled in by the next step. **Keep it safe**: the SMTP password stored in the admin settings is encrypted with it.
- The company name, colours, logo and mail settings are **not** in `.env`: they are managed in `/admin` > Settings.

---

## 6. Install the application

```bash
cd /var/www/support-app

# PHP dependencies (production: without dev tools)
composer install --no-dev --optimize-autoloader --no-interaction

# Application key (only on a new install – never on a moved site, see section 13)
php8.4 artisan key:generate --force

# Front-end assets for the client portal (Tailwind/Vite) -> public/build
npm ci
npm run build

# Livewire scripts are served as static files (see the nginx note in section 9)
php8.4 artisan vendor:publish --tag=livewire:assets --force

# Database tables
php8.4 artisan migrate --force

# Default helpdesk set-up: SLA plans, departments (Support, Service & onderhoud,
# Administratie) and help topics. Safe to run again; it only adds what is missing.
php8.4 artisan db:seed --force

# Public link for the uploaded logo, favicon and knowledge-base files
php8.4 artisan storage:link
```

> **Do not** run `php artisan db:seed --class=DemoSeeder` on a real server. It creates demo users and tickets for local testing only.

**No Node.js on the server?** Run `npm ci && npm run build` on another machine with the same code, then copy `public/build/` to the server.

---

## 7. First administrator

```bash
cd /var/www/support-app
php8.4 artisan support:create-user --role=admin
```

The command asks for the name, e-mail, password and language. An **admin** can use both `/agent` and `/admin`.

Other roles:
- `--role=agent`: handles tickets in `/agent`.
- `--role=customer`: client portal only.

Agents and clients are normally added later in the panels.

---

## 8. File permissions

The web server (PHP-FPM runs as `www-data`) must be able to write to `storage/` and `bootstrap/cache/`. Ticket attachments are stored in `storage/app/private`:

```bash
chown -R www-data:www-data /var/www/stock-rewrite/support-app
find /var/www/support-app/storage /var/www/support-app/bootstrap/cache -type d -exec chmod 775 {} \;
```

Run every later `artisan` command as root, then repeat the `chown` above. Alternatively run artisan as the web user (`sudo -u www-data php8.4 artisan …`), which avoids files owned by root.

### PHP-FPM sandbox (only when you hit a 500 with "read-only file system")

Some setups run php-fpm with systemd `ProtectSystem=full`, which makes `/usr` read-only. That is a problem when the app lives under `/usr/share/nginx/html/…`. Under `/var/www` it does not apply. If you do use such a path, allow writing to the two folders:

```bash
mkdir -p /etc/systemd/system/php8.4-fpm.service.d
cat > /etc/systemd/system/php8.4-fpm.service.d/support-app-writable.conf <<'EOF'
[Service]
ReadWritePaths=/usr/share/nginx/html/devops/support-app/storage /usr/share/nginx/html/devops/support-app/bootstrap/cache
EOF
systemctl daemon-reload && systemctl restart php8.4-fpm
```

### PHP upload limits

Ticket attachments and knowledge-base files can be up to 10 MB each. The nginx config below raises the PHP limits per site. If you prefer to set them globally, edit `/etc/php/8.4/fpm/php.ini`:

```ini
upload_max_filesize = 20M
post_max_size = 50M
```

then `systemctl restart php8.4-fpm`.

---

## 9. nginx

Create `/etc/nginx/sites-available/support-app`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name support.example.be;
    root /var/www/support-app/public;

    index index.php;
    charset utf-8;
    client_max_body_size 100m;

    access_log /var/log/nginx/support-app.access.log;
    error_log  /var/log/nginx/support-app.error.log;

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;
    add_header Referrer-Policy same-origin;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize=100M \n post_max_size=100M";
    }

    # Static files: cache long, but fall back to Laravel when the file does not exist
    location ~* \.(?:ico|css|js|mjs|map|jpe?g|png|webp|gif|svg|woff2?)$ {
        try_files $uri /index.php?$query_string;
        expires 365d;
        access_log off;
    }

    # Keep Let's Encrypt working, block every other dot-file (.env, .git…)
    location ^~ /.well-known/acme-challenge/ { allow all; }
    location ~ /\.(?!well-known) { deny all; }
}
```

```bash
ln -s /etc/nginx/sites-available/support-app /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

> **Why the `try_files` in the static block matters:** without it, `.js`/`.css` URLs that Laravel or Livewire generate (instead of real files) return 404. That is also why section 6 publishes the Livewire scripts into `public/vendor/livewire`.
>
> Ticket attachments are **not** in `public/`: they are served through the app (`/attachments/{id}`), which checks that the user may see the ticket.

---

## 10. HTTPS (and switching from osTicket)

Point the DNS A/AAAA record of the domain to the server, then:

```bash
certbot --nginx -d support.example.be --redirect
```

certbot adds the certificate lines and the HTTP→HTTPS redirect to the nginx config, and renews the certificate automatically.

**Taking over a domain that runs osTicket today:**
1. Install and test on a temporary domain first.
2. On the switch-over, change `root` in the existing nginx `server` block of that domain to `/var/www/support-app/public`, and the PHP socket to `php8.4-fpm`.
3. Set `APP_URL` in `.env` to the final domain.
4. Run section 11 again and `systemctl reload nginx`.

Leave the osTicket files and database untouched until you are sure you no longer need them.

---

## 11. Production optimisation

```bash
cd /var/www/support-app
php8.4 artisan optimize          # config, routes, views, events cache
php8.4 artisan filament:optimize # Filament component + icon cache
chown -R www-data:www-data /var/www/stock-rewrite/support-app
systemctl restart php8.4-fpm     # clears OPcache
```

After **every** code or `.env` change, run these four commands again: with the caches on, changes do not show until the caches are rebuilt.

---

## 12. First steps in the admin panel

Go to `https://support.example.be/admin` and log in with the account from section 7.

The panels use each user's own language (chosen when the user is created, changeable in the profile). The labels below are the English ones.

1. **Settings > Organization:** company name and tagline, contact details, social media links.
2. **Settings > Helpdesk:**
   - ticket settings;
   - portal settings, including whether visitors may **register themselves** (on by default – switch it off if only known clients may open tickets).
3. **Settings > E-mail:** SMTP server, port, encryption, user and password, sender. Click **Send test e-mail**. **Without working mail, nobody is notified of tickets.**
4. **Settings > Appearance:** colours, logo, favicon, and the homepage background picture behind the slogan (optional – without it the default navy background stays).
5. **Departments, Help topics, SLA plans, Teams:** adapt the defaults from the seeder to your organisation.
6. **Agents:** add the people who handle tickets and put them in their departments. An agent without departments sees all tickets.
7. **Who is who** (`/admin`): the people on the portal's "who is who" page: name, job title per language, phone, mobile, e-mail and a photo (without a photo the portal logo is shown). Drag the rows to change their order. The page appears in the portal menu as soon as one person is visible; switch it off in **Settings > Helpdesk > Client portal**.
8. **Footer links** (`/admin`): extra links (e.g. terms and conditions, the main website) in their own row at the bottom of every portal page. The row only shows when at least one visible link exists. Allowed addresses: `https://…`, a portal path like `/kb`, `mailto:` or `tel:`.
9. **Two-step verification** (recommended, not required): every user (clients, agents and admins) can switch it on in their own profile – in the portal under *Profile*, in `/agent` and `/admin` via the user menu > *Profile*. Two methods: an authenticator app (Google/Microsoft Authenticator…, with one-time recovery codes) and a code by e-mail. One set-up works for the portal and both panels. Users without it see a reminder. If someone loses both phone and recovery codes, an admin (for agents, in `/admin` > Agents) or an agent (for clients, in `/agent` > Clients) can use **Reset two-step verification**.
10. In `/agent`: **Knowledge base** (FAQ categories and articles), **Canned responses**, **Clients / Organizations**.

The client portal is at `https://support.example.be/`, the knowledge base at `/kb`.

---

## 13. Moving an existing site to the new server

Use this instead of a clean install when tickets, users and files have to come along.

On the **old** server:

```bash
cd /path/to/support-app
mysqldump --single-transaction --routines -u <user> -p support_app > /tmp/support.sql
tar czf /tmp/support-files.tgz -C storage/app public private   # logo/KB files + ticket attachments
grep ^APP_KEY= .env                                            # copy this value
```

On the **new** server, follow sections 1–6 but:
- **do not** run `key:generate`: put the old `APP_KEY` in `.env` (otherwise the stored SMTP password cannot be decrypted);
- import the database instead of `migrate`/`db:seed`, then run `migrate` anyway to apply any newer migrations:

```bash
mysql -u support_app -p support_app < /tmp/support.sql
tar xzf /tmp/support-files.tgz -C /var/www/support-app/storage/app
cd /var/www/support-app
php8.4 artisan migrate --force
php8.4 artisan storage:link
php8.4 artisan cache:clear
```

Then continue with sections 8–11. Change `APP_URL` if the domain changes.

---

## 14. Updating to a new version

```bash
cd /var/www/stock-rewrite
php8.4 /var/www/support-app/artisan down        # maintenance page
git pull
cd support-app
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php8.4 artisan vendor:publish --tag=livewire:assets --force
php8.4 artisan migrate --force
php8.4 artisan optimize && php8.4 artisan filament:optimize
chown -R www-data:www-data /var/www/stock-rewrite/support-app
systemctl restart php8.4-fpm
php8.4 artisan up
```

Back up the database and the attachments before an update (see section 15).

---

## 15. Backups

Back up these three things; everything else can be rebuilt from git:

| What | How |
|---|---|
| Database | `mysqldump --single-transaction -u support_app -p support_app > support-$(date +%F).sql` |
| Files | `storage/app/private/` (ticket attachments) and `storage/app/public/` (logo, favicon, knowledge-base files) |
| Configuration | `.env` (contains `APP_KEY` and the database password) |

---

## 16. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| HTTP 500, nothing in `storage/logs` | PHP cannot write to `storage/`: fix ownership (section 8), or add the systemd `ReadWritePaths` drop-in |
| 500 with *The "intl" PHP extension is required…* | `php8.4-intl` is missing: `apt install php8.4-intl && systemctl restart php8.4-fpm` |
| `/agent` or `/admin` without styling / buttons do nothing | Livewire/Filament assets give 404: check the static `location` block (section 9), run `php8.4 artisan vendor:publish --tag=livewire:assets --force` and `php8.4 artisan filament:assets` |
| Portal without styling, "Vite manifest not found" | The front-end was not built: `npm ci && npm run build` (or copy `public/build/`) |
| Logo / favicon / KB images give 404 | `php8.4 artisan storage:link` is missing |
| Attachments give 404 after a move | `storage/app/private` was not copied (section 13) |
| No ticket e-mails | Check Settings > E-mail and use **Send test e-mail**; errors are in `storage/logs/laravel.log` |
| Authenticator codes are always "not valid" | The server clock is off: the codes depend on the time. Check `timedatectl` and enable time sync (`timedatectl set-ntp true`). |
| No two-step verification e-mail arrives | E-mail codes need working mail (Settings > E-mail). Users can fall back on the authenticator app or a recovery code |
| A new agent sees no tickets / too many tickets | Agent visibility follows departments: no departments = all tickets |
| Changes do not show up | Caches are on: `php8.4 artisan optimize:clear`, then `optimize` + `filament:optimize` again, then restart php8.4-fpm |
| Logged out immediately / 419 errors | `SESSION_SECURE_COOKIE=true` on a site without HTTPS, or a wrong `APP_URL` |

Logs: `storage/logs/laravel.log` (application), `/var/log/nginx/support-app.error.log` (nginx), `journalctl -u php8.4-fpm` (PHP-FPM).

---

## 17. Final checklist

- [ ] `https://support.example.be/` shows the client portal, the language switch works (NL/FR/EN)
- [ ] `/admin` and `/agent` log in with the first admin
- [ ] Settings > E-mail > **Send test e-mail** arrives
- [ ] A test ticket from the portal (with an attachment) arrives in `/agent`, the agents of its department receive an e-mail, and the attachment can be opened
- [ ] A reply from `/agent` reaches the client by e-mail
- [ ] Self-registration on or off as intended (Settings > Helpdesk)
- [ ] Two-step verification: set up the authenticator app on the first admin account, log out and back in with a code
- [ ] `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] Backups of the database, `storage/app/private`, `storage/app/public` and `.env` are scheduled
