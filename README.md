# Saad Shafique — Headless Drupal 11 CMS Backend

Headless Drupal 11 + PostgreSQL CMS powering the personal portfolio (`saadsh15/website`), featuring:

- **`portfolio_core`:** Modular Pages, Projects, Blog Posts, Taxonomies, Typography Settings (`/admin/config/system/portfolio-appearance`), Headless REST API (`/api/v1/*`), native `/jsonapi`, and Next.js on-demand revalidation webhooks.
- **`portfolio_builder`:** Paragraph-based Website Building Modules (Hero Section + Portrait/Quote Card, Cinematic Background Band, Bento Grid, Dynamic Showcase, Rich Text, Stats Strip, Media Gallery, CTA Banner, and Pluggable Module Embed).

## Required Persistent Volumes (Dokploy)

Mount these 3 persistent volumes on the CMS container:
- `/opt/drupal/web/sites/default/files` — Uploaded media & public files
- `/opt/drupal/private` — Private files
- `/opt/drupal/state` — Persisted `settings.php`, hash salt, `.installed` marker, and config sync

## Environment Variables

See [.env.example](.env.example).
