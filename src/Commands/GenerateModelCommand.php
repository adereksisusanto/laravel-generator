<?php

namespace Adereksisusanto\Laravel\Generator\Commands;

use Adereksisusanto\Laravel\Generator\Concerns\TableHelper;
use Adereksisusanto\Laravel\Generator\Database\Schema;
use Adereksisusanto\Laravel\Generator\Generators\MigrationGenerator;
use Adereksisusanto\Laravel\Generator\Generators\ModelGenerator;
use Adereksisusanto\Laravel\Generator\Generators\SeederGenerator;
use Illuminate\Console\Command;

class GenerateModelCommand extends Command
{
    use TableHelper;

    protected $signature = 'generate:model
                            {--tables= : Comma-separated list of table names to process}
                            {--connection= : Database connection to use}
                            {--namespace= : Custom namespace for models}
                            {--path= : Custom output path for generated files}
                            {--force : Overwrite existing files}
                            {--m|migration : Also generate migrations}
                            {--s|seeder : Also generate seeders}';

    protected $description = 'Generate Eloquent models from database tables';

    protected $generator;

    protected $migrationGenerator;

    protected $seederGenerator;

    public function __construct(ModelGenerator $generator, MigrationGenerator $migrationGenerator, SeederGenerator $seederGenerator)
    {
        parent::__construct();

        $this->generator = $generator;
        $this->migrationGenerator = $migrationGenerator;
        $this->seederGenerator = $seederGenerator;
    }

    public function handle()
    {
        $connection = $this->getConnection();
        $force = $this->option('force');
        $namespace = $this->option('namespace') ?: config('generator.namespace');
        $path = $this->option('path') ?: config('generator.paths.model');

        $selectedTables = $this->scanTables($connection);
        if ($selectedTables === null) {
            return 1;
        }

        $schemaManager = new Schema;
        $this->line('');
        $this->info('Generating models...');

        foreach ($selectedTables as $table) {
            $columns = $schemaManager->getColumnDetails($table, $connection);
            $foreignKeys = $schemaManager->getForeignKeys($table, $connection);

            $result = $this->generator->generate($table, $columns, $foreignKeys, [
                'force' => $force,
                'namespace' => $namespace,
                'path' => $path,
                'connection' => $connection,
            ]);

            if ($result) {
                $this->info("  [OK] Model created: {$result}");
            } else {
                $this->warn("  [SKIP] Model for {$table} already exists.");
            }
        }

        if ($this->option('migration')) {
            $this->line('');
            $this->info('Generating migrations...');

            foreach ($selectedTables as $table) {
                $columns = $schemaManager->getColumnDetails($table, $connection);
                $result = $this->migrationGenerator->generate($table, $columns, [
                    'force' => $force,
                    'connection' => $connection,
                ]);

                if ($result) {
                    $this->info("  [OK] Migration created: {$result}");
                } else {
                    $this->warn("  [SKIP] Migration for {$table} already exists.");
                }
            }
        }

        if ($this->option('seeder')) {
            $this->line('');
            $this->info('Generating seeders...');

            foreach ($selectedTables as $table) {
                $result = $this->seederGenerator->generate($table, [
                    'force' => $force,
                    'connection' => $connection,
                ]);

                if ($result) {
                    $this->info("  [OK] Seeder created: {$result}");
                } else {
                    $this->warn("  [SKIP] Seeder for {$table} already exists.");
                }
            }
        }

        $this->line('');
        $this->info('Done!');

        return 0;
    }
}
