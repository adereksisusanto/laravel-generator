<?php

namespace Adereksisusanto\Laravel\Generator\Commands;

use Adereksisusanto\Laravel\Generator\Concerns\TableHelper;
use Adereksisusanto\Laravel\Generator\Generators\SeederGenerator;
use Illuminate\Console\Command;

class GenerateSeederCommand extends Command
{
    use TableHelper;

    protected $signature = 'generate:seeder
                            {--tables= : Comma-separated list of table names to process}
                            {--connection= : Database connection to use}
                            {--path= : Custom output path for generated files}
                            {--limit= : Max records to seed (0 = unlimited)}
                            {--force : Overwrite existing files}';

    protected $description = 'Generate seeders from database table data';

    protected $generator;

    public function __construct(SeederGenerator $generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }

    public function handle()
    {
        $connection = $this->getConnection();
        $force = $this->option('force');
        $limit = $this->option('limit');

        $selectedTables = $this->scanTables($connection);
        if ($selectedTables === null) {
            return 1;
        }

        $this->line('');
        $this->info('Generating seeders...');

        foreach ($selectedTables as $table) {
            $options = [
                'force' => $force,
                'connection' => $connection,
            ];

            if ($limit !== null) {
                $options['limit'] = (int) $limit;
            }

            $result = $this->generator->generate($table, $options);

            if ($result) {
                $this->info("  [OK] Seeder created: {$result}");
            } else {
                $this->warn("  [SKIP] Seeder for {$table} already exists.");
            }
        }

        $this->line('');
        $this->info('Done!');

        return 0;
    }
}
