# Konttiravintola Waves — Custom CMS

Flat-file JSON CMS for the Waves restaurant website. No database, no external dependencies.

## Tech Stack

- **Backend**: PHP 8.3+
- **Storage**: JSON files (`data/`)
- **CSS**: Tailwind CSS v4 (pre-compiled to `assets/css/index.css`)
- **Frontend**: Vanilla JS
- **Admin**: Simple password-protected PHP forms

## Local Development

```bash
docker compose up -d
open http://localhost:8080
```

**Admin**: http://localhost:8080/admin/
- First login: set any password (stored as bcrypt hash)

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
├── data/                 # JSON data (protected by .htaccess)
│   ├── settings.json
│   ├── notices.json
│   ├── menu.json
│   ├── lunch.json
│   ├── events.json
│   └── admin.json        # Password hash
└── assets/
    ├── css/index.css     # Compiled Tailwind
    ├── js/app.js         # Mobile nav, notice dismiss
    └── files/            # Uploads (logo, images)
```

## Data Architecture

All content is stored in JSON files under `data/`:

| File | Content |
|------|---------|
| `settings.json` | Contact info, opening hours, social links, SEO |
| `notices.json` | Temporary banners ("closed today", "private event") |
| `menu.json` | Categories and menu items with prices, dietary tags |
| `lunch.json` | Weekly lunch items (Mon–Fri) |
| `events.json` | Events with dates, descriptions |

## Bilingual URLs

| Finnish | English |
|---------|---------|
| `/` | `/en/` |
| `/menu` | `/en/menu` |
| `/lounas` | `/en/lunch` |
| `/tapahtumat` | `/en/events` |
| `/yhteystiedot` | `/en/contact` |

## Admin Features

- **Settings**: Edit contact details, social links, hero text, intro, SEO meta
- **Notices**: Create date-ranged temporary banners with dismiss option
- **Hours**: Set weekly schedule + exceptions (holidays, special events)
- **Menu**: Manage categories and items with prices, descriptions, dietary tags, visibility toggle
- **Lunch**: Weekly lunch list (Mon–Fri)
- **Events**: Event listings with date, time, descriptions

## Security

- Admin password stored as bcrypt hash in `data/admin.json`
- CSRF tokens on all admin forms
- `data/` directory blocked by `.htaccess` (Deny from all)
- JSON files written with `LOCK_EX` to prevent corruption

## Deployment (Hetzner Ubuntu + Nginx)

```bash
# 1. On server: install PHP-FPM and Nginx
sudo apt update
sudo apt install -y php8.3-fpm php8.3-gd php8.3-mbstring nginx certbot

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

## Notes

- The `waves.svg` logo should be placed in `assets/files/waves.svg`
- All uploaded images go to `assets/files/`
- The site auto-creates default data on first load if JSON files are missing
