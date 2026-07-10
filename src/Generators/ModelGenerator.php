<?php

namespace Adereksisusanto\Laravel\Generator\Generators;

use Adereksisusanto\Laravel\Generator\Database\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelGenerator
{
    protected $template;

    public function __construct()
    {
        $this->template = file_get_contents(__DIR__.'/../../resources/templates/model.stub');
    }

    public function generate($table, array $columns, array $foreignKeys, array $options = [])
    {
        $modelName = $this->getModelName($table);
        $namespace = isset($options['namespace']) ? $options['namespace'] : config('generator.namespace', 'App\Models');
        $defaultPath = app_path('Models');
        $outputPath = isset($options['path']) ? $options['path'] : config('generator.paths.model', $defaultPath);
        $force = isset($options['force']) ? $options['force'] : false;

        $filePath = rtrim($outputPath, '/\\').'/'.$modelName.'.php';

        if (File::exists($filePath) && ! $force) {
            return false;
        }

        $fillable = $this->getFillable($columns);
        $casts = $this->getCasts($columns);
        $hidden = $this->getHidden($columns);
        $traits = $this->getTraits($columns, $options);
        $relations = $this->getRelations($table, $foreignKeys, $namespace);
        $uses = $this->getUseStatements($traits, $relations);
        $traitUses = $this->getTraitUses($traits);

        $content = str_replace(
            [
                '{{namespace}}',
                '{{modelName}}',
                '{{table}}',
                '{{fillable}}',
                '{{casts}}',
                '{{hidden}}',
                '{{traitUses}}',
                '{{relations}}',
                '{{uses}}',
            ],
            [
                $namespace,
                $modelName,
                $table,
                $fillable,
                $casts,
                $hidden,
                $traitUses,
                $relations,
                $uses,
            ],
            $this->template
        );

        $content = $this->cleanupEmptyLines($content);

        if (! File::isDirectory(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }

        File::put($filePath, $content);

        return $filePath;
    }

    protected function getUseStatements(array $traits, $relations)
    {
        $uses = [];

        foreach ($traits as $trait) {
            $shortName = class_basename($trait);
            $uses[$shortName] = $trait;
        }

        if (! empty($relations)) {
            $lines = explode("\n", $relations);
            foreach ($lines as $line) {
                if (preg_match('/@return\s+\\\\?([A-Za-z0-9_\\\\]+)/', $line, $m)) {
                    $relatedClass = ltrim($m[1], '\\');
                    $shortName = class_basename($relatedClass);
                    $uses[$shortName] = $relatedClass;
                }
            }
        }

        if (empty($uses)) {
            return '';
        }

        $result = "\n";
        foreach ($uses as $shortName => $fullClass) {
            $result .= "use {$fullClass};\n";
        }

        return $result;
    }

    protected function getTraitUses(array $traits)
    {
        if (empty($traits)) {
            return '';
        }

        $result = "\n";
        foreach ($traits as $trait) {
            $shortName = class_basename($trait);
            $result .= "    use {$shortName};\n";
        }

        return $result;
    }

    protected function getModelName($table)
    {
        return Str::studly(Str::singular($table));
    }

    protected function getFillable(array $columns)
    {
        $fillable = [];

        foreach ($columns as $column) {
            if ($column['primary'] || $column['auto_increment']) {
                continue;
            }

            if (in_array($column['name'], ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $fillable[] = $column['name'];
        }

        if (empty($fillable)) {
            return '[]';
        }

        $result = "[\n";
        foreach ($fillable as $field) {
            $result .= "        '{$field}',\n";
        }
        $result .= '    ]';

        return $result;
    }

    protected function getCasts(array $columns)
    {
        $casts = [];

        foreach ($columns as $column) {
            $castType = $this->resolveCastType($column);

            if ($castType !== null) {
                $casts[$column['name']] = $castType;
            }
        }

        if (empty($casts)) {
            return '[]';
        }

        $result = "[\n";
        foreach ($casts as $field => $type) {
            $result .= "        '{$field}' => '{$type}',\n";
        }
        $result .= '    ]';

        return $result;
    }

    protected function resolveCastType(array $column)
    {
        $type = $column['type'];

        if ($column['primary'] || $column['auto_increment']) {
            return null;
        }

        if (in_array($type, ['boolean', 'tinyint'])) {
            return 'boolean';
        }

        if (in_array($type, ['int', 'bigint', 'smallint', 'mediumint'])) {
            return 'integer';
        }

        if (in_array($type, ['decimal', 'float', 'double'])) {
            return 'float';
        }

        if ($type === 'json') {
            return 'array';
        }

        if ($type === 'datetime' && ! in_array($column['name'], ['created_at', 'updated_at'])) {
            return 'datetime';
        }

        $typeMaps = [
            'date' => 'date',
            'timestamp' => 'datetime',
            'binary' => 'boolean',
        ];

        if (isset($typeMaps[$type])) {
            return $typeMaps[$type];
        }

        return null;
    }

    protected function getHidden(array $columns)
    {
        $hidden = [];

        foreach ($columns as $column) {
            $name = $column['name'];

            if (in_array($name, ['password', 'remember_token'])) {
                $hidden[] = $name;
            }
        }

        if (empty($hidden)) {
            return '[]';
        }

        $result = "[\n";
        foreach ($hidden as $field) {
            $result .= "        '{$field}',\n";
        }
        $result .= '    ]';

        return $result;
    }

    protected function getTraits(array $columns, array $options)
    {
        $traits = [
            'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        ];

        $hasSoftDelete = false;
        foreach ($columns as $column) {
            if ($column['name'] === 'deleted_at') {
                $hasSoftDelete = true;

                break;
            }
        }

        if ($hasSoftDelete || (isset($options['soft_deletes']) && $options['soft_deletes'])) {
            $traits[] = 'Illuminate\\Database\\Eloquent\\SoftDeletes';
        }

        return $traits;
    }

    protected function getRelations($table, array $foreignKeys, $namespace = 'App\Models')
    {
        if (! config('generator.model.relationships', true)) {
            return '';
        }

        $relations = '';

        foreach ($foreignKeys as $fk) {
            $relatedModel = $this->getModelName($fk['foreign_table']);
            $relationName = Str::camel(Str::singular($fk['foreign_table']));
            $localKey = $fk['column'];
            $foreignKey = $fk['foreign_column'];

            $relations .= <<<PHP

    /**
     * @return \\Illuminate\\Database\\Eloquent\\Relations\\BelongsTo
     */
    public function {$relationName}()
    {
        return \$this->belongsTo({$relatedModel}::class, '{$localKey}', '{$foreignKey}');
    }

PHP;
        }

        $tableName = $table;
        $allTables = $this->getPossibleHasManyTables($tableName);

        foreach ($allTables as $relatedTable) {
            $foreignKeyName = Str::singular($tableName).'_id';

            if ($this->hasForeignKeyInRelatedTable($relatedTable, $foreignKeyName)) {
                $relatedModel = $this->getModelName($relatedTable);
                $relationName = Str::camel(Str::plural($relatedTable));

                $relations .= <<<PHP

    /**
     * @return \\Illuminate\\Database\\Eloquent\\Relations\\HasMany
     */
    public function {$relationName}()
    {
        return \$this->hasMany({$relatedModel}::class, '{$foreignKeyName}');
    }

PHP;
            }
        }

        return $relations;
    }

    protected function getPossibleHasManyTables($table)
    {
        $connection = config('generator.connection');
        $schemaManager = new Schema;

        try {
            return $schemaManager->getTables($connection);
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function hasForeignKeyInRelatedTable($relatedTable, $foreignKey)
    {
        $connection = config('generator.connection');
        $schemaManager = new Schema;

        try {
            $columns = $schemaManager->getColumnDetails($relatedTable, $connection);

            foreach ($columns as $column) {
                if ($column['name'] === $foreignKey) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    protected function cleanupEmptyLines($content)
    {
        $lines = explode("\n", $content);
        $result = [];
        $prevEmpty = false;

        foreach ($lines as $line) {
            $isEmpty = trim($line) === '';

            if ($isEmpty && $prevEmpty) {
                continue;
            }

            $result[] = $line;
            $prevEmpty = $isEmpty;
        }

        return implode("\n", $result);
    }
}
