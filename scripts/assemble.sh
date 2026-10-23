#!/usr/bin/env bash
#
# Lays the built site out the way LWS serves it (see README, "Hosting layout"):
#
#   dist/                          hosting root = openrevise.com and the
#                                  revision app (+ install.php, media/)
#   dist/app.openrevise.com/       old address of the app: redirects only
#   dist/api.openrevise.com/       the API
#   dist/admin.openrevise.com/     the admin panel
#   dist/openrevise-core/          the shared Laravel code (never served)
#
# Run after "composer install --no-dev" and "npm run build". Nothing private is
# copied: no .env, no storage folder (lesson files), no uploaded diagrams.
#
#   OPENREVISE_DOMAIN=openrevise.com scripts/assemble.sh dist

set -euo pipefail

OUT="${1:-dist}"
DOMAIN="${OPENREVISE_DOMAIN:-openrevise.com}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

[ -f public/build/manifest.json ] || { echo "Run npm run build first." >&2; exit 1; }
[ -d vendor ] || { echo "Run composer install first." >&2; exit 1; }

rm -rf "$OUT"
mkdir -p "$OUT/openrevise-core"

# The shared code. Git metadata inside packages is never needed on the server.
for item in app bootstrap config database routes artisan composer.json composer.lock; do
    cp -a "$item" "$OUT/openrevise-core/"
done
tar -C . --exclude='.git' --exclude='.github' -cf - vendor | tar -C "$OUT/openrevise-core" -xf -
mkdir -p "$OUT/openrevise-core/resources"
cp -a resources/views resources/docs "$OUT/openrevise-core/resources/"
[ -d resources/lang ] && cp -a resources/lang "$OUT/openrevise-core/resources/"

# Laravel reads the Vite manifest from the core; the files themselves are
# copied into each site below.
mkdir -p "$OUT/openrevise-core/public/build"
cp public/build/manifest.json "$OUT/openrevise-core/public/build/"
cp sites/core.htaccess "$OUT/openrevise-core/.htaccess"

# Never ship local databases, caches built on this machine or secrets.
rm -f "$OUT"/openrevise-core/database/*.sqlite
find "$OUT/openrevise-core/bootstrap/cache" -type f ! -name 'packages.php' ! -name 'services.php' ! -name '.gitignore' -delete
rm -f "$OUT/openrevise-core/.env"

# openrevise.com and the revision app, served from the hosting root.
cp -a sites/main/. "$OUT/"
cp -a public/build "$OUT/build"

# Uploaded diagrams live on the server only; ship just the folder's rules.
find "$OUT/media" -type f ! -name '.htaccess' -delete

# The two subdomains, each in a folder named after it.
for site in api admin; do
    dir="$OUT/$site.$DOMAIN"
    mkdir -p "$dir"
    cp -a "sites/$site/." "$dir/"
    cp -a public/build "$dir/build"
done

# The app's old subdomain: just the redirect to openrevise.com.
mkdir -p "$OUT/app.$DOMAIN"
cp -a sites/app/. "$OUT/app.$DOMAIN/"

# Filament's own scripts, styles and fonts for the admin panel.
for asset in js css fonts; do
    [ -d "public/$asset" ] && cp -a "public/$asset" "$OUT/admin.$DOMAIN/"
done

echo "Assembled $OUT for $DOMAIN: $(find "$OUT" -type f | wc -l) files."
