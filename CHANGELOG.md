# Changelog

All notable changes to `black-paradise/core-admin` are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Breaking entries cite the id in `bp-laravel-admin/UPGRADE.md`.

## [1.1.0] - 2026-10-30

### Added
- `EntityCapabilities` and `Capability` value objects; `CapabilityDeniedException`.
- `ActionContext`, `ActionResult`, `ActionScope`, `ActionResolverContract`, `ExecuteActionUseCase`; `ActionNotFoundException`, `InvalidActionScopeException`.
- `SaveEntityGraphUseCase`, `DeleteEntityGraphUseCase` (owned hasMany/morphMany cascade), `WriteScopeContract`; events dispatched after commit.
- `AbstractField::requiredOnCreate()`; `AbstractField::isSearchable()`.
- `InvalidDefinitionException`.

### Changed
- **BREAKING (U-02)** `EntityDefinitionContract` gains `capabilities()`, `resolveName()`, `filters()`, `repository()`, `mutator()`; `modelClass(): ?string`.
- **BREAKING (U-03)** `ActionContract`: `scope()` → `scopes()`; `handle()`, `fields()`, `permission()` added.
- **BREAKING (U-04)** `actions()` returns class-strings.
- **BREAKING (U-10)** `DeleteRecordUseCase` no longer calls `exists()`; a mutator `delete()` returning `false` raises `EntityNotFoundException`.
- **BREAKING (U-12)** Domain events are dispatched after commit inside a write scope.

### Fixed
- `CreateRecordUseCase` and `UpdateRecordUseCase` gain optional `enforceCapability` (default `true`); embedded-child create/update pass `false` so embed-only (`creatable = false`) definitions can be created as embedded children. Authorization still runs first.
- File field `required()` rule no longer fails updates without a re-upload when `requiredOnCreate()` is used (U-16).
