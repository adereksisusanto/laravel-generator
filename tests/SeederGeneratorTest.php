<?php

namespace Adereksisusanto\Laravel\Generator\Tests;

use Adereksisusanto\Laravel\Generator\Generators\SeederGenerator;

class SeederGeneratorTest extends TestCase
{
    /** @test */
    public function it_formats_null_value()
    {
        $generator = $this->makeGenerator();

        $result = $this->invokeMethod($generator, 'formatValue', [null]);

        $this->assertEquals('null', $result);
    }

    /** @test */
    public function it_formats_numeric_value()
    {
        $generator = $this->makeGenerator();

        $this->assertEquals('42', $this->invokeMethod($generator, 'formatValue', [42]));
        $this->assertEquals('3.14', $this->invokeMethod($generator, 'formatValue', [3.14]));
    }

    /** @test */
    public function it_formats_string_value_with_single_quotes()
    {
        $generator = $this->makeGenerator();

        $result = $this->invokeMethod($generator, 'formatValue', ['hello']);

        $this->assertEquals("'hello'", $result);
    }

    /** @test */
    public function it_escapes_single_quotes_in_strings()
    {
        $generator = $this->makeGenerator();

        $result = $this->invokeMethod($generator, 'formatValue', ["it's"]);

        $this->assertEquals("'it\\'s'", $result);
    }

    /** @test */
    public function it_formats_boolean_values()
    {
        $generator = $this->makeGenerator();

        $this->assertEquals('true', $this->invokeMethod($generator, 'formatValue', [true]));
        $this->assertEquals('false', $this->invokeMethod($generator, 'formatValue', [false]));
    }

    /** @test */
    public function it_escapes_newlines_in_strings()
    {
        $generator = $this->makeGenerator();

        $result = $this->invokeMethod($generator, 'formatValue', ["line1\nline2"]);

        $this->assertEquals("'line1\\nline2'", $result);
    }

    /** @test */
    public function it_returns_empty_array_for_no_rows()
    {
        $generator = $this->makeGenerator();

        $result = $this->invokeMethod($generator, 'buildDataArray', [[]]);

        $this->assertEquals('[]', $result);
    }

    /** @test */
    public function it_builds_data_array_from_rows()
    {
        $generator = $this->makeGenerator();

        $rows = [
            (object) ['id' => 1, 'name' => 'Alice'],
            (object) ['id' => 2, 'name' => 'Bob'],
        ];

        $result = $this->invokeMethod($generator, 'buildDataArray', [$rows]);

        $this->assertStringContainsString("'id' => 1", $result);
        $this->assertStringContainsString("'name' => 'Alice'", $result);
        $this->assertStringContainsString("'id' => 2", $result);
        $this->assertStringContainsString("'name' => 'Bob'", $result);
    }

    protected function makeGenerator()
    {
        return new SeederGenerator;
    }

    protected function invokeMethod($object, $method, array $parameters = [])
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $parameters);
    }
}
