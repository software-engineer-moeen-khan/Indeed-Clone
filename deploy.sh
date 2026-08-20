#!/usr/bin/env bash
set -Eeuo pipefail

REPO_URL="https://github.com/software-engineer-moeen-khan/Indeed-Clone.git"
BRANCH="${DEPLOY_BRANCH:-main}"
TARGET_DIR="${DEPLOY_DIR:-$PWD}"

DB_DATABASE="${DB_DATABASE:-}"
DB_USERNAME="${DB_USERNAME:-}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"

log() {
    printf '\n\033[1;34m[deploy]\033[0m %s\n' "$*"
}

warn() {
    printf '\n\033[1;33m[deploy:warning]\033[0m %s\n' "$*" >&2
}

fail() {
    printf '\n\033[1;31m[deploy:error]\033[0m %s\n' "$*" >&2
    exit 1
}

cleanup_tmp=""
cleanup() {
    if [[ -n "$cleanup_tmp" && -d "$cleanup_tmp" ]]; then
        rm -rf "$cleanup_tmp"
    fi
}
trap cleanup EXIT
trap 'fail "Deployment stopped on line $LINENO."' ERR

[[ -n "$DB_DATABASE" ]] || fail "DB_DATABASE is required."
[[ -n "$DB_USERNAME" ]] || fail "DB_USERNAME is required."
[[ -n "$DB_PASSWORD" ]] || fail "DB_PASSWORD is required."

command -v git >/dev/null 2>&1 || fail "git is not available on this Hostinger SSH account."
command -v php >/dev/null 2>&1 || fail "php is not available on this Hostinger SSH account."
command -v composer >/dev/null 2>&1 || fail "composer is not available on this Hostinger SSH account."

PHP_VERSION_ID="$(php -r 'echo PHP_VERSION_ID;')"
(( PHP_VERSION_ID >= 80200 )) || fail "PHP 8.2+ is required; current version is $(php -r 'echo PHP_VERSION;')."

mkdir -p "$TARGET_DIR"
cd "$TARGET_DIR"

log "Deploying branch '$BRANCH' into $TARGET_DIR"

if [[ -d .git ]]; then
    log "Updating existing Git checkout"
    git fetch origin "$BRANCH"
    git checkout -f "$BRANCH"
    git reset --hard "origin/$BRANCH"
else
    unexpected="$(find . -mindepth 1 -maxdepth 1 \
        ! -name 'default.php' \
        ! -name '.well-known' \
        ! -name '.env' \
        -print -quit 2>/dev/null || true)"

    if [[ -n "$unexpected" ]]; then
        fail "This directory is not an Indeed-Clone Git checkout and contains existing files ($unexpected). Run from the intended empty Hostinger public_html directory."
    fi

    log "Installing repository into fresh Hostinger directory"
    rm -f default.php
    cleanup_tmp="$(mktemp -d)"
    git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$cleanup_tmp/repo"

    preserved_env=""
    if [[ -f .env ]]; then
        preserved_env="$cleanup_tmp/server.env"
        cp .env "$preserved_env"
    fi

    cp -a "$cleanup_tmp/repo/." ./

    if [[ -n "$preserved_env" && -f "$preserved_env" ]]; then
        cp "$preserved_env" .env
    fi
fi

FRESH_ENV=0
if [[ ! -f .env ]]; then
    cp .env.example .env
    FRESH_ENV=1
fi

set_env() {
    local key="$1"
    local value="$2"

    php -r '
        $file = ".env";
        $key = $argv[1];
        $value = $argv[2];
        $escaped = str_replace(["\\", "\""], ["\\\\", "\\\""], $value);
        $replacement = $key . "=\"" . $escaped . "\"";
        $contents = file_get_contents($file);
        $pattern = "/^" . preg_quote($key, "/") . "=.*$/m";
        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, $replacement, $contents, 1);
        } else {
            $contents = rtrim($contents) . PHP_EOL . $replacement . PHP_EOL;
        }
        file_put_contents($file, $contents);
    ' "$key" "$value"
}

if [[ -n "${APP_URL:-}" ]]; then
    RESOLVED_APP_URL="$APP_URL"
else
    HOSTINGER_DOMAIN="$(printf '%s' "$TARGET_DIR" | sed -n 's#^/home/[^/]*/domains/\([^/]*\)/public_html.*#\1#p')"
    if [[ -n "$HOSTINGER_DOMAIN" ]]; then
        RESOLVED_APP_URL="https://$HOSTINGER_DOMAIN"
    else
        RESOLVED_APP_URL="http://localhost"
    fi
fi

log "Writing production .env"
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "$RESOLVED_APP_URL"
set_env LOG_LEVEL error
set_env DB_CONNECTION mysql
set_env DB_HOST "$DB_HOST"
set_env DB_PORT "$DB_PORT"
set_env DB_DATABASE "$DB_DATABASE"
set_env DB_USERNAME "$DB_USERNAME"
set_env DB_PASSWORD "$DB_PASSWORD"
set_env SESSION_DRIVER database
set_env CACHE_STORE database
set_env QUEUE_CONNECTION database
set_env SCOUT_DRIVER database

log "Preparing Laravel writable directories"
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache || true

if php -r 'exit(function_exists("proc_open") ? 0 : 1);'; then
    log "proc_open is available; using Hostinger-safe Composer mode"
else
    warn "proc_open is disabled by Hostinger; Composer child scripts will be run directly by Bash"
fi

log "Installing PHP dependencies"
COMPOSER_MEMORY_LIMIT=-1 composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

rm -f \
    bootstrap/cache/config.php \
    bootstrap/cache/packages.php \
    bootstrap/cache/services.php

log "Running Laravel package discovery directly"
if ! php artisan package:discover --ansi --no-interaction; then
    warn "artisan package:discover failed; rebuilding Laravel package manifest directly"
    php -r '
        require __DIR__."/vendor/autoload.php";
        $files = new Illuminate\Filesystem\Filesystem();
        $manifest = new Illuminate\Foundation\PackageManifest(
            $files,
            __DIR__,
            __DIR__."/bootstrap/cache/packages.php"
        );
        $manifest->build();
    '
fi

log "Refreshing Filament and Livewire assets"
php artisan filament:upgrade --no-interaction || warn "Filament asset refresh was skipped; continuing deployment"
php artisan vendor:publish --force --tag=livewire:assets --ansi --no-interaction || warn "Livewire asset publish was skipped; continuing deployment"

if [[ "$FRESH_ENV" -eq 1 ]] || ! grep -Eq '^APP_KEY=base64:.+' .env; then
    log "Generating Laravel application key"
    php artisan key:generate --force --no-interaction
fi

if command -v npm >/dev/null 2>&1; then
    log "npm is available; rebuilding frontend assets"
    if [[ -f package-lock.json ]]; then
        npm ci --no-audit --no-fund
    else
        npm install --no-audit --no-fund
    fi
    npm run build
elif [[ -f public/build/manifest.json ]]; then
    log "npm is unavailable; using prebuilt Vite assets committed in public/build"
else
    fail "npm is unavailable and public/build/manifest.json is missing. Production frontend assets are required."
fi

log "Running database migrations"
php artisan migrate --force --no-interaction

log "Creating public storage link"
php artisan storage:link --force --no-interaction || true

log "Refreshing production caches"
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan route:cache >/dev/null 2>&1 || true

log "Deployment complete"
printf 'Application URL: %s\n' "$RESOLVED_APP_URL"
printf 'Branch: %s\n' "$BRANCH"
printf 'Database: %s\n' "$DB_DATABASE"
