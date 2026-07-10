<?php

namespace Adereksisusanto\Laravel\Generator\Tests;

use Adereksisusanto\Laravel\Generator\Generators\MigrationGenerator;

class MigrationGeneratorTest extends TestCase
{
    /** @test */
    public function it_resolves_mysql_column_types_to_schema_methods()
    {
        $generator = $this->makeGenerator();

        $tests = [
            ['type' => 'int', 'expected' => 'integer'],
            ['type' => 'bigint', 'expected' => 'bigInteger'],
            ['type' => 'varchar', 'expected' => 'string'],
            ['type' => 'text', 'expected' => 'text'],
            ['type' => 'decimal', 'expected' => 'decimal'],
            ['type' => 'boolean', 'expected' => 'boolean'],
            ['type' => 'json', 'expected' => 'json'],
            ['type' => 'datetime', 'expected' => 'dateTime'],
            ['type' => 'date', 'expected' => 'date'],
            ['type' => 'timestamp', 'expected' => 'timestamp'],
        ];

        foreach ($tests as $test) {
            $column = ['type' => $test['type'], 'auto_increment' => false, 'primary' => false];
            $result = $this->invokeMethod($generator, 'resolveSchemaMethod', [$column]);
            $this->assertEquals($test['expected'], $result, "Type {$test['type']} should map to {$test['expected']}");
        }
    }

    /** @test */
    public function it_builds_column_line_for_integer()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'views',
            'type' => 'int',
            'nullable' => false,
            'unsigned' => true,
            'auto_increment' => false,
            'primary' => false,
            'default' => 0,
            'length' => null,
            'precision' => null,
            'scale' => null,
            'comment' => '',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertStringContainsString('integer', $result);
        $this->assertStringContainsString('views', $result);
    }

    /** @test */
    public function it_builds_column_line_with_nullable()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'bio',
            'type' => 'text',
            'nullable' => true,
            'unsigned' => false,
            'auto_increment' => false,
            'primary' => false,
            'default' => null,
            'length' => null,
            'precision' => null,
            'scale' => null,
            'comment' => '',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertStringContainsString('->nullable()', $result);
    }

    /** @test */
    public function it_skips_timestamps_and_soft_deletes_columns()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'created_at', 'type' => 'timestamp', 'nullable' => true,
            'unsigned' => false, 'auto_increment' => false, 'primary' => false,
            'default' => null, 'length' => null, 'precision' => null,
            'scale' => null, 'comment' => '',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertNull($result);
    }

    /** @test */
    public function it_resolves_auto_increment_primary_as_increments()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'id', 'type' => 'int', 'nullable' => false,
            'unsigned' => true, 'auto_increment' => true, 'primary' => true,
            'default' => null, 'length' => null, 'precision' => null,
            'scale' => null, 'comment' => '',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertStringContainsString('increments', $result);
    }

    /** @test */
    public function it_builds_column_line_with_unsigned()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'count', 'type' => 'int', 'nullable' => false,
            'unsigned' => true, 'auto_increment' => false, 'primary' => false,
            'default' => null, 'length' => null, 'precision' => null,
            'scale' => null, 'comment' => '',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertStringContainsString('->unsigned()', $result);
    }

    /** @test */
    public function it_builds_column_line_with_use_current_for_timestamp_default()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'created_at', 'type' => 'timestamp', 'nullable' => false,
            'unsigned' => false, 'auto_increment' => false, 'primary' => false,
            'default' => 'CURRENT_TIMESTAMP', 'length' => null, 'precision' => null,
            'scale' => null, 'comment' => '',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertNull($result);
    }

    /** @test */
    public function it_builds_column_line_with_default_value()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'status', 'type' => 'varchar', 'nullable' => false,
            'unsigned' => false, 'auto_increment' => false, 'primary' => false,
            'default' => 'active', 'length' => 20, 'precision' => null,
            'scale' => null, 'comment' => '',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertStringContainsString("->default('active')", $result);
    }

    /** @test */
    public function it_builds_column_line_with_numeric_default()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'priority', 'type' => 'int', 'nullable' => false,
            'unsigned' => false, 'auto_increment' => false, 'primary' => false,
            'default' => 0, 'length' => null, 'precision' => null,
            'scale' => null, 'comment' => '',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertStringContainsString('->default(0)', $result);
    }

    /** @test */
    public function it_builds_column_line_with_comment()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'notes', 'type' => 'text', 'nullable' => true,
            'unsigned' => false, 'auto_increment' => false, 'primary' => false,
            'default' => null, 'length' => null, 'precision' => null,
            'scale' => null, 'comment' => 'User notes',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertStringContainsString("->comment('User notes')", $result);
    }

    /** @test */
    public function it_builds_enum_column()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'role', 'type' => 'enum', 'nullable' => false,
            'unsigned' => false, 'auto_increment' => false, 'primary' => false,
            'default' => null, 'length' => null, 'precision' => null,
            'scale' => null, 'comment' => '',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertStringContainsString("->enum('role', [])", $result);
    }

    /** @test */
    public function it_resolves_unknown_type_to_string()
    {
        $generator = $this->makeGenerator();

        $column = ['type' => 'unknown_type', 'auto_increment' => false, 'primary' => false];
        $result = $this->invokeMethod($generator, 'resolveSchemaMethod', [$column]);

        $this->assertEquals('string', $result);
    }

    /** @test */
    public function it_supports_length_for_string_types()
    {
        $generator = $this->makeGenerator();

        $this->assertTrue($this->invokeMethod($generator, 'supportsLength', ['string']));
        $this->assertTrue($this->invokeMethod($generator, 'supportsLength', ['integer']));
        $this->assertFalse($this->invokeMethod($generator, 'supportsLength', ['text']));
        $this->assertFalse($this->invokeMethod($generator, 'supportsLength', ['decimal']));
    }

    /** @test */
    public function it_builds_schema_with_timestamps_and_soft_deletes()
    {
        $generator = $this->makeGenerator();

        $columns = [
            [
                'name' => 'id', 'type' => 'int', 'nullable' => false,
                'unsigned' => true, 'auto_increment' => true, 'primary' => true,
                'default' => null, 'length' => null, 'precision' => null,
                'scale' => null, 'comment' => '',
            ],
            [
                'name' => 'title', 'type' => 'varchar', 'nullable' => false,
                'unsigned' => false, 'auto_increment' => false, 'primary' => false,
                'default' => null, 'length' => 255, 'precision' => null,
                'scale' => null, 'comment' => '',
            ],
            [
                'name' => 'created_at', 'type' => 'timestamp', 'nullable' => true,
                'unsigned' => false, 'auto_increment' => false, 'primary' => false,
                'default' => null, 'length' => null, 'precision' => null,
                'scale' => null, 'comment' => '',
            ],
            [
                'name' => 'updated_at', 'type' => 'timestamp', 'nullable' => true,
                'unsigned' => false, 'auto_increment' => false, 'primary' => false,
                'default' => null, 'length' => null, 'precision' => null,
                'scale' => null, 'comment' => '',
            ],
            [
                'name' => 'deleted_at', 'type' => 'timestamp', 'nullable' => true,
                'unsigned' => false, 'auto_increment' => false, 'primary' => false,
                'default' => null, 'length' => null, 'precision' => null,
                'scale' => null, 'comment' => '',
            ],
        ];

        $result = $this->invokeMethod($generator, 'buildSchema', [$columns, 'posts']);

        $this->assertStringContainsString('$table->increments', $result);
        $this->assertStringContainsString('$table->string', $result);
        $this->assertStringContainsString('$table->timestamps()', $result);
        $this->assertStringContainsString('$table->softDeletes()', $result);
    }

    /** @test */
    public function it_builds_big_increments_for_bigint_primary()
    {
        $generator = $this->makeGenerator();

        $column = [
            'name' => 'id', 'type' => 'bigint', 'nullable' => false,
            'unsigned' => true, 'auto_increment' => true, 'primary' => true,
            'default' => null, 'length' => null, 'precision' => null,
            'scale' => null, 'comment' => '',
        ];

        $result = $this->invokeMethod($generator, 'buildColumnLine', [$column]);

        $this->assertStringContainsString('bigIncrements', $result);
    }

    /** @test */
    public function it_has_generate_single_method()
    {
        $generator = $this->makeGenerator();

        $this->assertTrue(method_exists($generator, 'generateSingle'));
    }

    protected function makeGenerator()
    {
        return new MigrationGenerator();
    }

    protected function invokeMethod($object, $method, array $parameters = [])
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $parameters);
    }
}
