#!/usr/bin/env bash
set -euo pipefail

DRUSH="/opt/drupal/vendor/bin/drush"
SITE_DIR="/opt/drupal/web/sites/default"
STATE_MARKER="/opt/drupal/state/.installed"

echo "[portfolio-cms] Waiting for PostgreSQL at ${POSTGRES_HOST}:${POSTGRES_PORT}..."
until PGPASSWORD="${POSTGRES_PASSWORD}" psql -h "${POSTGRES_HOST}" -p "${POSTGRES_PORT}" -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" -c "SELECT 1;" >/dev/null 2>&1; do
  sleep 2
done
echo "[portfolio-cms] PostgreSQL is ready."

# Ensure pg_trgm exists even on an existing volume
PGPASSWORD="${POSTGRES_PASSWORD}" psql -h "${POSTGRES_HOST}" -p "${POSTGRES_PORT}" -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" -c "CREATE EXTENSION IF NOT EXISTS pg_trgm;" >/dev/null 2>&1 || true

mkdir -p "${SITE_DIR}/files" /opt/drupal/private /opt/drupal/state
chown -R www-data:www-data "${SITE_DIR}/files" /opt/drupal/private /opt/drupal/state

write_settings_and_services() {
  # Configure CORS in services.yml so Next.js and browser clients can access JSON:API & custom API
  if [ ! -f "${SITE_DIR}/services.yml" ] && [ -f "${SITE_DIR}/default.services.yml" ]; then
    cp "${SITE_DIR}/default.services.yml" "${SITE_DIR}/services.yml"
  fi

  cat > "${SITE_DIR}/services.yml" <<'EOF'
parameters:
  session.storage.options:
    gc_probability: 1
    gc_divisor: 100
    gc_maxlifetime: 200000
    cookie_lifetime: 2000000
  twig.config:
    debug: false
    auto_reload: null
    cache: true
  renderer.config:
    required_cache_contexts: ['languages:language_interface', 'theme', 'user.permissions']
    auto_placeholder_conditions:
      max-age: 0
      contexts: ['session', 'user']
      tags: []
  http.response.debug_cacheability_headers: false
  factory.keyvalue: {}
  factory.keyvalue.expirable: {}
  filter_protocols:
    - http
    - https
    - ftp
    - news
    - nntp
    - tel
    - telnet
    - mailto
    - irc
    - ssh
    - sftp
    - webcal
    - rtsp
  cors.config:
    enabled: true
    allowedHeaders: ['*']
    allowedMethods: ['GET', 'POST', 'PATCH', 'DELETE', 'OPTIONS']
    allowedOrigins: ['*']
    exposedHeaders: false
    maxAge: 3600
    supportsCredentials: false
EOF

  # Persist or reconstruct settings.php across container recreates so DB state is never lost
  HASH_SALT_FILE="/opt/drupal/state/hash_salt.txt"
  if [ ! -f "${HASH_SALT_FILE}" ]; then
    php -r "echo bin2hex(random_bytes(32));" > "${HASH_SALT_FILE}"
  fi
  HASH_SALT=$(cat "${HASH_SALT_FILE}")

  if [ ! -f "${SITE_DIR}/settings.php" ]; then
    if [ -f "/opt/drupal/state/settings.php" ]; then
      cp "/opt/drupal/state/settings.php" "${SITE_DIR}/settings.php"
    else
      cp "${SITE_DIR}/default.settings.php" "${SITE_DIR}/settings.php"
      chmod 644 "${SITE_DIR}/settings.php"
      cat >> "${SITE_DIR}/settings.php" <<EOF

\$databases['default']['default'] = [
  'database' => getenv('POSTGRES_DB') ?: 'portfolio_cms',
  'username' => getenv('POSTGRES_USER') ?: 'drupal',
  'password' => getenv('POSTGRES_PASSWORD') ?: '',
  'prefix' => '',
  'host' => getenv('POSTGRES_HOST') ?: 'db',
  'port' => getenv('POSTGRES_PORT') ?: '5432',
  'isolation_level' => 'READ COMMITTED',
  'namespace' => 'Drupal\\\\pgsql\\\\Driver\\\\Database\\\\pgsql',
  'driver' => 'pgsql',
  'autoload' => 'core/modules/pgsql/src/Driver/Database/pgsql/',
];
\$settings['hash_salt'] = '${HASH_SALT}';
EOF
    fi
  fi

  # Append trusted_host_patterns, Traefik reverse proxy HTTPS headers, and private file path
  if [ -f "${SITE_DIR}/settings.php" ] && ! grep -q "PORTFOLIO_CMS_CONFIG" "${SITE_DIR}/settings.php"; then
    chmod 644 "${SITE_DIR}/settings.php"
    cat >> "${SITE_DIR}/settings.php" <<'EOF'

// PORTFOLIO_CMS_CONFIG
$trusted_hosts = [
  '^localhost$',
  '^127\.0\.0\.1$',
  '^cms$',
  '^portfolio_cms$',
  '^.+\.local$',
];

// Automatically trust the host from PUBLIC_CMS_URL and optional comma-separated TRUSTED_HOSTS (for Dokploy)
if ($public_url = getenv('PUBLIC_CMS_URL')) {
  $parsed_host = parse_url($public_url, PHP_URL_HOST);
  if ($parsed_host) {
    $trusted_hosts[] = '^' . preg_quote($parsed_host, '/') . '$';
  }
}
if ($extra_hosts = getenv('TRUSTED_HOSTS')) {
  foreach (explode(',', $extra_hosts) as $h) {
    $h = trim($h);
    if ($h !== '') {
      $trusted_hosts[] = '^' . preg_quote($h, '/') . '$';
    }
  }
}
$settings['trusted_host_patterns'] = array_values(array_unique($trusted_hosts));

// Reverse-proxy support (Dokploy / Traefik TLS termination)
if (getenv('REVERSE_PROXY') !== 'false') {
  $settings['reverse_proxy'] = TRUE;
  if (!empty($_SERVER['REMOTE_ADDR'])) {
    $settings['reverse_proxy_addresses'] = [$_SERVER['REMOTE_ADDR']];
  }
  $settings['reverse_proxy_trusted_headers'] =
    \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_FOR |
    \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_HOST |
    \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PORT |
    \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_PROTO;
}

$settings['file_private_path'] = '/opt/drupal/private';
$settings['config_sync_directory'] = '/opt/drupal/state/sync';
EOF
  fi

  if [ -f "${SITE_DIR}/settings.php" ]; then
    cp "${SITE_DIR}/settings.php" "/opt/drupal/state/settings.php"
  fi
}

if [ ! -f "${STATE_MARKER}" ]; then
  echo "[portfolio-cms] First run detected — installing Drupal 11 with PostgreSQL..."
  DB_URL="pgsql://${POSTGRES_USER}:${POSTGRES_PASSWORD}@${POSTGRES_HOST}:${POSTGRES_PORT}/${POSTGRES_DB}"

  ${DRUSH} site:install standard \
    --db-url="${DB_URL}" \
    --site-name="${DRUPAL_SITE_NAME:-Saad Portfolio Studio}" \
    --account-name="${DRUPAL_ADMIN_USER:-admin}" \
    --account-pass="${DRUPAL_ADMIN_PASS:-admin123}" \
    --account-mail="${DRUPAL_ADMIN_EMAIL:-admin@portfolio.local}" \
    --yes

  write_settings_and_services

  echo "[portfolio-cms] Enabling core, page builder, and modular portfolio extensions..."
  ${DRUSH} en -y \
    jsonapi \
    paragraphs \
    entity_reference_revisions \
    portfolio_builder \
    portfolio_core

  echo "[portfolio-cms] Enabling modern Gin admin theme..."
  ${DRUSH} theme:enable gin -y || true
  ${DRUSH} config:set system.theme admin gin -y || true
  ${DRUSH} config:set gin.settings enable_darkmode "1" -y || true
  ${DRUSH} config:set gin.settings classic_toolbar "vertical" -y || true

  echo "[portfolio-cms] Seeding initial Modular Pages, Projects, and Blog Posts..."
  ${DRUSH} php:eval "\Drupal::service('portfolio_core.sample_seeder')->seedAll(false);"

  ${DRUSH} cr
  chown -R www-data:www-data "${SITE_DIR}" /opt/drupal/state /opt/drupal/private
  touch "${STATE_MARKER}"
  echo "[portfolio-cms] Bootstrap complete! Admin UI ready at ${PUBLIC_CMS_URL:-http://localhost:8090}/user/login"
else
  echo "[portfolio-cms] Existing installation found — ensuring settings & cache are up to date..."
  write_settings_and_services
  chown -R www-data:www-data "${SITE_DIR}/files" /opt/drupal/state /opt/drupal/private
  ${DRUSH} cr || true
fi

exec "$@"
