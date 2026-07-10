<?php

namespace Adereksisusanto\Laravel\Generator\Tests;

use Adereksisusanto\Laravel\Generator\Database\MySqlDriver;
use Adereksisusanto\Laravel\Generator\Database\PostgresDriver;
use Adereksisusanto\Laravel\Generator\Database\SqliteDriver;
use Adereksisusanto\Laravel\Generator\Database\SqlSrvDriver;

class DatabaseSchemaTest extends TestCase
{
    /** @test */
    public function it_parses_mysql_varchar_type()
    {
        $driver = $this->makeMySqlDriver();

        $result = $this->invokeMethod($driver, 'parseColumnType', ['varchar(255)']);

        $this->assertEquals('varchar', $result);
    }

    /** @test */
    public function it_parses_mysql_int_type()
    {
        $driver = $this->makeMySqlDriver();

        $this->assertEquals('int', $this->invokeMethod($driver, 'parseColumnType', ['int']));
        $this->assertEquals('int', $this->invokeMethod($driver, 'parseColumnType', ['int(11)']));
        $this->assertEquals('bigint', $this->invokeMethod($driver, 'parseColumnType', ['bigint(20) unsigned']));
        $this->assertEquals('tinyint', $this->invokeMethod($driver, 'parseColumnType', ['tinyint(1)']));
    }

    /** @test */
    public function it_parses_mysql_decimal_type()
    {
        $driver = $this->makeMySqlDriver();

        $result = $this->invokeMethod($driver, 'parseColumnType', ['decimal(8,2)']);

        $this->assertEquals('decimal', $result);
    }

    /** @test */
    public function it_parses_mysql_datetime_types()
    {
        $driver = $this->makeMySqlDriver();

        $this->assertEquals('datetime', $this->invokeMethod($driver, 'parseColumnType', ['datetime']));
        $this->assertEquals('timestamp', $this->invokeMethod($driver, 'parseColumnType', ['timestamp']));
        $this->assertEquals('date', $this->invokeMethod($driver, 'parseColumnType', ['date']));
    }

    /** @test */
    public function it_parses_mysql_enum_type()
    {
        $driver = $this->makeMySqlDriver();

        $result = $this->invokeMethod($driver, 'parseColumnType', ["enum('active','inactive')"]);

        $this->assertEquals('enum', $result);
    }

    /** @test */
    public function it_parses_mysql_json_and_blob_types()
    {
        $driver = $this->makeMySqlDriver();

        $this->assertEquals('json', $this->invokeMethod($driver, 'parseColumnType', ['json']));
        $this->assertEquals('binary', $this->invokeMethod($driver, 'parseColumnType', ['binary']));
        $this->assertEquals('blob', $this->invokeMethod($driver, 'parseColumnType', ['blob']));
    }

    /** @test */
    public function it_parses_postgres_types()
    {
        $driver = $this->makePostgresDriver();

        $this->assertEquals('varchar', $this->invokeMethod($driver, 'parseColumnType', ['character varying']));
        $this->assertEquals('char', $this->invokeMethod($driver, 'parseColumnType', ['character']));
        $this->assertEquals('int', $this->invokeMethod($driver, 'parseColumnType', ['integer']));
        $this->assertEquals('bigint', $this->invokeMethod($driver, 'parseColumnType', ['bigint']));
        $this->assertEquals('decimal', $this->invokeMethod($driver, 'parseColumnType', ['numeric']));
        $this->assertEquals('double', $this->invokeMethod($driver, 'parseColumnType', ['double precision']));
        $this->assertEquals('float', $this->invokeMethod($driver, 'parseColumnType', ['real']));
        $this->assertEquals('datetime', $this->invokeMethod($driver, 'parseColumnType', ['timestamp without time zone']));
        $this->assertEquals('text', $this->invokeMethod($driver, 'parseColumnType', ['text']));
    }

    /** @test */
    public function it_parses_sqlite_types()
    {
        $driver = $this->makeSqliteDriver();

        $this->assertEquals('int', $this->invokeMethod($driver, 'parseColumnType', ['integer']));
        $this->assertEquals('int', $this->invokeMethod($driver, 'parseColumnType', ['bigint']));
        $this->assertEquals('text', $this->invokeMethod($driver, 'parseColumnType', ['varchar']));
        $this->assertEquals('text', $this->invokeMethod($driver, 'parseColumnType', ['text']));
        $this->assertEquals('decimal', $this->invokeMethod($driver, 'parseColumnType', ['real']));
        $this->assertEquals('decimal', $this->invokeMethod($driver, 'parseColumnType', ['decimal']));
        $this->assertEquals('datetime', $this->invokeMethod($driver, 'parseColumnType', ['datetime']));
        $this->assertEquals('binary', $this->invokeMethod($driver, 'parseColumnType', ['blob']));
    }

    /** @test */
    public function it_parses_sql_server_types()
    {
        $driver = $this->makeSqlSrvDriver();

        $this->assertEquals('varchar', $this->invokeMethod($driver, 'parseColumnType', ['nvarchar']));
        $this->assertEquals('char', $this->invokeMethod($driver, 'parseColumnType', ['nchar']));
        $this->assertEquals('text', $this->invokeMethod($driver, 'parseColumnType', ['ntext']));
        $this->assertEquals('int', $this->invokeMethod($driver, 'parseColumnType', ['int']));
        $this->assertEquals('bigint', $this->invokeMethod($driver, 'parseColumnType', ['bigint']));
        $this->assertEquals('decimal', $this->invokeMethod($driver, 'parseColumnType', ['decimal']));
        $this->assertEquals('double', $this->invokeMethod($driver, 'parseColumnType', ['float']));
        $this->assertEquals('float', $this->invokeMethod($driver, 'parseColumnType', ['real']));
        $this->assertEquals('datetime', $this->invokeMethod($driver, 'parseColumnType', ['datetime2']));
        $this->assertEquals('tinyint', $this->invokeMethod($driver, 'parseColumnType', ['bit']));
    }

    protected function makeMySqlDriver()
    {
        return new MySqlDriver('mysql');
    }

    protected function makePostgresDriver()
    {
        return new PostgresDriver('pgsql');
    }

    protected function makeSqliteDriver()
    {
        return new SqliteDriver('sqlite');
    }

    protected function makeSqlSrvDriver()
    {
        return new SqlSrvDriver('sqlsrv');
    }

    protected function invokeMethod($object, $method, array $parameters = [])
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $parameters);
    }
}
