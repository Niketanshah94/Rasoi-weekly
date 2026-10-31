# Hosting Rasoi on your own Ubuntu server

These steps set up a fresh **Ubuntu 24.04** server (for example a Contabo VPS) to run Rasoi: the customer app, the admin panel, the queue worker and the scheduler, with free HTTPS.

You don’t need a domain to start. Until you have one, the app runs on `https://<your-ip-with-dashes>.sslip.io` (for server IP `203.0.113.5`, that’s `https://203-0-113-5.sslip.io`). sslip.io is a free service that points that name at your IP.

**Before you start:** the setup script turns on the firewall (allowing only SSH on port 22, HTTP and HTTPS) and replaces nginx’s default site. If this server already runs other websites, or SSH uses a different port, tell Claude before running it.

## 1. Connect to the server

```
ssh root@YOUR-SERVER-IP
```

## 2. Create the app user and a deploy key

The repository is private, so the server needs its own read-only key to pull the code.

```
adduser --disabled-password --gecos "" rasoi
apt-get update && apt-get install -y git
sudo -u rasoi mkdir -p -m 700 /home/rasoi/.ssh
sudo -u rasoi ssh-keygen -t ed25519 -N "" -C rasoi-server -f /home/rasoi/.ssh/id_ed25519
cat /home/rasoi/.ssh/id_ed25519.pub
```

Copy the line that starts with `ssh-ed25519`.

## 3. Add the key on GitHub

On GitHub, open the repository → **Settings** → **Deploy keys** → **Add deploy key**. Title: `Contabo server`. Paste the key. Leave **Allow write access** unticked. Save.

## 4. Clone the code

```
mkdir -p /var/www/rasoi && chown rasoi:rasoi /var/www/rasoi
sudo -u rasoi git clone --branch claude/new-session-gplyu0 git@github.com:Niketanshah94/Rasoi-weekly.git /var/www/rasoi
```

When it asks whether to trust `github.com`, type `yes`.

(Once the work is merged into `main`, clone `main` instead.)

## 5. Run the setup script

Use your email address: Let’s Encrypt uses it for certificate expiry notices.

```
sudo bash /var/www/rasoi/deploy/provision.sh you@example.com
```

It takes 5 to 10 minutes. It installs PHP 8.3, PostgreSQL, Redis, nginx, Node and Composer, creates the database with a random password, builds the app, starts the queue worker (Horizon) and the scheduler, and gets an HTTPS certificate. At the end it prints your addresses.

## 6. Create your admin login

```
sudo -u rasoi php /var/www/rasoi/artisan make:filament-user
sudo -u rasoi php /var/www/rasoi/artisan tinker --execute="App\Models\Staff::where('email', 'YOUR-EMAIL')->update(['role' => 'owner'])"
```

The second line makes you an owner, which you need to open Horizon.

Then open the app address in a browser to check it works, and the `admin.` address to sign in to the admin panel.

## 7. Point the Android app at your server

On GitHub, open the repository → **Settings** → **Secrets and variables** → **Actions** → **Variables** → **New repository variable**:

- Name: `RASOI_SERVER_URL`
- Value: your app address, for example `https://203-0-113-5.sslip.io`

Then **Actions** → **android** → **Run workflow**. When it finishes, download the new APK from **Releases** (`android-dev`) on your phone and install it. The app now opens your server straight away. If the server can’t be reached, it shows the connect screen instead.

## Updating to the latest code

```
sudo -u rasoi bash /var/www/rasoi/deploy/deploy.sh
```

This pulls the latest code, installs, builds, migrates the database and restarts the queue worker. Changes to screens and features reach the Android app as soon as you deploy; you only need a new APK when something under `mobile/` changes.

## When you have a domain

1. At your domain registrar, add two **A records** pointing at your server’s IP address:
   - the name you want for the app, for example `@` (the bare domain) or `app`
   - `admin` in front of it, for example `admin` or `admin.app`
2. Wait until they resolve (usually minutes, sometimes a few hours). Check with `ping rasoi.example.com`.
3. Run the setup script again with the domain:

   ```
   sudo bash /var/www/rasoi/deploy/provision.sh you@example.com rasoi.example.com
   ```

   It keeps your database and settings, switches the web server to the new names and gets a certificate for them.
4. Change the `RASOI_SERVER_URL` variable on GitHub to `https://rasoi.example.com` and run the **android** workflow again.

## Where things are

| What | Where |
|---|---|
| App code | `/var/www/rasoi` |
| Settings (database password, keys) | `/var/www/rasoi/.env`, readable only by the `rasoi` user |
| App logs | `/var/www/rasoi/storage/logs/` |
| Queue worker | `supervisorctl status rasoi-horizon` |
| Web server config | `/etc/nginx/sites-available/rasoi` |
| Scheduler | `/etc/cron.d/rasoi` |

Back up the database with `sudo -u postgres pg_dump rasoi > rasoi-$(date +%F).sql`. Automatic backups come in M10.
