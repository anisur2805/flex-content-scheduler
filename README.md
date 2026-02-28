# Flex Content Scheduler

Schedule post expiry with flexible rules - unpublish, delete, redirect, or change status.

## Features
- Per-post expiry scheduling with multiple actions
- Automatic processing through WP-Cron
- REST API for external integrations
- React-powered admin and metabox UI
- Uncanny Automator triggers and actions

## Requirements
- WordPress 6.0+
- PHP 7.4+

## Installation
1. Copy plugin into `wp-content/plugins/flex-content-scheduler`
2. Run `composer install`
3. Run `npm install && npm run build`
4. Activate from WordPress admin

## Usage
- Edit any public post type and configure expiry in the metabox.
- Visit the plugin admin page to review all schedules.

## REST API Reference
Base: `/wp-json/flex-cs/v1`
- `GET /schedules`
- `POST /schedules`
- `GET /schedules/{id}`
- `PUT /schedules/{id}`
- `DELETE /schedules/{id}`
- `GET /schedules/post/{post_id}`

## Hooks & Filters Reference
- `flex_cs_schedule_created`
- `flex_cs_schedule_updated`
- `flex_cs_schedule_deleted`
- `flex_cs_before_expiry_action`
- `flex_cs_after_expiry_action`
- `flex_cs_cron_processed`
- `flex_cs_schedule_data_before_insert`
- `flex_cs_expiry_actions`

## Uncanny Automator Integration
Provides 2 triggers and 2 actions under integration code `FLEX_CS`.

## Development Setup
- PHP dependencies: `composer install`
- JS dependencies: `npm install`
- Build assets: `npm run build`

## Running Tests
Run `vendor/bin/phpunit`.

## Architecture Notes
This plugin follows a Loader pattern and PSR-4 autoloading to keep responsibilities separate. An AI-assisted workflow was used to scaffold and validate repetitive integration code while preserving explicit security checks.

## Changelog
### 1.0.0
- Initial release

## License
GPL-2.0-or-later
