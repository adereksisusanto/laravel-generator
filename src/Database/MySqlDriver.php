<?php

namespace Adereksisusanto\Laravel\Generator\Database;

use Illuminate\Support\Facades\DB;

class MySqlDriver implements \Adereksisusanto\Laravel\Generator\Contracts\DriverContract
{
    protected $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function getTables()
    {
        $database = $this->getDatabaseName();
        $results = DB::connection($this->connection)
            ->select("SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'", [$database]);

        return array_map(function ($row) {
            return $row->TABLE_NAME;
        }, $results);
    }

    public function getColumnDetails($table)
    {
        $database = $this->getDatabaseName();
        $results = DB::connection($this->connection)
            ->select('SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE, COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION', [$database, $table]);

        $columns = [];

        foreach ($results as $row) {
            $columns[] = [
                'name' => $row->COLUMN_NAME,
                'type' => $this->parseColumnType($row->COLUMN_TYPE),
                'raw_type' => $row->COLUMN_TYPE,
                'nullable' => $row->IS_NULLABLE === 'YES',
                'default' => $row->COLUMN_DEFAULT,
                'auto_increment' => stripos($row->EXTRA, 'auto_increment') !== false,
                'unsigned' => stripos($row->COLUMN_TYPE, 'unsigned') !== false,
                'length' => $row->CHARACTER_MAXIMUM_LENGTH,
                'precision' => $row->NUMERIC_PRECISION,
                'scale' => $row->NUMERIC_SCALE,
                'comment' => $row->COLUMN_COMMENT,
                'primary' => false,
            ];
        }

        $primaryKeys = $this->getPrimaryKeys($table);
        foreach ($columns as &$column) {
            if (in_array($column['name'], $primaryKeys)) {
                $column['primary'] = true;
            }
        }

        return $columns;
    }

    public function getForeignKeys($table)
    {
        $database = $this->getDatabaseName();
        $results = DB::connection($this->connection)
            ->select('SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL', [$database, $table]);

        $foreignKeys = [];

        foreach ($results as $row) {
            $foreignKeys[] = [
                'column' => $row->COLUMN_NAME,
                'foreign_table' => $row->REFERENCED_TABLE_NAME,
                'foreign_column' => $row->REFERENCED_COLUMN_NAME,
            ];
        }

        return $foreignKeys;
    }

    protected function getPrimaryKeys($table)
    {
        $database = $this->getDatabaseName();
        $results = DB::connection($this->connection)
            ->select("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'", [$database, $table]);

        return array_map(function ($row) {
            return $row->COLUMN_NAME;
        }, $results);
    }

    protected function getDatabaseName()
    {
        /** @var \Illuminate\Database\Connection $conn */
        $conn = DB::connection($this->connection);
        return $conn->getDatabaseName();
    }

    protected function parseColumnType($rawType)
    {
        $rawType = strtolower($rawType);
        $rawType = preg_replace('/\s+unsigned/', '', $rawType);

        if (preg_match('/^(varchar|char|text|longtext|mediumtext|tinytext)/', $rawType, $m)) {
            return $m[1];
        }
        if (preg_match('/^(int|bigint|smallint|tinyint|mediumint)/', $rawType, $m)) {
            return $m[1];
        }
        if (preg_match('/^(decimal|float|double)/', $rawType, $m)) {
            return $m[1];
        }
        if (preg_match('/^(datetime|timestamp|date|time|year)/', $rawType, $m)) {
            return $m[1];
        }
        if (preg_match('/^(enum|set)/', $rawType, $m)) {
            return $m[1];
        }
        if (preg_match('/^(json|binary|blob|longblob|mediumblob)/', $rawType, $m)) {
            return $m[1];
        }

        return $rawType;
    }
}
