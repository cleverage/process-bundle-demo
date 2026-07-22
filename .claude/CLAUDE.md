# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Symfony 7.4 demo project showcasing [`cleverage/process-bundle`](https://github.com/cleverage/process-bundle) v5 and its bridge bundles (archive, cache, doctrine, flysystem, rest, soap, ui). The "product" here is not PHP code — it is the **process configurations** under `config/packages/process/`. Each file demonstrates one feature of the process ecosystem. `src/` contains only a handful of glue classes (entities, fixtures, a few custom adapters/DTOs).

## Environment & commands

Everything runs inside Docker (`.docker/compose.yaml`), driven by the `Makefile`. Do **not** run PHP/composer/console directly on the host — wrap them in the container. The Makefile targets already do this via `docker compose ... run --rm php-fpm`.

```bash
make start          # First-time bring-up: containers + migrations + fixtures + assets
make upd            # Start containers detached (installs vendor if missing)
make bash           # Shell into the php-fpm container as the host user
make stop / down    # Stop / tear down containers

make quality        # phpstan + php-cs-fixer + rector (run before committing)
make phpstan        # PHPStan (level 10, analyses src/ only)
make php-cs-fixer   # PHP-CS-Fixer (config in .php-cs-fixer.dist.php)
make rector         # Rector (config in rector.php)
make tests          # PHPUnit (== make phpunit)

make xdebug/on      # Restart php with Xdebug enabled; xdebug/off to disable
```

UI: after `make start`, the process UI is at http://process-bundle-demo.localhost/process (login `admin@clever-age.com` / `admin@clever-age.com`).

Run one PHPUnit test: `make bash` then `vendor/bin/phpunit --filter TestName`. Note `phpunit.xml.dist` sets `requireCoverageMetadata="true"` and `failOnRisky/failOnWarning="true"`, so every test needs a `#[CoversClass]`/`#[CoversNothing]` attribute or it fails as risky.

## Running processes

Processes are executed via the bundle's console commands (inside the container):

```bash
bin/console cleverage:process:execute <process.code> [--input=...] [-c key:"value"]
bin/console cleverage:process:list            # list all defined processes
bin/console cleverage:process:help <code>     # describe a process (tasks, options)
bin/console cleverage:ui-process:user-create  # create a UI login user
```

Each config's `help:` line shows a concrete example. `-c key:"'value'"` injects a **context** value (note the nested quoting for string literals). `--input=` seeds the process entry point.

## The cleverage bundles (`vendor/cleverage/*`)

`composer.json` pins `preferred-install: { "cleverage/*": "source" }`, so these bundles are checked out as **full source** under `vendor/cleverage/` (with their own `src/`, `docs/`, `tests/`) — read them directly to understand behaviour rather than guessing. Each `demo.*` process in this project exercises one of them.

**`process-bundle` (core, v5)** — the engine. Everything else plugs into it.
- **Model / lifecycle** (`src/Model/`): a process is a graph of tasks; each task is a service implementing `TaskInterface::execute(ProcessState $state)`. `ProcessState` is the data bus passed task-to-task — `getInput()`/`setOutput()`/`setErrorOutput()`, `stop()`, plus `getContext()` and `getContextualizedOption()` (the `-c key:value` values). Optional lifecycle interfaces refine a task: `IterableTaskInterface` (emit many outputs from one input), `BlockingTaskInterface` (wait for all inputs before proceeding), `Initializable/Flushable/FinalizableTaskInterface`, and `AbstractConfigurableTask` (OptionsResolver-based options).
- **Tasks** (`src/Task/`): the generic building blocks referenced in configs — `ConstantOutputTask`, `TransformerTask`, `File/Csv/*`, `File/JsonStream/*`, `File/Xml|Yaml/*`, `Serialization/*` (normalize/denormalize/(de)serialize), `Validation/ValidatorTask`, `Debug/*` (`DebugTask`, `DieTask`, `StopwatchTask`, `ErrorForwarderTask`), `Reporting/*` (`LoggerTask`, stat counters), aggregators/iterators, and `Process/*` (run sub-processes or shell commands).
- **Transformers** (`src/Transformer/`): the value-mapping library used inside `TransformerTask` and `generic_transformers` — `MappingTransformer`, `CallbackTransformer`, `CastTransformer`, `RulesTransformer`, `Array/*`, `String/*` (implode, explode, slugify, sprintf, preg_match…), `Date/*`, `Object/*`, `Serialization/*`, `Xml/XpathEvaluatorTransformer`.
- **Registries** (`src/Registry/`): `ProcessConfigurationRegistry` and `TransformerRegistry` resolve the `@service` / transformer names used in YAML.

**Bridge bundles** each add a small set of tasks (`src/Task/`) plus a client/adapter concept wired in `config/services.yaml`:
- `doctrine-process-bundle` — DB & entity tasks: `Database/DatabaseReaderTask`, `DatabaseUpdaterTask`, and `EntityManager/*` (`DoctrineReaderTask`, `DoctrineWriterTask`, `DoctrineBatchWriterTask`, cleaner/detacher/refresher/remover/clearer).
- `rest-process-bundle` — `Task/RequestTask` driven by a tagged `cleverage.rest.client` (see `apicarto_ign` client in `services.yaml`).
- `soap-process-bundle` — `Task/RequestTask` driven by a tagged `cleverage.soap.client` (see `oorsprong_countryinfo` client).
- `flysystem-process-bundle` — `FileFetchTask`, `ListContentTask`, `RemoveFileTask` over Flysystem storages (config in `config/packages/flysystem.yaml`); the demo also uses SFTP.
- `archive-process-bundle` — `ZipTask` / `UnzipTask`.
- `cache-process-bundle` — `GetTask` / `SetTask` backed by a tagged `cleverage.cache.adapter` (see `src/Adapter/MemoryAdapter.php`).
- `ui-process-bundle` (v3) — the **web UI** at `/process` (no process tasks). Built on EasyAdmin: dashboard, process list/launch/upload-and-run actions, `User` auth, and it **persists every run** — `ProcessExecution`, `LogRecord`, `ProcessSchedule` entities. Its Doctrine migrations ship in the bundle (`vendor/cleverage/ui-process-bundle/src/Migrations/`), which is why `make doctrine/migrations` creates those tables. It also provides the Scheduler integration behind `src/Schedule.php`.

To find how a task behaves or what options it accepts, open its class under the relevant `vendor/cleverage/*/src/Task/` and its matching page under that bundle's `docs/reference/`.

## How a process config works

A process = a named graph of **tasks**. Read `config/packages/process/demo.transformer.yaml` first — it is the canonical, self-documenting example. Key structure:

```yaml
clever_age_process:
    configurations:
        demo.<name>:
            description: ...        # shown in UI / process list
            help: ...               # example invocation
            entry_point: <task>     # required when input is fed in (e.g. file upload)
            options:
                ui: { ... }         # controls the UiProcessBundle launch form (ui_launch_mode: form,
                                    #   entrypoint_type: file|text, default values, Symfony validation constraints)
            tasks:
                <taskName>:
                    service: '@CleverAge\ProcessBundle\Task\...'  # the task class, referenced as a service
                    options: { ... }
                    outputs: [ <nextTask>, ... ]   # edges to downstream tasks (fan-out allowed)
                    error_strategy: stop|skip|...
```

Tasks pass data along `outputs` edges. `TransformerTask` chains named **transformers** (mapping, callback, sprintf, slugify, array_map, cast, …). Bridge bundles contribute their own tasks (`RestProcessBundle\Task\RequestTask`, `SoapProcessBundle`, doctrine readers/writers, archive zip/unzip, sftp, cache).

- **Generic transformers** (project-wide reusable transformer aliases like `uppercase`, `json_decode`, `substr`) are defined once in `config/packages/cleverage_process.yaml` under `clever_age_process.generic_transformers` and referenced by name in any process.
- `default_error_strategy: stop` is set globally there too.
- All process files are auto-imported via `imports: [{ resource: process/ }]` in `cleverage_process.yaml`. Adding a new `demo.*.yaml` under `config/packages/process/` is enough to register a new process.

## Wiring custom PHP into processes (`config/services.yaml`)

The `_instanceof` rules auto-tag anything implementing `TaskInterface` (public, non-shared, logged) or `TransformerInterface` (tagged `cleverage.transformer`). To expose external clients/adapters to processes you register a service with the right tag — see existing examples in `services.yaml`:
- SOAP client → tag `cleverage.soap.client` (referenced by `code` in soap tasks)
- REST client → tag `cleverage.rest.client`
- Cache adapter → tag `cleverage.cache.adapter` (see `src/Adapter/MemoryAdapter.php`)

## Domain glue in `src/`

- `Entity/Book`, `Entity/Author` + repositories — sample data model, managed via Doctrine, exposed in EasyAdmin, used by the doctrine-process demos.
- `DataFixtures/` — loaded by `make doctrine/fixtures`.
- `Dto/Commune.php` — denormalization target for the REST demo.
- `Schedule.php` — Symfony Scheduler provider (currently empty; add recurring tasks here).
- `Kernel.php` — standard Symfony micro-kernel.

## Docs

Human-facing task reference lives in `docs/reference/` (template at `docs/reference/tasks/_template.md`). `docs/index.md` is the usage entry point.
