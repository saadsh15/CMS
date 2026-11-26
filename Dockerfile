FROM drupal:11-php8.3-apache

# Ensure PostgreSQL client/extension and curl are available
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    libpq-dev \
    postgresql-client \
    && (php -m | grep -qi pdo_pgsql || docker-php-ext-install pdo_pgsql pgsql) \
    && rm -rf /var/lib/apt/lists/*

# PHP tuning for Drupal CMS + JSON:API
RUN { \
      echo 'memory_limit = 512M'; \
      echo 'upload_max_filesize = 32M'; \
      echo 'post_max_size = 36M'; \
      echo 'max_execution_time = 120'; \
    } > /usr/local/etc/php/conf.d/portfolio-cms.ini

WORKDIR /opt/drupal

# Install Drush, Paragraphs (Page Builder foundation), and Gin modern admin theme
RUN composer require --no-interaction --prefer-dist \
    drush/drush \
    drupal/paragraphs \
    drupal/gin \
    && composer clear-cache

# Bake custom portfolio modules into the image for standalone production deployments (Dokploy)
COPY web/modules/custom /opt/drupal/web/modules/custom
COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Prepare runtime directories
RUN mkdir -p /opt/drupal/private /opt/drupal/state /opt/drupal/web/sites/default/files \
    && chown -R www-data:www-data /opt/drupal/web/sites /opt/drupal/private /opt/drupal/state /opt/drupal/web/modules/custom

COPY scripts/entrypoint.sh /usr/local/bin/portfolio-cms-entrypoint.sh
RUN chmod +x /usr/local/bin/portfolio-cms-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/portfolio-cms-entrypoint.sh"]
CMD ["apache2-foreground"]
