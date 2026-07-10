<?php

namespace Adereksisusanto\Laravel\Generator\Generators;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SeederGenerator
{
    protected $template;

    public function __construct()
    {
        $this->template = file_get_contents(__DIR__.'/../../resources/templates/seeder.stub');
    }

    public function generate($table, array $options = [])
    {
        $force = isset($options['force']) ? $options['force'] : false;
        $connection = isset($options['connection']) ? $options['connection'] : config('generator.connection');

        $seederName = Str::studly(Str::plural($table)).'TableSeeder';
        $modelName = Str::studly(Str::singular($table));

        $defaultPath = database_path('seeders');
        $outputPath = isset($options['path']) ? $options['path'] : config('generator.paths.seeder', $defaultPath);

        $filePath = rtrim($outputPath, '/\\').'/'.$seederName.'.php';

        if (File::exists($filePath) && ! $force) {
            return false;
        }

        $rows = $this->fetchData($table, $options);
        $dataArray = $this->buildDataArray($rows);

        $namespace = config('generator.namespace', 'App\Models');

        $content = str_replace(
            [
                '{{seederName}}',
                '{{modelName}}',
                '{{namespace}}',
                '{{table}}',
                '{{data}}',
            ],
            [
                $seederName,
                $modelName,
                $namespace,
                $table,
                $dataArray,
            ],
            $this->template
        );

        if (! File::isDirectory(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }

        File::put($filePath, $content);

        return $filePath;
    }

    protected function fetchData($table, array $options)
    {
        $connection = isset($options['connection']) ? $options['connection'] : null;
        $limit = isset($options['limit']) ? (int) $options['limit'] : config('generator.seeder.limit', 100);

        $query = DB::connection($connection)->table($table);

        if ($limit > 0) {
            $query->limit($limit);
        }

        $rows = $query->get();

        return $rows->toArray();
    }

    protected function buildDataArray(array $rows)
    {
        if (empty($rows)) {
            return '[]';
        }

        $result = "[\n";

        foreach ($rows as $row) {
            $row = (array) $row;
            $result .= "            [\n";

            foreach ($row as $column => $value) {
                $result .= "                '{$column}' => ".$this->formatValue($value).",\n";
            }

            $result .= "            ],\n";
        }

        $result .= '        ]';

        return $result;
    }

    protected function formatValue($value)
    {
        if ($value === null) {
            return 'null';
        }

        if (is_numeric($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $escaped = str_replace(["'", "\n", "\r"], ["\\'", '\\n', '\\r'], $value);

        return "'{$escaped}'";
    }
}
