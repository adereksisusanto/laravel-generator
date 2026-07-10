<?php

namespace Adereksisusanto\Laravel\Generator\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrationGenerator
{
    protected $template;

    protected $singleTemplate;

    public function __construct()
    {
        $this->template = file_get_contents(__DIR__.'/../../resources/templates/migration.stub');
        $this->singleTemplate = file_get_contents(__DIR__.'/../../resources/templates/migration-single.stub');
    }

    public function generate($table, array $columns, array $options = [])
    {
        $force = isset($options['force']) ? $options['force'] : false;
        $migrationName = 'create_'.$table.'_table';
        $className = 'Create'.Str::studly($table).'Table';

        $outputPath = config('generator.paths.migration', database_path('migrations'));

        $existing = glob(rtrim($outputPath, '/\\').'/*_'.$migrationName.'.php');

        if (! empty($existing) && ! $force) {
            return false;
        }

        $timestamp = date('Y_m_d_His');
        $customPrefix = config('generator.migration.prefix');
        if ($customPrefix && strlen($customPrefix) >= 14) {
            $timestamp = $customPrefix;
        }

        $fileName = $timestamp.'_'.$migrationName.'.php';
        $filePath = rtrim($outputPath, '/\\').'/'.$fileName;

        $schemaLines = $this->buildSchema($columns, $table);

        $content = str_replace(
            [
                '{{className}}',
                '{{table}}',
                '{{schema}}',
            ],
            [
                $className,
                $table,
                $schemaLines,
            ],
            $this->template
        );

        if (! File::isDirectory(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }

        File::put($filePath, $content);

        return $filePath;
    }

    public function generateSingle(array $tables, array $tablesColumns, array $options = [])
    {
        $force = isset($options['force']) ? $options['force'] : false;
        $migrationName = 'create_all_tables';
        $className = 'CreateAllTables';

        $outputPath = config('generator.paths.migration', database_path('migrations'));

        $existing = glob(rtrim($outputPath, '/\\').'/*_'.$migrationName.'.php');

        if (! empty($existing) && ! $force) {
            return false;
        }

        $timestamp = date('Y_m_d_His');
        $customPrefix = config('generator.migration.prefix');
        if ($customPrefix && strlen($customPrefix) >= 14) {
            $timestamp = $customPrefix;
        }

        $fileName = $timestamp.'_'.$migrationName.'.php';
        $filePath = rtrim($outputPath, '/\\').'/'.$fileName;

        $upLines = [];
        $downLines = [];

        foreach ($tables as $table) {
            $columns = $tablesColumns[$table] ?? [];
            $schema = $this->buildSchema($columns, $table);

            $upLines[] = "        Schema::create('{$table}', function (Blueprint \$table) {";
            $upLines[] = $schema;
            $upLines[] = '        });';
            $upLines[] = '';

            $downLines[] = "        Schema::dropIfExists('{$table}');";
        }

        $content = str_replace(
            [
                '{{className}}',
                '{{schema}}',
                '{{down}}',
            ],
            [
                $className,
                implode("\n", $upLines),
                implode("\n", $downLines),
            ],
            $this->singleTemplate
        );

        if (! File::isDirectory(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }

        File::put($filePath, $content);

        return $filePath;
    }

    protected function buildSchema(array $columns, $table)
    {
        $lines = [];

        foreach ($columns as $column) {
            $line = $this->buildColumnLine($column);

            if ($line !== null) {
                $lines[] = $line;
            }
        }

        if (! empty($columns)) {
            $hasTimestamps = false;
            $hasSoftDeletes = false;

            foreach ($columns as $column) {
                if ($column['name'] === 'created_at' || $column['name'] === 'updated_at') {
                    $hasTimestamps = true;
                }
                if ($column['name'] === 'deleted_at') {
                    $hasSoftDeletes = true;
                }
            }

            if ($hasTimestamps) {
                $lines[] = '            $table->timestamps();';
            }

            if ($hasSoftDeletes) {
                $lines[] = '            $table->softDeletes();';
            }
        }

        return implode("\n", $lines);
    }

    protected function buildColumnLine(array $column)
    {
        if ($column['auto_increment'] && $column['primary']) {
            if ($column['type'] === 'bigint') {
                return '            $table->bigIncrements(\''.$column['name'].'\');';
            }

            return '            $table->increments(\''.$column['name'].'\');';
        }

        $name = $column['name'];

        if (in_array($name, ['created_at', 'updated_at', 'deleted_at'])) {
            return null;
        }

        $method = $this->resolveSchemaMethod($column);
        $chain = '';

        if ($method === 'timestamps' || $method === 'softDeletes') {
            return null;
        }

        if ($method === 'enum') {
            return '            $table->enum(\''.$name.'\', []);';
        }

        $args = "'{$name}'";

        if ($column['length'] && $this->supportsLength($method)) {
            $args .= ', '.$column['length'];
        } elseif ($method === 'decimal') {
            $precision = isset($column['precision']) && $column['precision'] ? $column['precision'] : 8;
            $scale = isset($column['scale']) && $column['scale'] ? $column['scale'] : 2;
            $args .= ', '.$precision.', '.$scale;
        }

        $line = '            $table->'.$method.'('.$args.')';

        if ($column['nullable'] && $name !== 'id') {
            $chain .= '->nullable()';
        }

        if ($column['unsigned']) {
            $chain .= '->unsigned()';
        }

        if ($column['default'] !== null && $column['default'] !== '') {
            $default = $column['default'];

            if (is_numeric($default)) {
                $chain .= '->default('.$default.')';
            } elseif (in_array($default, ['CURRENT_TIMESTAMP', 'current_timestamp()'])) {
                $chain .= '->useCurrent()';
            } else {
                $chain .= "->default('{$default}')";
            }
        }

        if ($column['comment']) {
            $chain .= "->comment('".addslashes($column['comment'])."')";
        }

        $line .= $chain.';';

        return $line;
    }

    protected function resolveSchemaMethod(array $column)
    {
        $type = $column['type'];

        $map = [
            'int' => 'integer',
            'bigint' => 'bigInteger',
            'smallint' => 'smallInteger',
            'mediumint' => 'mediumInteger',
            'tinyint' => 'tinyInteger',
            'varchar' => 'string',
            'char' => 'char',
            'text' => 'text',
            'longtext' => 'longText',
            'mediumtext' => 'mediumText',
            'tinytext' => 'tinyText',
            'decimal' => 'decimal',
            'float' => 'float',
            'double' => 'double',
            'boolean' => 'boolean',
            'enum' => 'enum',
            'json' => 'json',
            'binary' => 'binary',
            'blob' => 'binary',
            'date' => 'date',
            'datetime' => 'dateTime',
            'timestamp' => 'timestamp',
            'time' => 'time',
            'year' => 'year',
        ];

        if (isset($map[$type])) {
            return $map[$type];
        }

        if ($column['auto_increment'] && ! $column['primary']) {
            return 'unsignedBigInteger';
        }

        return 'string';
    }

    protected function supportsLength($method)
    {
        return in_array($method, ['string', 'char', 'integer', 'bigInteger', 'smallInteger', 'mediumInteger', 'tinyInteger']);
    }
}
