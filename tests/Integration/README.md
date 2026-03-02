# Integration Tests

These tests are intended to run in a real WordPress runtime using `wp-env`.

## Setup
1. Install `@wordpress/env` globally or as a dev dependency.
2. Start environment:
   - `wp-env start`
3. Run integration test suite from container/CLI with WordPress loaded.

## Notes
- `tests/Integration/RestApiTest.php` includes controller-level tests for CI-level verification.
- For end-to-end REST validation, run requests against the `wp-env` WordPress instance.
