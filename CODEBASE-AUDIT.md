# Flex Content Scheduler — Codebase Audit Report

**Branch:** `review/codebase-audit`
**Date:** 2026-03-02
**Reviewer:** Oz (AI-assisted)

---

## 1. Security Issues

### 🔴 High Priority

- [x] **Unsanitized `$_SERVER['REQUEST_URI']`** — `flex-content-scheduler.php:24`
  The raw `$_SERVER['REQUEST_URI']` is cast to string but not sanitized with `esc_url()` or `sanitize_text_field()`. Even though it's only used for `strpos()` matching, it should follow WP coding standards.

- [x] **`get_due_schedules()` has no LIMIT clause** — `ScheduleManager.php:168`
  If thousands of expired rows accumulate (e.g. cron was disabled for a while), all rows load into memory at once. Add a configurable LIMIT (e.g. 50) and process in batches.

- [x] **REST write operations use `edit_posts` capability** — `ScheduleRestController.php:263`
  `create_item_permissions_check()` delegates to `get_item_permissions_check()` which only checks `edit_posts`. Any contributor-level user could create/update/delete schedules for any post. Consider checking `edit_post` for the specific `post_id` being acted on.

### 🟡 Medium Priority

- [x] **`@ini_set('display_errors', '0')` with error suppression** — `flex-content-scheduler.php:30`
  Uses `@ini_set` with PHPCS ignore. This is a workaround; if REST responses leak PHP notices, the root cause should be fixed instead.

- [x] **Dynamic redirect host allowlisting** — `ExpiryActions.php:152`
  `handle_template_redirect()` trusts any URL stored in `_flex_cs_redirect_url` post meta and adds its host to `allowed_redirect_hosts`. If an attacker gains `edit_posts` capability, they can set arbitrary redirect URLs. Consider adding a domain allowlist option.

- [x] **`validate()` uses `apply_filters('flex_cs_expiry_actions', ...)`** — `ScheduleManager.php:271`
  A malicious or buggy third-party plugin could inject unexpected action types through this filter. Document this as a security-sensitive hook and consider validating the filter output.

- [x] **No CSRF protection on REST DELETE** — `ScheduleRestController.php:87-98`
  While WordPress REST API uses nonce verification by default, the delete endpoint has no body-level validation. This is acceptable but worth confirming nonces are always passed from the JS client.

### 🟢 Low Priority / Informational

- [x] **No rate limiting on REST API endpoints** — Consider adding throttling for schedule creation to prevent abuse.
- [x] **`begin_request_guard()` OB buffering pattern** — `ScheduleRestController.php:374-389` — Unusual pattern for REST. Document why this is needed or remove if the underlying issue is resolved.

---

## 2. Performance Issues

### 🔴 High Priority

- [x] **`ensure_table_ready()` runs `SHOW TABLES` on every request** — `ScheduleManager.php:303-327`
  Called on every CRUD operation. Although it has a static `$table_checked` cache, the first invocation per request runs a `SHOW TABLES LIKE` query. On a high-traffic site this adds unnecessary DB overhead. Consider checking only during activation or using a transient.

- [x] **Runtime schedule processing on every page load** — `CronManager.php:72-89`
  `maybe_process_due_schedules_runtime()` fires on every `init` (non-cron, non-REST) request. This queries `get_option` twice and potentially processes all due schedules during regular frontend page loads, impacting TTFB.

### 🟡 Medium Priority

- [x] **No object caching** — `ScheduleManager.php`
  No use of `wp_cache_set()`/`wp_cache_get()` for schedule lookups. Repeated calls to `get_schedule()` or `get_schedule_by_post()` within a single request hit the database every time.

- [x] **Every-minute cron interval** — `CronManager.php:25`
  The `every_minute` interval is aggressive. Consider using WP's built-in single scheduled events at the exact expiry time (already partially done in `maybe_schedule_processing_event()`) and increasing the recurrence to `every_5_minutes`.

- [x] **`get_all_schedules()` LEFT JOIN without caching** — `ScheduleManager.php:215`
  On the admin list page, each load runs a JOIN query. For admin pages this is acceptable, but add a transient or short-lived cache for the total count at minimum.

### 🟢 Low Priority

- [x] **`MetaBox::register_meta_box()` loops all public post types** — `MetaBox.php:22-34`
  Fine for most sites, but add a filter (e.g. `flex_cs_supported_post_types`) so users can exclude specific types.

---

## 3. DocBlock / Documentation Issues

### Missing Class-Level DocBlocks

- [x] `Plugin.php` — Missing `@package`, `@since`, class description
- [x] `Loader.php` — Missing class docblock entirely
- [x] `Activator.php` — Missing class docblock
- [x] `Deactivator.php` — Missing class docblock
- [x] `AdminMenu.php` — Missing class docblock
- [x] `ListTable.php` — Missing class docblock (also a placeholder class) — **File does not exist; removed.**
- [x] `MetaBox.php` — Missing class docblock
- [x] `ScheduleRestController.php` — Missing class docblock
- [x] `ScheduleTable.php` — Missing class docblock
- [x] `DateTimeHelper.php` — Missing class docblock
- [x] `PostTypeHelper.php` — Missing class docblock
- [x] `CronManager.php` — Missing class docblock
- [x] `ScheduleManager.php` — Missing class docblock
- [x] `AutomatorIntegration.php` — Missing class docblock
- [x] `RedirectPostAction.php` — Missing class docblock
- [x] `UnpublishPostAction.php` — Missing class docblock
- [x] `ContentExpiredTrigger.php` — Missing class docblock
- [x] `ContentScheduledTrigger.php` — Missing class docblock

> **Note:** `ExpiryActions.php` is the only file with proper docblocks. Use it as the template for all other files.

### Missing Method-Level DocBlocks

- [x] Every public/private method in **all files except `ExpiryActions.php`** is missing `@param`, `@return`, `@since`, and description.
- [x] `flex-content-scheduler.php` — `flex_cs_run()` is missing `@since` and proper doc.
- [x] `uninstall.php` — Missing file-level `@package` and `@since` docblock.

### Missing `@since` Tags

- [x] No `@since` annotations anywhere. Add `@since 1.0.0` to every class, method, and hook.

### Missing JSDoc in JavaScript

- [x] `assets/src/admin/components/App.jsx` — No JSDoc on component
- [x] `assets/src/admin/components/ScheduleForm.jsx` — No JSDoc on component or props
- [x] `assets/src/admin/components/ScheduleList.jsx` — No JSDoc on component or props
- [x] `assets/src/admin/components/SettingsPanel.jsx` — No JSDoc on component
- [x] `assets/src/metabox/MetaBox.jsx` — No JSDoc on helper functions (`toLocalInput`, `toUtcString`, `parsePossiblyPollutedJson`, `request`)

### Missing Inline Hook Documentation

- [x] Hooks like `flex_cs_schedule_created`, `flex_cs_before_expiry_action`, etc. lack inline `@since` and `@param` annotations at their `do_action()`/`apply_filters()` call sites.

---

## 4. Code Quality & Best Practices

### 🔴 Issues

- [x] **`ListTable.php` is an empty placeholder** — File does not exist in the codebase; no action needed.
- [x] **`Activator.php:44` uses double-escaped class strings**
  ```php
  array( 'Anisur\\\\ContentScheduler\\\\Activator', 'activate' )
  ```
  Use `Activator::class` syntax instead for clarity.
- [x] **`ScheduleManager` return types are imprecise**
  - `get_schedule()` returns `mixed` (object|null) — should have `@return object|null`
  - `create_schedule()` returns `int|false` — needs explicit return type doc
  - `get_schedule_by_post()` returns `mixed` — same issue

### 🟡 Improvements

- [x] **Inconsistent error logging** — Some places use `error_log('[FLEX_CS]...')` behind `WP_DEBUG`, others silently fail. Create a centralized `Logger` helper class.
- [x] **`ScheduleRestController` OB guard is duplicated** — Every method calls `begin_request_guard()`/`end_request_guard()`. Consider middleware or a decorator pattern.
- [x] **`MetaBox.jsx`: `parsePossiblyPollutedJson()`** — This function works around PHP notices leaking into REST JSON. If the OB guard in the REST controller works correctly, this shouldn't be needed. Investigate root cause.
- [x] **`ScheduleList.jsx`: `total` state is set but never used** — Fixed: total is now read from X-WP-Total header and used to disable "Next" button.
- [x] **`ScheduleForm.jsx`: No error feedback** — Fixed: error state and catch block now display user-visible error messages.
- [x] **JS strings are not internationalized** — All strings now use `@wordpress/i18n` `__()`.
- [x] **No PropTypes or TypeScript** — JSX components have no prop validation.

### 🟢 Nice-to-Have

- [x] **Add a `flex_cs_supported_post_types` filter** — Allow users to restrict which post types get the metabox.
- [x] **Webpack externals missing `@wordpress/i18n`** — `webpack.config.js` externals only maps `react`/`react-dom` but `@wordpress/api-fetch`, `@wordpress/i18n`, and `@wordpress/components` should also be externalized since they're WP dependencies.
- [x] **Add `.editorconfig`** — Enforce consistent coding style across editors.
- [x] **Add `CONTRIBUTING.md`** — Document coding standards and contribution workflow.

---

## 5. Testing Gaps

### 🔴 Missing Tests

- [x] **`ScheduleRestController`** — `RestApiTest.php` only checks that a method exists. Needs actual endpoint tests (request/response, permissions, validation).
- [x] **`ExpiryActions::handle_template_redirect()`** — Critical security function with no test coverage for redirect behavior, host allowlisting, and edge cases.
- [x] **`AdminMenu`** — No tests for menu registration or script enqueuing.
- [x] **`MetaBox::save_meta_box_data()`** — No tests for nonce verification, capability check, date validation, schedule creation/update/delete via metabox.
- [x] **`Activator::activate()`** — No tests for table creation, cron scheduling, or version checking.
- [x] **`Deactivator::deactivate()`** — No test for cron cleanup.
- [x] **`ScheduleTable`** — No tests for `create_table()` or `drop_table()`.
- [x] **`PostTypeHelper`** — No tests.

### 🟡 Improvements

- [x] **JS components have zero test coverage** — Add Jest + React Testing Library for the admin and metabox components.
- [x] **Integration tests need a real WP environment** — `RestApiTest` should use `wp-env` or WP test framework for proper integration testing.
- [x] **`AutomatorIntegration` + triggers/actions** — No tests at all.
- [x] **Add edge case tests** — e.g. concurrent schedule processing, DB failure handling, expired redirect cleanup.

---

## 6. Architecture Observations

- [x] **Loader pattern is fine but consider removing it** — The `Loader` class adds indirection without significant benefit over calling `add_action()`/`add_filter()` directly. Makes debugging hooks harder.
- [x] **No dependency injection container** — Currently manual wiring in `Plugin::__construct()`. Fine for this scale, but keep in mind as features grow.
- [x] **No migration system** — The DB schema is created via `dbDelta` on activation but there's no upgrade path if the schema changes in v2.

---

## 7. Recommended Action Priority

1. **Fix REST permission checks** (security) — most impactful
2. **Add LIMIT to `get_due_schedules()`** (security + performance)
3. **Add docblocks to all files** (code quality) — use `ExpiryActions.php` as template
4. **Add `@since 1.0.0` tags everywhere** (WP coding standards)
5. **Fix `ScheduleList.jsx` pagination** (bug — "Next" button never disables)
6. **Add error handling to `ScheduleForm.jsx`** (UX)
7. **Internationalize JS strings** (i18n)
8. **Add missing unit tests** — prioritize REST controller + MetaBox save
9. **Optimize `ensure_table_ready()`** — use transient instead of per-request DB check
10. **Remove or implement `ListTable.php`**
11. **Add `flex_cs_supported_post_types` filter**
12. **Externalize WP JS deps in webpack config**
