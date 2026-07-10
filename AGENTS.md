# AGENTS.md — Laravel Generator

## What this is

A Laravel **library package** (not a full app) that generates Eloquent models, migrations, and seeders by reverse-engineering an existing database schema. Supports MySQL, PostgreSQL, SQLite, SQL Server.

## Key architecture

- **PSR-4**: `src/` → `Adereksisusanto\Laravel\Generator\`, `tests/` → `Adereksisusanto\Laravel\Generator\Tests\`
- **Entrypoint**: `ServiceProvider.php` registers 4 artisan commands + publishes `config/generator.php`
- **Commands**: `generate` (combined), `generate:model`, `generate:migration`, `generate:seeder`
- **Generators** (`src/Generators/`): `ModelGenerator`, `MigrationGenerator`, `SeederGenerator` — each reads a `.stub` template from `resources/templates/`
- **Database layer** (`src/Database/`): `Schema` dispatches to driver classes (`MySqlDriver`, `PostgresDriver`, `SqliteDriver`, `SqlSrvDriver`) implementing `DriverContract`
- **Trait**: `TableHelper` (table scanning/filtering) is used by commands
- **Config**: `config/generator.php` controls tables (include/exclude), output paths, model/migration/seeder options

## Commands (exact)

```bash
composer test                    # phpunit
composer test-coverage           # phpunit --coverage-html coverage
composer lint                    # php-cs-fixer fix --dry-run --diff (src/ tests/ config/)
composer lint-fix                # php-cs-fixer fix
composer analyse                 # phpstan analyse --level=5 (src/ only)
composer check                   # lint -> analyse -> test  (run this before committing)
```

**NOTE**: `composer check` runs `lint` → `analyse` → `test` in sequence. Always run it before pushing.

## Testing quirks

- **Test base class**: `tests/TestCase.php` extends plain `PHPUnit\Framework\TestCase` (NOT Orchestra Testbench TestCase)
- Test file naming: `*Test.php` suffix required by `phpunit.xml.dist`
- Tests are not integration tests against a real DB — no DB setup needed

## Code style (php-cs-fixer)

Enforced by CI (`fix-php-code-style-issues.yml`) and `composer lint`/`composer lint-fix`:
- `@PSR12`, short arrays, alpha-sorted imports, `single_quote`, trailing commas in multiline, no unused imports
- Scoped to `src/`, `tests/`, `config/`

## Static analysis

PHPStan level 5, scoped to `src/` only (excludes `*.blade.php`).

## Framework support

PHP 7.2.5+, Laravel 7–8, Orchestra Testbench 5–6.

## CI workflows

| Workflow | Trigger | Key detail |
|---|---|---|
| `run-tests.yml` | push/PR on `.php` files | Matrix: PHP 7.2–8.1, Laravel 7/8, OS ubuntu+windows, prefer-lowest/prefer-stable |
| `phpstan.yml` | push/PR on `.php` files | PHP 7.4–8.3, single OS |
| `fix-php-code-style-issues.yml` | push on `.php` files | Auto-commits style fixes |
| `release.yml` | `workflow_dispatch` (manual) | Runs `composer check`, auto-updates CHANGELOG from git log, creates GitHub release |
| `dependabot-auto-merge.yml` | `pull_request_target` | Auto-merges minor/patch dependabot PRs |
| `opencode.yml` | comments containing `/oc` or `/opencode` | Model: opencode/deepseek-v4-flash-free |

## Outdated doc note

`CHANGELOG.md` mentions command `generate:from-database` — that name does not exist. The actual command is `generate`.

## VSCode

`.vscode/settings.json` references a local PHP 7.2.9 executable (Windows). Ignore unless you are on that machine.
