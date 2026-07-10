<?php

namespace Adereksisusanto\Laravel\Generator\Commands;

use Adereksisusanto\Laravel\Generator\Concerns\TableHelper;
use Adereksisusanto\Laravel\Generator\Database\Schema;
use Adereksisusanto\Laravel\Generator\Generators\MigrationGenerator;
use Adereksisusanto\Laravel\Generator\Generators\ModelGenerator;
use Adereksisusanto\Laravel\Generator\Generators\SeederGenerator;
use Illuminate\Console\Command;

class GenerateCommand extends Command
{
    use TableHelper;

    protected $signature = 'generate
                            {--tables= : Comma-separated list of table names to process}
                            {--connection= : Database connection to use}
                            {--path= : Custom output path for generated files}
                            {--namespace= : Custom namespace for models}
                            {--model-only : Generate only models}
                            {--migration-only : Generate only migrations}
                            {--seeder-only : Generate only seeders}
                            {--seeder-limit= : Max records to seed (0 = unlimited)}
                            {--force : Overwrite existing files}';

    protected $description = 'Generate Laravel models, migrations and seeders from database tables';

    protected $modelGenerator;

    protected $migrationGenerator;

    protected $seederGenerator;

    public function __construct(ModelGenerator $modelGenerator, MigrationGenerator $migrationGenerator, SeederGenerator $seederGenerator)
    {
        parent::__construct();

        $this->modelGenerator = $modelGenerator;
        $this->migrationGenerator = $migrationGenerator;
        $this->seederGenerator = $seederGenerator;
    }

    public function handle()
    {
        $connection = $this->getConnection();
        $force = $this->option('force');
        $modelOnly = $this->option('model-only');
        $migrationOnly = $this->option('migration-only');
        $seederOnly = $this->option('seeder-only');
        $seederLimit = $this->option('seeder-limit');

        $selectedTables = $this->scanTables($connection);
        if ($selectedTables === null) {
            return 1;
        }

        $schemaManager = new Schema;

        if (! $modelOnly && ! $seederOnly) {
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

        if (! $migrationOnly && ! $seederOnly) {
            $this->line('');
            $this->info('Generating models...');

            $namespace = $this->option('namespace') ?: config('generator.namespace');
            $path = $this->option('path') ?: config('generator.paths.model');

            foreach ($selectedTables as $table) {
                $columns = $schemaManager->getColumnDetails($table, $connection);
                $foreignKeys = $schemaManager->getForeignKeys($table, $connection);

                $result = $this->modelGenerator->generate($table, $columns, $foreignKeys, [
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
        }

        if (! $modelOnly && ! $migrationOnly) {
            $this->line('');
            $this->info('Generating seeders...');

            $seederOptions = [
                'force' => $force,
                'connection' => $connection,
            ];

            if ($seederLimit !== null) {
                $seederOptions['limit'] = (int) $seederLimit;
            }

            foreach ($selectedTables as $table) {
                $result = $this->seederGenerator->generate($table, $seederOptions);

                if ($result) {
                    $this->info("  [OK] Seeder created: {$result}");
                } else {
                    $this->warn("  [SKIP] Seeder for {$table} already exists.");
                }
            }
        }

        $this->line('');
        $this->info('Generation completed!');

        return 0;
    }
}
