<?php

namespace Adereksisusanto\Laravel\Generator\Concerns;

use Adereksisusanto\Laravel\Generator\Database\Schema;

trait TableHelper
{
    protected function getTablesList()
    {
        $tables = $this->option('tables');

        if (empty($tables)) {
            return [];
        }

        return array_map('trim', explode(',', $tables));
    }

    protected function filterTables(array $allTables, array $specificTables)
    {
        $includes = config('generator.tables.includes', []);
        $excludes = config('generator.tables.excludes', []);

        if (! empty($specificTables)) {
            return array_intersect($allTables, $specificTables);
        }

        if (! empty($includes)) {
            return array_intersect($allTables, $includes);
        }

        return array_values(array_diff($allTables, $excludes));
    }

    protected function scanTables($connection)
    {
        $schemaManager = new Schema;
        $allTables = $schemaManager->getTables($connection);

        if (empty($allTables)) {
            $this->error('No tables found in the database.');

            return null;
        }

        $selectedTables = $this->filterTables($allTables, $this->getTablesList());

        if (empty($selectedTables)) {
            $this->error('No tables matched the specified criteria.');

            return null;
        }

        $this->info('Found '.count($selectedTables).' tables to process:');
        foreach ($selectedTables as $table) {
            $this->line("  - {$table}");
        }

        return $selectedTables;
    }

    protected function getConnection()
    {
        return $this->option('connection') ?: config('generator.connection');
    }
}
