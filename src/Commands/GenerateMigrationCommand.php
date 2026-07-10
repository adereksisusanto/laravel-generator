<?php

namespace Adereksisusanto\Laravel\Generator\Commands;

use Adereksisusanto\Laravel\Generator\Concerns\TableHelper;
use Adereksisusanto\Laravel\Generator\Database\Schema;
use Adereksisusanto\Laravel\Generator\Generators\MigrationGenerator;
use Illuminate\Console\Command;

class GenerateMigrationCommand extends Command
{
    use TableHelper;

    protected $signature = 'generate:migration
                            {--tables= : Comma-separated list of table names to process}
                            {--connection= : Database connection to use}
                            {--path= : Custom output path for generated files}
                            {--force : Overwrite existing files}
                            {--single : Generate a single migration file for all tables}';

    protected $description = 'Generate migrations from database tables';

    protected $generator;

    public function __construct(MigrationGenerator $generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }

    public function handle()
    {
        $connection = $this->getConnection();
        $force = $this->option('force');

        $selectedTables = $this->scanTables($connection);
        if ($selectedTables === null) {
            return 1;
        }

        $schemaManager = new Schema();

        if ($this->option('single')) {
            return $this->handleSingle($selectedTables, $schemaManager, $connection, $force);
        }

        $this->line('');
        $this->info('Generating migrations...');

        foreach ($selectedTables as $table) {
            $columns = $schemaManager->getColumnDetails($table, $connection);
            $result = $this->generator->generate($table, $columns, [
                'force' => $force,
                'connection' => $connection,
            ]);

            if ($result) {
                $this->info("  [OK] Migration created: {$result}");
            } else {
                $this->warn("  [SKIP] Migration for {$table} already exists.");
            }
        }

        $this->line('');
        $this->info('Done!');

        return 0;
    }

    protected function handleSingle(array $tables, Schema $schemaManager, $connection, $force)
    {
        $this->line('');
        $this->info('Generating single migration file...');

        $tablesColumns = [];

        foreach ($tables as $table) {
            $tablesColumns[$table] = $schemaManager->getColumnDetails($table, $connection);
        }

        $result = $this->generator->generateSingle($tables, $tablesColumns, [
            'force' => $force,
            'connection' => $connection,
        ]);

        if ($result) {
            $this->info("  [OK] Migration created: {$result}");
        } else {
            $this->warn('  [SKIP] Single migration already exists.');
        }

        $this->line('');
        $this->info('Done!');

        return 0;
    }
}
