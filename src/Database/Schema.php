<?php

namespace Adereksisusanto\Laravel\Generator\Database;

use Illuminate\Support\Facades\DB;

class Schema
{
    public function getTables($connection = null)
    {
        return $this->driver($connection)->getTables();
    }

    public function getColumnDetails($table, $connection = null)
    {
        return $this->driver($connection)->getColumnDetails($table);
    }

    public function getForeignKeys($table, $connection = null)
    {
        return $this->driver($connection)->getForeignKeys($table);
    }

    protected function driver($connection = null)
    {
        $connection = $connection ?: config('generator.connection');
        $driverName = $this->getDriverName($connection);

        switch ($driverName) {
            case 'mysql':
                return new MySqlDriver($connection);
            case 'pgsql':
                return new PostgresDriver($connection);
            case 'sqlite':
                return new SqliteDriver($connection);
            case 'sqlsrv':
                return new SqlSrvDriver($connection);
            default:
                throw new \RuntimeException("Unsupported database driver: {$driverName}");
        }
    }

    protected function getDriverName($connection)
    {
        /** @var \Illuminate\Database\Connection $conn */
        $conn = DB::connection($connection);
        return $conn->getDriverName();
    }
}
