<?php

namespace Adereksisusanto\Laravel\Generator\Database;

use Illuminate\Support\Facades\DB;

class PostgresDriver implements \Adereksisusanto\Laravel\Generator\Contracts\DriverContract
{
    protected $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function getTables()
    {
        $results = DB::connection($this->connection)
            ->select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname NOT IN ('pg_catalog', 'information_schema')");

        return array_map(function ($row) {
            return $row->tablename;
        }, $results);
    }

    public function getColumnDetails($table)
    {
        $results = DB::connection($this->connection)
            ->select("
                SELECT
                    c.column_name,
                    c.data_type,
                    c.is_nullable,
                    c.column_default,
                    c.character_maximum_length,
                    c.numeric_precision,
                    c.numeric_scale,
                    CASE WHEN pk.column_name IS NOT NULL THEN true ELSE false END as is_primary
                FROM information_schema.COLUMNS c
                LEFT JOIN (
                    SELECT ku.column_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage ku
                        ON tc.constraint_name = ku.constraint_name
                        AND tc.table_schema = ku.table_schema
                    WHERE tc.constraint_type = 'PRIMARY KEY'
                        AND tc.table_name = ?
                ) pk ON c.column_name = pk.column_name
                WHERE c.table_name = ?
                ORDER BY c.ordinal_position
            ", [$table, $table]);

        $columns = [];

        foreach ($results as $row) {
            $columns[] = [
                'name' => $row->column_name,
                'type' => $this->parseColumnType($row->data_type),
                'raw_type' => $row->data_type,
                'nullable' => $row->is_nullable === 'YES',
                'default' => $row->column_default,
                'auto_increment' => $row->column_default && strpos($row->column_default, 'nextval') !== false,
                'unsigned' => false,
                'length' => $row->character_maximum_length,
                'precision' => $row->numeric_precision,
                'scale' => $row->numeric_scale,
                'comment' => '',
                'primary' => (bool) $row->is_primary,
            ];
        }

        return $columns;
    }

    public function getForeignKeys($table)
    {
        $results = DB::connection($this->connection)
            ->select("
                SELECT
                    kcu.column_name,
                    ccu.table_name AS foreign_table_name,
                    ccu.column_name AS foreign_column_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage ccu
                    ON ccu.constraint_name = tc.constraint_name
                    AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                    AND tc.table_name = ?
            ", [$table]);

        $foreignKeys = [];

        foreach ($results as $row) {
            $foreignKeys[] = [
                'column' => $row->column_name,
                'foreign_table' => $row->foreign_table_name,
                'foreign_column' => $row->foreign_column_name,
            ];
        }

        return $foreignKeys;
    }

    protected function parseColumnType($type)
    {
        $type = strtolower($type);

        $map = [
            'character varying' => 'varchar',
            'character' => 'char',
            'integer' => 'int',
            'bigint' => 'bigint',
            'smallint' => 'smallint',
            'numeric' => 'decimal',
            'double precision' => 'double',
            'real' => 'float',
            'timestamp without time zone' => 'datetime',
            'timestamp with time zone' => 'datetime',
            'time without time zone' => 'time',
            'time with time zone' => 'time',
        ];

        return isset($map[$type]) ? $map[$type] : $type;
    }
}
