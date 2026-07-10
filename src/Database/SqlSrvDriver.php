<?php

namespace Adereksisusanto\Laravel\Generator\Database;

use Illuminate\Support\Facades\DB;

class SqlSrvDriver implements \Adereksisusanto\Laravel\Generator\Contracts\DriverContract
{
    protected $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function getTables()
    {
        $results = DB::connection($this->connection)
            ->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");

        return array_map(function ($row) {
            return $row->TABLE_NAME;
        }, $results);
    }

    public function getColumnDetails($table)
    {
        $results = DB::connection($this->connection)
            ->select("
                SELECT
                    c.COLUMN_NAME,
                    c.DATA_TYPE,
                    c.IS_NULLABLE,
                    c.COLUMN_DEFAULT,
                    c.CHARACTER_MAXIMUM_LENGTH,
                    c.NUMERIC_PRECISION,
                    c.NUMERIC_SCALE,
                    COLUMNPROPERTY(OBJECT_ID(c.TABLE_NAME), c.COLUMN_NAME, 'IsIdentity') as IS_IDENTITY
                FROM INFORMATION_SCHEMA.COLUMNS c
                WHERE c.TABLE_NAME = ?
                ORDER BY c.ORDINAL_POSITION
            ", [$table]);

        $columns = [];

        foreach ($results as $row) {
            $columns[] = [
                'name' => $row->COLUMN_NAME,
                'type' => $this->parseColumnType($row->DATA_TYPE),
                'raw_type' => $row->DATA_TYPE,
                'nullable' => $row->IS_NULLABLE === 'YES',
                'default' => $row->COLUMN_DEFAULT,
                'auto_increment' => (bool) $row->IS_IDENTITY,
                'unsigned' => false,
                'length' => $row->CHARACTER_MAXIMUM_LENGTH,
                'precision' => $row->NUMERIC_PRECISION,
                'scale' => $row->NUMERIC_SCALE,
                'comment' => '',
                'primary' => false,
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

        $map = [
            'nvarchar' => 'varchar',
            'nchar' => 'char',
            'ntext' => 'text',
            'int' => 'int',
            'bigint' => 'bigint',
            'smallint' => 'smallint',
            'tinyint' => 'tinyint',
            'decimal' => 'decimal',
            'float' => 'double',
            'real' => 'float',
            'datetime' => 'datetime',
            'datetime2' => 'datetime',
            'smalldatetime' => 'datetime',
            'bit' => 'tinyint',
            'money' => 'decimal',
            'smallmoney' => 'decimal',
        ];

        return isset($map[$type]) ? $map[$type] : $type;
    }
}
