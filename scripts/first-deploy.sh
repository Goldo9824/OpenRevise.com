#!/usr/bin/env bash
#
# The first deploy, as one archive instead of thousands of FTP uploads.
#
# The FTP upload action keeps a record of what it sent in
# .ftp-deploy-sync-state.json on the server, and later deploys send only the
# files that changed since. Without that record it would send every file one
# by one, which takes hours. So when the record is missing:
#
#   1. the upload action runs as a dry run, which only writes the record into
#      dist/ (exactly as it would after a real upload),
#   2. this script zips the files listed in the record, uploads the archive and
#      scripts/unpack.php, and calls the unpacker over HTTPS until it is done:
#      it deletes everything already on the server (except the .env file,
#      lesson files, uploaded diagrams and .well-known) and puts the new files
#      in place,
#   3. it uploads the record last, so the next deploy is a normal one. If any
#      step fails, the record is not there and the next deploy starts over.
#
#   scripts/first-deploy.sh check          prints first=true or first=false
#   scripts/first-deploy.sh upload dist    the steps above, after the dry run
#
# Environment: FTP_SERV, FTP_USER, FTP_PASSWORD and OPENREVISE_DOMAIN.
# SITE_URL defaults to https://$OPENREVISE_DOMAIN.

set -euo pipefail

STATE=.ftp-deploy-sync-state.json
DOMAIN="${OPENREVISE_DOMAIN:-openrevise.com}"
SITE_URL="${SITE_URL:-https://$DOMAIN}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

HOST="${FTP_SERV#*://}"
HOST="${HOST%/}"
# Explicit FTP over TLS, like the upload action.
FTP=(curl -sS --ssl-reqd --retry 3 -u "$FTP_USER:$FTP_PASSWORD")
release=""
work=""
finished=0

fail() {
    echo "::error::$1" >&2
    exit 1
}

connect() {
    local rc=0 err
    err="$("${FTP[@]}" --list-only -o /dev/null "ftp://$HOST/" 2>&1)" || rc=$?
    if [ "$rc" -eq 60 ]; then
        # The upload action does not check the certificate either.
        echo "::warning::The FTP server's certificate could not be verified for $HOST; carrying on without checking it, like the upload action." >&2
        FTP+=(--insecure)
    elif [ "$rc" -ne 0 ]; then
        fail "Could not connect to the FTP server: $err"
    fi
}

# On failure, takes the unpacker and the archive off the server. The site is
# left as it was (or half replaced, if unpacking had started); the record is
# not saved, so the next deploy starts over.
cleanup() {
    rm -rf "$work"
    if [ "$finished" != 1 ]; then
        "${FTP[@]}" -Q "*DELE _unpack-$release.php" -Q "*DELE .openrevise-release-$release.zip" \
            --list-only -o /dev/null "ftp://$HOST/" 2>/dev/null || true
    fi
}

check() {
    connect
    local rc=0
    "${FTP[@]}" -o /dev/null "ftp://$HOST/$STATE" 2>/dev/null || rc=$?
    case "$rc" in
        0) echo "Found $STATE on the server: uploading changed files only." >&2
           echo "first=false" ;;
        78) echo "No $STATE on the server: this is the first deploy." >&2
            echo "first=true" ;;
        # Never take a connection problem for a first deploy: that would wipe the server.
        *) fail "Could not check the server for $STATE (curl error $rc)." ;;
    esac
}

upload() {
    local dist="$1"
    [ -f "$dist/$STATE" ] || fail "$dist/$STATE is missing: run the upload action as a dry run first."
    connect

    local token hash
    release="$(date -u +%Y%m%d%H%M%S)-$(openssl rand -hex 8)"
    token="$(openssl rand -hex 32)"
    hash="$(printf %s "$token" | sha256sum | cut -d' ' -f1)"
    work="$(mktemp -d)"
    trap cleanup EXIT

    # Exactly the files and folders in the record, so the next deploy finds
    # the server as the record describes it. The record itself goes up last.
    jq -r --arg state "$STATE" '.data[].name | select(. != $state)' "$dist/$STATE" > "$work/names"
    (cd "$dist" && zip -q -X "$work/release.zip" -@ < "$work/names")
    echo "Archive: $(wc -l < "$work/names") files and folders, $(du -h "$work/release.zip" | cut -f1)."

    sed -e "s/%%TOKEN_HASH%%/$hash/" -e "s/%%RELEASE%%/$release/" \
        "$ROOT/scripts/unpack.php" > "$work/unpack.php"

    echo "Uploading the archive..."
    "${FTP[@]}" -T "$work/release.zip" "ftp://$HOST/.openrevise-release-$release.zip"
    "${FTP[@]}" -T "$work/unpack.php" "ftp://$HOST/_unpack-$release.php"

    echo "Unpacking on the server..."
    local url="$SITE_URL/_unpack-$release.php" calls=0 errors=0 rc out code body
    while :; do
        calls=$((calls + 1))
        [ "$calls" -le 300 ] || fail "The unpacker had not finished after 300 calls."

        rc=0
        out="$(curl -sS --max-time 120 -w '\n%{http_code}' --data-urlencode "token=$token" "$url")" || rc=$?
        code="${out##*$'\n'}"
        body="${out%$'\n'*}"
        body="${body%$'\n'}"

        if [ "$rc" -eq 0 ] && [ "$code" = 200 ]; then
            errors=0
            echo "  ${body%%$'\n'*}"
            if [ "${body%%$'\n'*}" = done ]; then
                [ "$body" = done ] || echo "::warning::${body#*$'\n'}"
                break
            fi
            continue
        fi

        case "$rc" in
            6) fail "Could not find $SITE_URL. Does $DOMAIN point to the LWS hosting yet?" ;;
            35|51|60) fail "Could not make a secure connection to $SITE_URL. Is SSL turned on for $DOMAIN in the LWS panel?" ;;
        esac
        case "$code" in
            404) fail "$url was not found. Is the FTP account's home folder the root of $DOMAIN?" ;;
            403) fail "$url refused the call (HTTP 403)." ;;
        esac
        if [ "${body#error:}" != "$body" ]; then
            fail "${body#error: }"
        fi

        # Busy, cut off or a passing server error: the unpacker picks up where it was.
        errors=$((errors + 1))
        [ "$errors" -le 5 ] || fail "The unpacker kept failing (HTTP $code, curl error $rc)."
        echo "  no answer yet (HTTP $code, curl error $rc), trying again"
        sleep 10
    done

    finished=1
    echo "Saving the deploy record..."
    "${FTP[@]}" -T "$dist/$STATE" "ftp://$HOST/$STATE"
    echo "First deploy complete. Later deploys upload changed files only."
}

case "${1:-}" in
    check) check ;;
    upload) upload "${2:-dist}" ;;
    *) echo "Usage: $0 check | upload <dist>" >&2; exit 1 ;;
esac
