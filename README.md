# Flex Content Scheduler

Schedule post expiry with flexible rules: unpublish, delete, redirect, or change status.

## Features
- Per-post expiry scheduling from a React metabox.
- Actions on expiry: `unpublish`, `delete`, `redirect`, `change_status`.
- WP-Cron processing with recurring and single-event scheduling.
- REST API endpoints for schedule CRUD and settings management.
- Admin dashboard (React) for schedule list, filters, and settings.
- Redirect host allowlist support for safer external redirects.
- Uncanny Automator integration (triggers + actions).

## Requirements
- WordPress `6.0+`
- PHP `7.4+`
- Node.js `18+` (for asset build/dev only)
- Composer `2+` (for PHP dependencies/dev tooling)

## Installation
1. Copy plugin to `wp-content/plugins/flex-content-scheduler`.
2. Run `composer install`.
3. Run `npm install`.
4. Build assets with `npm run build`.
5. Activate from WordPress Admin.

## Usage
1. Edit any supported public post type.
2. In **Content Expiry Schedule**, choose:
   - Expiry date/time
   - Expiry action
   - Redirect URL (if action is `redirect`)
   - New status (if action is `change_status`)
3. Save/update the schedule.
4. Optional: manage defaults from the admin settings page.

## REST API Reference
Base namespace: `/wp-json/flex-cs/v1`

- `GET /schedules`
- `POST /schedules`
- `GET /schedules/{id}`
- `PUT|PATCH /schedules/{id}`
- `DELETE /schedules/{id}`
- `GET /schedules/post/{post_id}`
- `GET /settings`
- `PUT|PATCH /settings`

Permissions:
- Read endpoints: require user with `edit_posts`.
- Write endpoints: require valid REST nonce, authenticated user, per-user rate limit, and `edit_post` for the target post.

## Hooks and Filters
Actions:
- `flex_cs_schedule_created`
- `flex_cs_schedule_updated`
- `flex_cs_schedule_deleted`
- `flex_cs_before_expiry_action`
- `flex_cs_after_expiry_action`
- `flex_cs_cron_processed`

Filters:
- `flex_cs_schedule_data_before_insert`
- `flex_cs_expiry_actions`
- `flex_cs_due_schedules_limit`
- `flex_cs_allowed_redirect_hosts`
- `flex_cs_rest_write_rate_limit`
- `flex_cs_rest_write_rate_window`
- `flex_cs_supported_post_types`
- `flex_cs_enable_runtime_fallback`

## Uncanny Automator Integration
Integration code: `FLEX_CS`

Triggers:
- `FLEX_CS_CONTENT_EXPIRED`
- `FLEX_CS_CONTENT_SCHEDULED`

Actions:
- `FLEX_CS_UNPUBLISH_POST`
- `FLEX_CS_REDIRECT_POST`

## Development Setup
Install dependencies:

```bash
composer install
npm install
```

Run during development:

```bash
npm run dev
```

Build production assets:

```bash
npm run build
```

## Testing and Quality Checks
PHP unit tests:

```bash
vendor/bin/phpunit
```

JavaScript tests:

```bash
npm test -- --runInBand
```

Coding standards:

```bash
vendor/bin/phpcs --standard=.phpcs.xml.dist
```

PHP syntax:

```bash
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

## Publish Checklist
Before publishing/tagging:

1. Ensure all checks pass:
   - `vendor/bin/phpunit`
   - `npm test -- --runInBand`
   - `vendor/bin/phpcs --standard=.phpcs.xml.dist`
   - `npm run build`
2. Verify plugin header version in `flex-content-scheduler.php` matches release tag.
3. Verify `Stable` docs/changelog version entries are updated.
4. Ensure build assets are included in release artifacts.
   - Note: `assets/dist/` is ignored by git, so include built files via CI artifact packaging or release zip assembly.
5. Smoke test on a clean WordPress instance:
   - create schedule
   - update schedule
   - delete schedule
   - verify each expiry action behavior
6. Confirm uninstall behavior (options/table cleanup) in non-production env.

## Architecture Notes
- Loader pattern is used for deterministic hook registration.
- Database schema upgrades are managed through `MigrationManager`.
- AI-assisted development workflow was used for scaffolding, repetitive test creation, and audit-driven hardening; security and behavior decisions were manually validated.

## Changelog
## 1.0.0
- Initial release with metabox/admin UI, REST API, cron processing, and Automator integration.
- Added security hardening (nonce validation, capability checks, rate limiting).
- Added redirect host allowlist and settings support.
- Added migration manager, expanded tests, and WPCS/PHPCS compliance improvements.

## License
GPL-2.0-or-later
