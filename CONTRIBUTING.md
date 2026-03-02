# Contributing to Flex Content Scheduler

## Coding Standards
- Follow WordPress Coding Standards (WPCS) and run PHPCS before opening a PR.
- Prefer strict sanitization/escaping for all input/output.
- Keep hooks and filters documented with `@since` and parameter docs.

## Development Setup
1. Install PHP dependencies:
   - `composer install`
2. Install JS dependencies:
   - `npm install`
3. Build assets:
   - `npm run build`

## Quality Checks
- Run PHP unit tests:
  - `vendor/bin/phpunit`
- Run PHPCS:
  - `vendor/bin/phpcs --standard=WordPress .`
- Run JS tests:
  - `npm test`

## Pull Request Rules
- Keep changes focused and small.
- Add/adjust tests for behavior changes.
- Update docs (README/audit/changelog) when user-facing behavior changes.
- Do not commit generated `assets/dist` unless release packaging requires it.

## Security Rules
- Verify capability + nonce for all write operations.
- Use prepared statements and sanitized data for database writes.
- Avoid open redirect behavior; use allowlisted hosts for redirect actions.
