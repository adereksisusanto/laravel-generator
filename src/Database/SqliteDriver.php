<?php

namespace Adereksisusanto\Laravel\Generator\Database;

use Illuminate\Support\Facades\DB;

class SqliteDriver implements \Adereksisusanto\Laravel\Generator\Contracts\DriverContract
{
    protected $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function getTables()
    {
        $results = DB::connection($this->connection)
            ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        return array_map(function ($row) {
            return $row->name;
        }, $results);
    }

    public function getColumnDetails($table)
    {
        $safeTable = str_replace('"', '""', $table);
        $results = DB::connection($this->connection)
            ->select('PRAGMA table_info("' . $safeTable . '")');

        $columns = [];

        foreach ($results as $row) {
            $type = $row->type;
            $length = null;

            if (preg_match('/^(\w+)\((\d+)\)/', $type, $m)) {
                $type = $m[1];
                $length = (int) $m[2];
            }

            $columns[] = [
                'name' => $row->name,
                'type' => $this->parseColumnType($type),
                'raw_type' => $row->type,
                'nullable' => !$row->notnull,
                'default' => $row->dflt_value,
                'auto_increment' => false,
                'unsigned' => false,
                'length' => $length,
                'precision' => null,
                'scale' => null,
                'comment' => '',
                'primary' => (bool) $row->pk,
            ];
        }

        return $columns;
    }

    public function getForeignKeys($table)
    {
        return [];
    }

    protected function parseColumnType($type)
    {
        $type = strtolower($type);

        if (in_array($type, ['int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint'])) {
            return 'int';
        }
        if (in_array($type, ['varchar', 'char', 'text', 'clob'])) {
            return 'text';
        }
        if (in_array($type, ['real', 'double', 'float', 'numeric', 'decimal'])) {
            return 'decimal';
        }
        if (in_array($type, ['blob'])) {
            return 'binary';
        }
        if (in_array($type, ['date', 'datetime', 'timestamp'])) {
            return 'datetime';
        }

        return $type;
    }
}
