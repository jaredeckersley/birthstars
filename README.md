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
- PHP ≥ 8.0 (tested on 8.0.30 and 8.4), Slim 4, Twig 3, PDO SQLite, PHPUnit 9.6.
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

## Deploy

### Option A: dedicated (virtual) host, web root = `public/`
1. **Requirements.** PHP 8.0+ with `pdo_sqlite` (plus `mbstring` and `ctype`, which are usually on by default), and Composer.
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
             fastcgi_pass unix:/run/php/php8.0-fpm.sock;
         }
     }
     ```
4. **Subfolder of an existing site.** The base path (e.g. `/birthstars`) is detected automatically. Setting `APP_BASE_PATH=/birthstars` (via `SetEnv` or `fastcgi_param`) still works and overrides the detection.

5. **Debug output** is off. Set `APP_DEBUG=1` only while troubleshooting.

### Option B: subfolder inside an existing Apache web root (no vhost changes)
Use this when the server already serves `/var/www/html` and you want the app at `https://host/birthstars`. It needs PHP 8.0+, `mod_rewrite`, and `AllowOverride All` (or at least `FileInfo Options AuthConfig Limit`) for `/var/www/html`.
```bash
cd /var/www/html
git clone https://github.com/jaredeckersley/birthstars.git birthstars
cd birthstars
composer install --no-dev --optimize-autoloader
sudo a2enmod rewrite && sudo systemctl reload apache2   # if not already enabled
```
That's it: open `https://host/birthstars/` (and `/birthstars/kiosk`).
- The repo-root `.htaccess` sends every request into `public/`. Static files are served directly, and everything else goes to `public/index.php`.
- It returns **404** for `src/`, `vendor/`, `data/`, `bin/`, `tests/`, `templates/`, `.git`, dotfiles, `composer.*`, `README`, `LICENSE` and `phpunit.xml`.
- If `mod_rewrite` is missing, it denies everything rather than exposing files.
- The base path `/birthstars` is detected automatically, so you don't need `APP_BASE_PATH`.
- To update later: `git pull && composer install --no-dev --optimize-autoloader`.

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

## Telescope photos of every birthday star
`public/assets/survey/<hyg_id>.jpg` holds a real sky-survey photo of every possible birthday star. `public/assets/survey/manifest.json` records the survey, field of view, coordinates, source URL and credit for each one. The photos are bundled so the site works offline.

Each photo is a 600×600 colour cutout from the [CDS hips2fits](https://alasky.cds.unistra.fr/hips-image-services/hips2fits) service:
- **Survey:** Pan-STARRS DR1 colour (`CDS/P/PanSTARRS/DR1/color-z-zg-g`, taken from Haleakalā, Maui) wherever it covers the sky (Dec > −29°). DSS2 colour (`CDS/P/DSS2/color`) is used elsewhere, and also when a Pan-STARRS cutout comes back blank.
- **Position:** centred on the star after applying its HYG proper motion to the survey epoch.
- **Field of view:** 0.25°–1°, wider for brighter stars.
- **Compression:** recompressed to JPEG quality 80 if PHP's GD extension is available.

```bash
php bin/fetch-survey-images.php           # fetch missing images (resumable; ~1 request/s with retries)
php bin/fetch-survey-images.php --prune   # after a star-data refresh: also delete photos of stars no longer listed
php bin/fetch-survey-images.php --force   # re-download everything
```
If a photo is missing, the result page just says so.

**Credits:**
- Pan-STARRS1 Surveys (PS1), Institute for Astronomy, University of Hawaiʻi. The PS1 data archive asks users to acknowledge the PS1 Surveys and its funders. See <https://panstarrs.stsci.edu/>.
- Digitized Sky Survey, produced at STScI under US Government grant NAG W-2166, from photographic plates taken at Palomar Observatory (Caltech) and the UK Schmidt Telescope (AAO/ROE). The DSS may be reused for non-commercial and educational purposes with acknowledgement. See <https://archive.stsci.edu/dss/acknowledging.html>.
- Cutouts provided by CDS, Strasbourg (hips2fits / HiPS, Fernique et al. 2015, A&A 578, A114).

## Constellation names and Hawaiian star names on the chart
- **Constellation names.** All 88 constellations are labelled from `public/assets/data/constellations.names.json`. The label positions and size ranks come from d3-celestial `constellations.json` (BSD-3-Clause, Olaf Frohn). Minor constellations are hidden when zoomed out, and the birthday star's constellation is always shown, highlighted in gold.
- **Hawaiian star names.** These come from `public/assets/data/hawaiian-star-names.json` and appear on the result page and as chart labels.
  - Every name records its source: the Polynesian Voyaging Society pages at hokulea.com, which draw on Johnson & Mahelona, *Nā Inoa Hōkū*.
  - Uncertain names and asterisms were left out on purpose. The file's `note` field says which ones.
  - **The Hawaiian names, their ʻokina and kahakō, and the subtitle *Hōkū Hānau* must be reviewed by a native speaker or cultural practitioner before launch.**

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
