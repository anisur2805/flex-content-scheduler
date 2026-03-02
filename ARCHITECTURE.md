# Architecture Notes

## Loader Pattern Decision
The plugin keeps the `Loader` abstraction to keep hook registration centralized and testable. This improves hook visibility in unit tests and keeps constructor wiring deterministic.

## Dependency Management
A full DI container is intentionally not introduced yet to avoid over-engineering for the current plugin size. Services are still explicitly instantiated in `Plugin` for readability.

## Migration Strategy
A versioned migration entry point now exists via `MigrationManager`.
- Option key: `flex_cs_db_version`
- Current target version: `1.0.0`
- Future schema changes should be added through incremental `version_compare()` branches in `MigrationManager::migrate()`.
