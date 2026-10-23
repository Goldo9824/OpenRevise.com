# OpenRevise

Free GCSE revision with no sign-up: notes, exam questions and mock exams, organised by exam board (AQA, Edexcel, OCR).

One Laravel codebase serves three sites from one LWS hosting space:

| Site | What it is | Who uses it | Database |
| --- | --- | --- | --- |
| `openrevise.com` | Landing, About, Privacy, Terms, Contact, and the revision app: notes, exam questions, mock exams | Everyone; students, no login | Content |
| `api.openrevise.com` | Lets Claude create and list content | Claude, with an API key | Content + Admin |
| `admin.openrevise.com` | Analytics, content, API keys, logs, settings | The owner (password, then optional passkey) | Admin + Analytics + Content |

The app used to live on `app.openrevise.com`; that address now only sends old links to the same page on `openrevise.com` (301).

Nothing is hard-coded: subjects, topics, chapters, lessons, questions and mock exams are added in the admin panel or through the API.

**Stack:** Laravel 13 (PHP 8.3+), Filament 5, Tailwind CSS 4, Alpine.js, Vite, KaTeX, MySQL. Built on GitHub, uploaded to LWS over FTP.

---

## How the code is organised

```
app/
  Analytics/          cookie-free page views, daily roll-ups, dashboard reports
  Content/            lesson text parser, lesson files, diagrams, SVG cleaning
  Filament/           the admin panel (resources, dashboard widgets, settings)
  Http/Controllers/
    Api/              api.openrevise.com (v1)
    App/              the revision app (on openrevise.com)
    Main/             openrevise.com's presentation pages
    Admin/            the passkey sign-in step
  Models/
    Content/          Content DB: boards, subjects, topics, chapters, lessons...
    Admin/            Admin DB: admin, passkeys, API keys, logs, settings
    Analytics/        Analytics DB: page views, daily totals
routes/
  main.php app.php api.php admin.php     picked by hostname; main.php and app.php both serve openrevise.com
sites/
  main/  api/  admin/                    each site's public folder (index.php, .htaccess)
  app/                                   just the .htaccess redirecting app.openrevise.com
resources/
  views/              Blade templates (app/, main/, filament/...)
  docs/api.md         the API reference (served at api.openrevise.com)
scripts/assemble.sh   lays the build out the way LWS serves it
```

### Three databases

| Database | Holds |
| --- | --- |
| Content | Boards and paper templates, subjects, topics, chapters, lesson details and tags, exam questions, mock exams, diagram details, old addresses (redirects) |
| Admin | Admin account, passkeys, API keys (hashed), API request log, sign-ins, errors, change history, mistake reports, settings. Also the migrations table |
| Analytics | Raw page views (90 days), daily totals, daily salts |

Lesson bodies are text files in `openrevise-core/storage/app/lessons/{id}.txt`, and diagrams are files in the `media/` folder at the hosting root (served at `openrevise.com/media/`), so neither counts towards the 1 GB database limit. There are no foreign keys between databases: rows only store each other's IDs.

### Addresses

```
/revise/                                             pick a board and a subject
/gcse/                                               every subject
/gcse/biology/                                       subject
/gcse/biology/aqa/                                   board page (lists only AQA lessons)
/gcse/biology/cell-biology/                          topic
/gcse/biology/cell-biology/cell-structure/           chapter
/gcse/biology/cell-biology/cell-structure/<lesson>/  lesson (one address for every board)
.../<lesson>/exam-questions/                         the lesson's exam questions
/gcse/biology/mock-exams/aqa-paper-1/                mock exam
```

Slugs are made from titles. Board names and `mock-exams` are reserved, so no topic can take them. A lesson for only some of a subject's boards gets their names at the end of its slug (`required-practical-microscopy-aqa`). Changing a slug, or moving a page, records the old address; it answers with a 301 for the page and everything under it. Every page has a canonical link, breadcrumbs and structured data (BreadcrumbList, LearningResource, Quiz), and `sitemap.xml` is built from the content.

### Lesson text format

Lessons are plain text with a few tags, never HTML. The full reference, written for Claude, is in [`resources/docs/api.md`](resources/docs/api.md).

```
## Cell structure

[KEYTERM] Nucleus: contains the cell's genetic material.

[TABLE] Parts of a cell
Part | Function
Nucleus | Controls the cell
[/TABLE]

[IMAGE] animal-cell | Labelled animal cell
[FORMULA] magnification = image size / real size
[TIP] Always show your working in calculations.
```

Formulas can be LaTeX or plain text (`a / b` with spaces becomes a fraction). Unknown tags, unclosed tables and missing diagrams are rejected with their line number, both in the API and the admin panel.

---

## Local development

Requirements: PHP 8.3+ with `intl`, `gd`, `pdo_sqlite`; Composer; Node 22.

```bash
composer install
npm install
cp .env.example .env        # then set DB_DRIVER=sqlite and the local domain below
php artisan key:generate
php artisan openrevise:fresh --seed     # three SQLite databases, launch boards and subjects, demo lessons
php artisan openrevise:admin --email=you@example.com --name=You
php artisan filament:assets
npm run build
php -S 127.0.0.1:8000 dev-server.php
```

Local `.env` values:

```
OPENREVISE_DOMAIN=openrevise.localhost
OPENREVISE_SCHEME=http
OPENREVISE_PORT=:8000
DB_DRIVER=sqlite
```

Then open <http://openrevise.localhost:8000> (the app starts at <http://openrevise.localhost:8000/revise/>), <http://api.openrevise.localhost:8000> and <http://admin.openrevise.localhost:8000> (`*.localhost` always points at your own machine). `dev-server.php` picks the site folder from the hostname, the way LWS does.

Tests: `vendor/bin/phpunit` (three in-memory SQLite databases; no setup needed).

---

## Hosting layout on LWS

LWS serves each subdomain from a folder named after it, and the main domain from the root of the hosting space. `scripts/assemble.sh` builds exactly this:

| Folder | Holds |
| --- | --- |
| Hosting root | `index.php`, `.htaccess`, `install.php`, built CSS/JS for openrevise.com and the app, `media/` (uploaded diagrams) |
| `api.openrevise.com/` | `index.php`, `.htaccess` |
| `admin.openrevise.com/` | `index.php`, `.htaccess`, built CSS/JS, Filament assets |
| `app.openrevise.com/` | only a `.htaccess` that redirects old links to `openrevise.com` |
| `openrevise-core/` | the shared Laravel code, `vendor`, `storage` (lesson files), `.env` |

Each `index.php` finds `openrevise-core` next to it and Laravel picks the routes from the hostname. `openrevise-core` is never served: it has its own `Require all denied` rule and the root `.htaccess` refuses the path. Each subdomain's `.htaccess` also refuses to answer on the main domain (so `openrevise.com/admin.openrevise.com/` gives a 404), and diagrams in `media/` can never run as scripts.

LWS maps each subdomain onto its folder inside the hosting root, so the subdomains' `.htaccess` files set `RewriteBase /`. Without it Apache sends every page but the home page round in a loop and answers with a 500.

**Test this after the first deploy:** <https://openrevise.com/openrevise-core/.env> and <https://openrevise.com/openrevise-core/composer.json> must both be refused (403 or 404).

---

## Deploying

Every push to `main` runs [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml): it runs the tests, installs PHP packages without dev tools, builds the CSS and JavaScript, assembles the folders and uploads them over FTP. Only changed files go up. The `.env` file, the `storage` folder (lesson files) and uploaded diagrams are never in the upload, so a deploy cannot replace or delete them.

The upload keeps a record of what it sent in `.ftp-deploy-sync-state.json` at the hosting root. When that record is missing (the first deploy), sending thousands of files one by one would take hours, so [`scripts/first-deploy.sh`](scripts/first-deploy.sh) does it differently:

1. It uploads the whole site as one archive, plus a one-time unpacker ([`scripts/unpack.php`](scripts/unpack.php)).
2. It calls the unpacker over HTTPS with a one-time token. The unpacker extracts the archive into a hidden folder, deletes everything already in the hosting space, moves the new files into place, then deletes the archive and itself. It never deletes `openrevise-core/.env`, `openrevise-core/storage` (lesson files), `media` (diagrams) or `.well-known` (SSL certificates).
3. It saves the record last, so the next deploy uploads changed files only. If anything fails, the record is not saved and the next deploy starts over.

To force a full deploy later, delete `.ftp-deploy-sync-state.json` in the LWS file manager and run the workflow again.

### One-time setup

1. **Subdomains.** In the LWS panel, *Gestion du domaine › Sous domaines*, create `api` and `admin`, plus `app` so old links keep working (it only redirects). LWS creates a folder for each. (Use only the Sous domaines tool, not Multi-Domains.) Turn on SSL for the domain and the three subdomains.
2. **PHP.** Choose PHP 8.3 or newer for the hosting, and check that `intl`, `gd` and `pdo_mysql` are enabled.
3. **Databases.** Create three MySQL databases (content, admin, analytics), each with its own user. Keep the host, names, users and passwords for step 7.
4. **FTP account for GitHub.** Create a dedicated FTP account whose home is the hosting root, so it can be revoked without touching yours.
5. **GitHub secrets.** In the repository, *Settings › Secrets and variables › Actions*, add `FTP_SERV` (the LWS FTP server address), `ID` (the FTP username) and `PASSWORD`.
6. **First deploy.** Push to `main` (or run the workflow by hand from the Actions tab). It goes up as one archive and replaces everything in the hosting space (see above), so SSL must already be on for the domain. The workflow uses explicit FTP over TLS; if LWS refuses it, change `protocol: ftps` to `ftp` in the workflow and remove `--ssl-reqd` in `scripts/first-deploy.sh`.
7. **Installer.** Open the LWS file manager, open `openrevise-core/storage/install-key.txt` and copy the key. Then go to <https://openrevise.com/install.php> and follow the steps: install key, requirements check, site details, the three databases (each connection is tested), the admin account. It writes `openrevise-core/.env`, creates the tables, optionally adds the launch boards and subjects, creates the admin, and locks itself.
8. **Cron.** In the LWS panel, add a daily cron job: `php /path/to/openrevise-core/artisan openrevise:analytics-rollup`. Without it, the dashboard catches up by itself when you open it.
9. **Admin.** Sign in at <https://admin.openrevise.com>. In *Settings*, add a passkey (you get ten one-time recovery codes; keep them safe). In *API keys*, make a key for Claude and give it the API reference at <https://api.openrevise.com>.

### After a deploy that changes the database

Databases cannot be reached from GitHub, so run the migrations from the LWS web terminal:

```bash
cd openrevise-core && php artisan migrate --force
```

Importing the SQL in phpMyAdmin is the fallback.

### Re-running the installer

Delete `openrevise-core/storage/install.lock` and reload `install.php`. It starts from the current settings and never drops tables. Deploys never upload the lock file, so they never reopen the installer.

### If you are locked out of the admin

From the LWS web terminal, in `openrevise-core`:

```bash
php artisan openrevise:admin                    # set a new password
php artisan openrevise:admin --remove-passkeys  # if the passkey and the recovery codes are lost too
```

---

## The admin panel

- **Analytics:** visitors, page views, live visitors, time on page, bounce rate, top lessons, sources, subjects, boards, countries, browsers, devices and the path from home to lesson to exam questions. Raw views are kept for 90 days, then only daily totals.
- **Content:** subjects (with each board's specification code), topics, chapters, lessons (text with tag checking, boards, Higher tier and Separate Science tags, draft/published/hidden, previews of drafts), exam questions, mock exams, boards and paper templates, diagrams, and mistakes reported by students.
- **API:** keys (shown once, stored hashed, read / write / publish rights, optional expiry, revocable) and the log of every request.
- **Logs:** sign-ins, errors and the change history of all content.
- **Settings:** account, password, passkeys, recovery codes, an announcement bar for the app, and the page-view counter switch.

## Privacy

No cookies on the public sites, no third-party scripts or fonts. Page views store the page, the referring site, browser, device type, country (from the time zone) and time on page. IP addresses are never stored: visitors are counted with a hash that uses a salt deleted after two days. Students' answers, marks, saved lessons and course choices stay in their browser's local storage.

## Licence

All rights reserved by the owner of OpenRevise, unless stated otherwise.
