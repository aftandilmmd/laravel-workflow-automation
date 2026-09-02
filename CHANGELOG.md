# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2026-09-02

### Added

- Node builder API: fluent, typed builder classes for all 26 built-in nodes under
  `Aftandilmmd\WorkflowAutomation\Builders`, plus `GenericNode` for nodes without one.
- `Workflow::addNode()`, `WorkflowNode::addNode()` and `WorkflowService::addNode()` accept a
  builder; `Workflow::addNodes()` adds several at once.
- Builders are validated against the node's config schema before the node is saved; missing
  required fields and unknown keys throw `InvalidNodeConfigException`.
- Enums for every fixed-value config field: `ConditionOperator`, `MailSendMode`,
  `HttpMethod`, `CommandType`, `AiProvider`, `ModelEvent`, `ScheduleInterval`,
  `WebhookAuthType`, `WorkflowTriggerOn`, `DelayUnit`, `ErrorRoute`, `MergeMode`,
  `ParseFormat`, `CodeMode`, `FilterLogic`, `StickyColor`.
- `AsWorkflowNode` accepts a `builder` argument; `NodeRegistry::builderFor()` resolves it,
  and the registry listing, the REST node endpoints and the MCP node metadata expose it as
  `builder_class`.
- `php artisan workflow:make-node-builder` generates a builder from a node's config schema.

### Changed

- Node `select` options are now derived from enums, so schema options and enum cases cannot
  drift apart. The emitted values are unchanged.
- Documentation and examples use the builder API.

### Deprecated

- `Operator` — use `ConditionOperator` instead. Values are identical and the old enum keeps
  working.
- Adding nodes with a node key and config array. It still works, but the builder API is the
  recommended path and the array form will likely be removed in a future release.

[0.2.0]: https://github.com/aftandilmmd/laravel-workflow-automation/compare/v0.1.8...v0.2.0
