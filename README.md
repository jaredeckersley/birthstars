# Birthday Stars · Hōkū Hānau

Tell Birthday Stars when you were born, and it finds a star whose distance in light years matches your age. The light we see from that star today left it around the day you were born, and it has taken your whole life to reach Earth.

This version is a rewrite for **Journey Through the Universe**, Hawaiʻi Island. It's built for kids at a booth kiosk and for volunteers in classrooms.

> The Hawaiian subtitle *Hōkū Hānau* still **needs review by a native speaker**. You can change it in one place: `App::SUBTITLE_HAW` in `src/App.php`. Set it to `''` to hide it.

## Features
- **Animated starfield.** A big, touch-friendly birthday picker: a year keypad, then month tiles, then day tiles. A drop-down fallback is there too, and the form works with no JavaScript at all.
- **Dramatic reveal** of *your birthday star*. A kid-level explanation of light travel time follows, then fun facts:
  - how bright the star is
  - its constellation and what the name means
  - whether it's up tonight from Hawaiʻi, or the best month to see it
  - what kind of star it is
- **Interactive sky chart** drawn on a `<canvas>` (`public/assets/js/skychart.js`). You can drag, pinch, scroll, use the +/− buttons or the arrow keys. The birthday star's constellation is highlighted.
- **Fully offline.** No CDNs, web fonts or external APIs. All JS, CSS, logos and chart data are served from `public/assets/`. The CSP header is `default-src 'self'`.
- **Kiosk mode** at `/kiosk`:
  - The first tap goes full screen. There's also a "Full screen" button.
  - After 90 s idle it shows a 10-second countdown, then resets to the start.
  - The right-click menu is disabled.
- **Print-friendly** result page with a white-background chart.
- **Accessible:** high contrast, large text, visible focus rings, skip link, keyboard-operable picker and chart, and it honours `prefers-reduced-motion`.
- **Secure by default:**
  - Twig auto-escaping and strict input validation.
  - PDO prepared statements.
  - Read-only SQLite.
  - No secrets anywhere, and no personal data is stored.

## Tech
- PHP ≥ 8.2 (tested on 8.4), Slim 4, Twig 3, PDO SQLite, PHPUnit 11.
- Composer only. No JS build step: plain ES5 JavaScript and one CSS file.

```
bin/import-stars.php   # one command to refresh star data
data/stars.sqlite      # generated star DB (committed, ~0.6 MB)
public/                # web root: index.php, .htaccess, assets/
src/                   # App, AgeCalculator, BirthdayStarFinder, StarRepository, StarNames, SkyFacts, HygImporter
templates/             # Twig templates
tests/                 # PHPUnit tests (+ small HYG fixture)
```

## Run locally
```bash
composer install
php -S 127.0.0.1:8000 -t public public/index.php
# open http://127.0.0.1:8000/ (or /kiosk)
composer test
```

## Deploy (plain Apache or Nginx + PHP-FPM host)
1. **Requirements.** PHP 8.2+ with `pdo_sqlite` (plus `mbstring` and `ctype`, which are usually on by default), and Composer.
2. **Copy the code** to the server, e.g. `/var/www/birthstars`:
   ```bash
   git clone https://github.com/jaredeckersley/birthstars.git /var/www/birthstars
   cd /var/www/birthstars
   composer install --no-dev --optimize-autoloader
   ```
   No import step is needed, because `data/stars.sqlite` is committed. The web server only needs **read** access to `data/`.
3. **Point the web root at `public/`**, never at the repo root.
   - **Apache:** set `DocumentRoot /var/www/birthstars/public` with `AllowOverride All` and `mod_rewrite` enabled. The included `public/.htaccess` handles routing.
   - **Nginx:**
     ```nginx
     server {
         root /var/www/birthstars/public;
         index index.php;
         location / { try_files $uri /index.php$is_args$args; }
         location ~ \.php$ {
             include fastcgi_params;
             fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
             fastcgi_pass unix:/run/php/php8.3-fpm.sock;
         }
     }
     ```
4. **Hosting in a subfolder** (e.g. `example.org/birthstars/`): set the environment variable `APP_BASE_PATH=/birthstars`. For Apache, use `SetEnv APP_BASE_PATH /birthstars`; for Nginx, use `fastcgi_param APP_BASE_PATH /birthstars;`.
5. **Debug output** is off. Set `APP_DEBUG=1` only while troubleshooting.

### Booth kiosk with no internet
Run it on a laptop with `php -S 127.0.0.1:8000 -t public public/index.php` and open Chrome in kiosk mode: `google-chrome --kiosk http://127.0.0.1:8000/kiosk`. Everything works offline.

## Refreshing the star data
```bash
php bin/import-stars.php              # downloads the pinned HYG release, checks its SHA-256, rebuilds
php bin/import-stars.php hygdata.csv  # or import a local copy
```
This rebuilds `data/stars.sqlite` and `public/assets/data/sky-stars.json` in one step. The database is replaced atomically, so a failed import leaves the old data in place. Commit both files afterwards.

**Source (pinned):** [HYG Database](https://github.com/astronexus/HYG-Database) **v4.1**, file `hyg/CURRENT/hygdata_v41.csv`, at commit `e4fd9c95381c6b5f34e465259cb4d2e1a7ff2e09` (2025-02-13). The SHA-256 is `d9f69fd8…be4ebd`. HYG combines the Hipparcos (new reduction), Yale Bright Star and Gliese catalogs, and is licensed **CC BY-SA 4.0** by David Nash / astronexus.

To move to a newer release, update `HYG_COMMIT`, `HYG_FILE` and `HYG_SHA256` in `bin/import-stars.php`.

**What gets imported:**
- **Birthday-star candidates:** every star with a known distance of ≤ 135 light years and a magnitude, excluding the Sun. That's 6,317 stars, 623 of which have a proper name, Bayer letter or Flamsteed number.
- **The match:** the **named** star whose distance is closest to your age. Every age from about 5 to 120 gets a star within about 2 ly; a test checks this. Children under about 4 get Alpha Centauri, with a friendly "come back when you're older" note.
- **Sky chart:** 2,865 stars brighter than magnitude 5.5.

## Credits & licences
- **Code:** MIT.
- **Star data:** HYG Database v4.1, CC BY-SA 4.0 (attribution is shown on the site).
- **Constellation lines:** `public/assets/data/constellations.lines.json` comes from [d3-celestial](https://github.com/ofrohn/d3-celestial) by Olaf Frohn, BSD 3-Clause. See `public/assets/data/LICENSE-d3-celestial.txt`.
- **Logos** in `public/assets/img/logos/` were downloaded from the official NOIRLab logo archive, <https://noirlab.edu/public/products/logos/>. They are stored locally and resized for the web:
  | File | Source |
  |---|---|
  | `jttu-hawaii-island.png` | logo020, "Journey through the Universe Hawaiʻi Island" (transparent PNG, resized to 480 px) |
  | `gemini-white.svg` | logo176, "International Gemini Observatory White" (SVG) |
  | `nsf-noirlab-horizontal-color.png` | logo001, "U.S. NSF NOIRLab Horizontal" (transparent PNG, resized to 700 px). Kept in colour on a white pill, per the NOIRLab Visual Identity guide |
  | `noirlab-white-horizontal.svg` | logo075, "NOIRLab White Horizontal" (SVG, spare for dark backgrounds) |

  **Usage terms:** NOIRLab's [usage page](https://noirlab.edu/public/copyright/) says images are CC BY 4.0, **but logos are excluded.** The NOIRLab logo "may not be used or reproduced without prior and individual written consent from NOIRLab". The NSF logo also has its own [guidelines](https://www.nsf.gov/policies/logos.jsp). The project owner reports having permission. **Keep a copy of that written consent from NOIRLab Communications, Education & Engagement on file.** The credit line is "Logos courtesy of U.S. NSF & NOIRLab". The logos are *not* covered by this repo's MIT licence.
- **Original idea:** *Birthday Stars* by the Joint Astronomy Centre, Hilo (2004). This rewrite shares no code or assets with it.
