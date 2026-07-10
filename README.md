# Laravel Generator

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-9-FF2D20?logo=laravel)
![License](https://img.shields.io/github/license/adereksisusanto/laravel-generator)
[![Tests](https://github.com/adereksisusanto/laravel-generator/workflows/Tests/badge.svg)](https://github.com/adereksisusanto/laravel-generator/actions)
[![PHPStan](https://github.com/adereksisusanto/laravel-generator/workflows/PHPStan/badge.svg)](https://github.com/adereksisusanto/laravel-generator/actions)
[![2.x](https://img.shields.io/github/v/tag/adereksisusanto/laravel-generator?filter=v2.*&label=2.x&color=blue)](https://github.com/adereksisusanto/laravel-generator/tree/2.x)
[![Downloads](https://img.shields.io/packagist/dt/adereksisusanto/laravel-generator)](https://packagist.org/packages/adereksisusanto/laravel-generator)

Generate Laravel **models**, **migrations**, and **seeders** directly from your existing database tables.

Supports MySQL, PostgreSQL, SQLite, and SQL Server. Compatible with PHP 8.0+ and Laravel 9.

## Installation

```bash
composer require adereksisusanto/laravel-generator
```

Publish config (optional):

```bash
php artisan vendor:publish --provider="Adereksisusanto\Laravel\Generator\ServiceProvider" --tag=config
```

## Commands

```bash
# Generate all (models + migrations + seeders)
php artisan generate

# Individual generators
php artisan generate:model
php artisan generate:migration
php artisan generate:seeder
```

### Options

```bash
# Specific tables
php artisan generate:model --tables=users,posts

# Custom namespace (model only)
php artisan generate:model --namespace="App\Models"

# Record limit (seeder only, 0 = unlimited)
php artisan generate:seeder --limit=50

# Overwrite existing files
php artisan generate:model --force

# Custom connection
php artisan generate:model --connection=mysql

# Also generate migration and/or seeder with model
php artisan generate:model --tables=users -m
php artisan generate:model --tables=users -s
php artisan generate:model --tables=users -ms
```

### Combined command flags (`generate`)

| Flag | Description |
|------|-------------|
| `--model-only` | Generate only models |
| `--migration-only` | Generate only migrations |
| `--seeder-only` | Generate only seeders |
| `--seeder-limit` | Max seed records (0 = unlimited) |

## What Gets Generated

### Models

Eloquent model with `$fillable`, `$casts`, `$hidden`, relationships (`belongsTo`/`hasMany`), `HasFactory` trait, and `SoftDeletes` trait when `deleted_at` column exists.

### Migrations

`Schema::create()` with proper column types, nullable, unsigned, defaults, and indexes.

### Seeders

`Model::insert([...])` populated with actual data from your database table.

## Configuration

```php
// config/generator.php

'tables' => [
    'includes' => [],
    'excludes' => ['migrations', 'failed_jobs'],
],
'paths' => [
    'model' => app_path('Models'),
    'migration' => database_path('migrations'),
    'seeder' => database_path('seeders'),
],
'model' => [
    'timestamps' => true,
    'soft_deletes' => false,
    'relationships' => true,
],
'seeder' => [
    'limit' => 100,
],
```

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md) for contribution guidelines.

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.
