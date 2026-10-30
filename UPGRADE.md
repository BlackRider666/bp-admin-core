# Upgrade guide

## 1.0 → 1.1 (laravel-admin 3.1, blade-ui 3.1)

The canonical, step-by-step guide (U-01..U-19, Known limits) lives in the `black-paradise/laravel-admin` package:
`bp-laravel-admin/UPGRADE.md`. Pin the old line (`core-admin 1.0.*`) before starting.

Core-only changes you are affected by when you implement core contracts directly:

| Id | Change |
|----|--------|
| U-01 | `laravel-admin 3.1` requires `core-admin ^1.1`; bump your constraint. |
| U-02 | `EntityDefinitionContract` gains `capabilities()`, `resolveName()` (instance), `filters()`, `repository()`, `mutator()`; `modelClass(): ?string`. |
| U-03 | `ActionContract`: `scope()` → `scopes(): list<ActionScope>`; `handle(ActionContext): ActionResult` and `fields()` required; `permission()`. |
| U-04 | `actions()` returns `list<class-string<ActionContract>>`, resolved by `ActionResolverContract`. |
| U-10 | `DeleteRecordUseCase` no longer calls `exists()`; `EntityMutatorInterface::delete()` returns `false` for a missing record. |
| U-16 | `AbstractField::requiredOnCreate()` added; `{field}__clear` semantics for file fields. |
| U-19 | `AbstractField::isSearchable()` added (not on `FieldContract`). |

New additive API: `EntityCapabilities` / `Capability`, `ActionContext` / `ActionResult` / `ActionScope`, `ExecuteActionUseCase`,
`SaveEntityGraphUseCase` / `DeleteEntityGraphUseCase`, `WriteScopeContract`, `ActionResolverContract`, new exceptions
(`CapabilityDeniedException`, `ActionNotFoundException`, `InvalidActionScopeException`, `InvalidDefinitionException`).
