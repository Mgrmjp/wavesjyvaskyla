# Konttiravintola Waves — Custom CMS

Custom PHP CMS for the Waves restaurant website. Content and admin users are stored in SQLite; contact submissions and revision history remain in JSON files.

## Tech Stack

- **Backend**: PHP 8.3+
- **Storage**: SQLite for content and admin users, JSON for contact submissions and revisions
- **CSS**: Tailwind CSS v4 (pre-compiled to `assets/css/index.css`)
- **Frontend**: Vanilla JS
- **Admin**: Simple password-protected PHP forms

## Local Development

```bash
docker compose up -d
open http://localhost:8080
```

**Admin**: http://localhost:8080/admin/

## File Structure

```
wavesjkl/
├── index.php              # Router
├── .htaccess             # URL rewriting
├── includes/
│   ├── functions.php     # Data layer, helpers, auth
│   ├── header.php        # Site header + nav
│   ├── footer.php        # Site footer
│   └── opening-hours.php # Hours table component
├── templates/            # Frontend pages
│   ├── home.php
│   ├── menu.php
│   ├── lunch.php
│   ├── events.php
│   ├── contact.php
│   └── 404.php
├── admin/                # Admin panel
│   ├── login.php
│   ├── logout.php
│   ├── index.php         # Dashboard
│   ├── settings.php      # Contact, SEO, socials
│   ├── notices.php       # Temporary notices
│   ├── hours.php         # Opening hours + exceptions
│   ├── menu.php          # Menu categories & items
│   ├── lunch.php         # Weekly lunch
│   └── events.php        # Events
├── data/                 # Remaining JSON files (protected by .htaccess)
│   ├── messages.json
│   └── revisions.json
├── scripts/
│   └── migrate-json-to-sqlite.php
└── assets/
    ├── css/index.css     # Compiled Tailwind
    ├── js/app.js         # Mobile nav, notice dismiss
    └── files/            # Uploads (logo, images)
```

## Data Architecture

Primary content and admin users are stored in SQLite. The default database path is `data/waves.sqlite` under the repo root, or override it with `APP_DB_PATH`.

Runtime data under `data/` is intentionally not tracked in git. The app creates the SQLite database, revision log, and contact-message storage on demand.

Remaining JSON files under `data/`:

| File             | Content                                   |
| ---------------- | ----------------------------------------- |
| `messages.json`  | Contact form submissions                  |
| `revisions.json` | Revision history for selected admin edits |

## Bilingual URLs

| Finnish         | English       |
| --------------- | ------------- |
| `/`             | `/en/`        |
| `/menu`         | `/en/menu`    |
| `/lounas`       | `/en/lunch`   |
| `/tapahtumat`   | `/en/events`  |
| `/yhteystiedot` | `/en/contact` |

## Admin Features

- **Settings**: Edit contact details, social links, hero text, intro, SEO meta
- **Notices**: Create date-ranged temporary banners with dismiss option
- **Hours**: Set weekly schedule + exceptions (holidays, special events)
- **Menu**: Manage categories and items with prices, descriptions, dietary tags, visibility toggle
- **Lunch**: Weekly lunch list (Mon–Fri)
- **Events**: Event listings with date, time, descriptions

## Storage Migration

Enable the `pdo_sqlite` PHP extension in the target runtime, then run:

```bash
php scripts/migrate-json-to-sqlite.php
```

This imports existing JSON-backed content and admin users into SQLite.

## Google Hours Sync

Opening hours can be synced from Google Places instead of copying hours manually from Google Search.

1. Create a Google Places API key with access to Places API (New).
2. Verify the parsed hours in dry-run mode:

```bash
GOOGLE_PLACES_API_KEY='your-api-key' php scripts/sync-google-hours.php --query='waves jkl'
```

3. Apply the update:

```bash
GOOGLE_PLACES_API_KEY='your-api-key' php scripts/sync-google-hours.php --apply --query='waves jkl'
```

For production automation, use `deploy/google-hours-sync.cron.example` as the starting point for `/etc/cron.d/waves-google-hours`.

## First Admin User

Create the first admin user from the command line:

```bash
php scripts/create-admin-user.php admin your-password
```

## Deployment (Hetzner Ubuntu + Nginx)

```bash
# 1. On server: install PHP-FPM and Nginx
sudo apt update
sudo apt install -y php8.3-fpm php8.3-gd php8.3-mbstring php8.3-sqlite3 nginx certbot

# 2. Deploy files (from local)
./deploy/deploy.sh

# 3. On server: fix permissions
sudo chown -R www-data:www-data /var/www/wavesjyvaskyla
sudo chmod -R 775 /var/www/wavesjyvaskyla/data

# 4. SSL
certbot --nginx -d wavesjyvaskyla.fi
```

## CSS Development

If you modify `src/css/main.css`, rebuild with:

```bash
npm install  # first time only
npm run css:build
```

(Note: `src/css/` and `package.json` are for dev only, not deployed.)

## Screenshot Capture

To capture desktop and mobile screenshots for the public site and admin views:

1. Start the site locally.

```bash
php -d session.save_path=/tmp -S 127.0.0.1:8080
```

2. Install Playwright once for this repo.

```bash
npm install -D playwright
npx playwright install chromium
```

3. Run the screenshot script.

```bash
ADMIN_PASSWORD='your-admin-password' npm run screenshots
```

By default, screenshots are written to `screenshots/` and include:

- Public FI: `/`, `/menu`, `/lounas`, `/tapahtumat`, `/yhteystiedot`, `/kuvat`
- Public EN: `/en/`, `/en/menu`, `/en/lunch`, `/en/events`, `/en/contact`, `/en/gallery`
- Admin: `/admin/`, `/admin/settings.php`, `/admin/notices.php`, `/admin/hours.php`, `/admin/menu.php`, `/admin/lunch.php`, `/admin/events.php`, `/admin/gallery.php`

Useful environment variables:

- `SCREENSHOT_BASE_URL` to target a different local URL
- `SCREENSHOT_OUTPUT_DIR` to change the output folder
- `ADMIN_PASSWORD` to enable admin screenshots; if omitted, only public views are captured

## Notes

- The `waves.svg` logo should be placed in `assets/files/waves.svg`
- All uploaded images go to `assets/files/`
- The site auto-creates default data on first load if JSON files are missing
