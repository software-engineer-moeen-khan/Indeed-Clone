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
    # A fresh Hostinger site commonly contains only default.php and/or .well-known.
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

    # Preserve a pre-existing server .env if there is one.
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

# Safely replace a dotenv key while keeping secrets out of Git history.
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

# Derive the Hostinger domain from /home/USER/domains/DOMAIN/public_html when
# APP_URL was not explicitly supplied to the command.
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

# Keep the app usable on ordinary shared hosting without requiring Redis.
set_env SESSION_DRIVER database
set_env CACHE_STORE database
set_env QUEUE_CONNECTION database

log "Installing PHP dependencies"
COMPOSER_MEMORY_LIMIT=-1 composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress

if [[ "$FRESH_ENV" -eq 1 ]] || ! grep -Eq '^APP_KEY=base64:.+' .env; then
    log "Generating Laravel application key"
    php artisan key:generate --force --no-interaction
fi

if command -v npm >/dev/null 2>&1; then
    log "Installing and building frontend assets"
    if [[ -f package-lock.json ]]; then
        npm ci --no-audit --no-fund
    else
        npm install --no-audit --no-fund
    fi
    npm run build
else
    fail "npm is required because public/build is not committed. Enable Node.js/npm for this Hostinger account and run the same command again."
fi

log "Preparing Laravel storage and cache directories"
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache || true

log "Running database migrations"
php artisan migrate --force --no-interaction

log "Creating public storage link"
php artisan storage:link --force --no-interaction || true

log "Refreshing production caches"
php artisan optimize:clear
php artisan config:cache
php artisan view:cache

# Route cache is useful in production, but some applications intentionally use
# closure routes. Do not fail deployment if those routes cannot be cached.
php artisan route:cache >/dev/null 2>&1 || true

log "Deployment complete"
printf 'Application URL: %s\n' "$RESOLVED_APP_URL"
printf 'Branch: %s\n' "$BRANCH"
printf 'Database: %s\n' "$DB_DATABASE"
