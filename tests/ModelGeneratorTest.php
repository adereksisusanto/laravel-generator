<?php

namespace Adereksisusanto\Laravel\Generator\Tests;

use Adereksisusanto\Laravel\Generator\Generators\ModelGenerator;
use PHPUnit\Framework\Attributes\Test;

class ModelGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_studly_model_name_from_snake_table()
    {
        $generator = $this->makeGenerator();

        $this->assertEquals('User', $this->invokeMethod($generator, 'getModelName', ['users']));
        $this->assertEquals('Post', $this->invokeMethod($generator, 'getModelName', ['posts']));
        $this->assertEquals('BlogPost', $this->invokeMethod($generator, 'getModelName', ['blog_posts']));
        $this->assertEquals('Tag', $this->invokeMethod($generator, 'getModelName', ['tags']));
    }

    #[Test]
    public function it_builds_fillable_from_columns_excluding_primary_and_timestamps()
    {
        $generator = $this->makeGenerator();

        $columns = [
            ['name' => 'id', 'primary' => true, 'auto_increment' => true],
            ['name' => 'name', 'primary' => false, 'auto_increment' => false],
            ['name' => 'email', 'primary' => false, 'auto_increment' => false],
            ['name' => 'created_at', 'primary' => false, 'auto_increment' => false],
            ['name' => 'updated_at', 'primary' => false, 'auto_increment' => false],
        ];

        $result = $this->invokeMethod($generator, 'getFillable', [$columns]);

        $this->assertStringContainsString("'name'", $result);
        $this->assertStringContainsString("'email'", $result);
        $this->assertStringNotContainsString("'id'", $result);
        $this->assertStringNotContainsString("'created_at'", $result);
    }

    #[Test]
    public function it_builds_casts_for_boolean_integer_float_and_datetime()
    {
        $generator = $this->makeGenerator();

        $columns = [
            ['name' => 'is_active', 'type' => 'tinyint', 'primary' => false, 'auto_increment' => false],
            ['name' => 'views', 'type' => 'int', 'primary' => false, 'auto_increment' => false],
            ['name' => 'price', 'type' => 'decimal', 'primary' => false, 'auto_increment' => false],
            ['name' => 'meta', 'type' => 'json', 'primary' => false, 'auto_increment' => false],
            ['name' => 'published_at', 'type' => 'datetime', 'primary' => false, 'auto_increment' => false],
            ['name' => 'id', 'type' => 'bigint', 'primary' => true, 'auto_increment' => true],
        ];

        $result = $this->invokeMethod($generator, 'getCasts', [$columns]);

        $this->assertStringContainsString("'is_active' => 'boolean'", $result);
        $this->assertStringContainsString("'views' => 'integer'", $result);
        $this->assertStringContainsString("'price' => 'float'", $result);
        $this->assertStringContainsString("'meta' => 'array'", $result);
        $this->assertStringContainsString("'published_at' => 'datetime'", $result);
        $this->assertStringNotContainsString("'id'", $result);
    }

    #[Test]
    public function it_detects_soft_delete_trait_when_deleted_at_column_exists()
    {
        $generator = $this->makeGenerator();

        $columns = [
            ['name' => 'deleted_at', 'type' => 'timestamp', 'primary' => false, 'auto_increment' => false],
        ];

        $traits = $this->invokeMethod($generator, 'getTraits', [$columns, []]);

        $this->assertContains('Illuminate\\Database\\Eloquent\\SoftDeletes', $traits);
    }

    #[Test]
    public function it_returns_default_traits_when_no_deleted_at_column()
    {
        $generator = $this->makeGenerator();

        $columns = [
            ['name' => 'name', 'type' => 'varchar', 'primary' => false, 'auto_increment' => false],
        ];

        $traits = $this->invokeMethod($generator, 'getTraits', [$columns, []]);

        $this->assertContains('Illuminate\\Database\\Eloquent\\Factories\\HasFactory', $traits);
        $this->assertCount(1, $traits);
    }

    #[Test]
    public function it_returns_empty_fillable_for_primary_key_only()
    {
        $generator = $this->makeGenerator();

        $columns = [
            ['name' => 'id', 'primary' => true, 'auto_increment' => true],
        ];

        $result = $this->invokeMethod($generator, 'getFillable', [$columns]);

        $this->assertEquals('[]', $result);
    }

    #[Test]
    public function it_hides_password_and_remember_token()
    {
        $generator = $this->makeGenerator();

        $columns = [
            ['name' => 'password', 'type' => 'varchar', 'primary' => false, 'auto_increment' => false],
            ['name' => 'remember_token', 'type' => 'varchar', 'primary' => false, 'auto_increment' => false],
            ['name' => 'email', 'type' => 'varchar', 'primary' => false, 'auto_increment' => false],
        ];

        $result = $this->invokeMethod($generator, 'getHidden', [$columns]);

        $this->assertStringContainsString("'password'", $result);
        $this->assertStringContainsString("'remember_token'", $result);
        $this->assertStringNotContainsString("'email'", $result);
    }

    #[Test]
    public function it_resolves_cast_types_for_date_timestamp_and_binary()
    {
        $generator = $this->makeGenerator();

        $this->assertEquals('date', $this->invokeMethod($generator, 'resolveCastType', [
            ['name' => 'some_date', 'type' => 'date', 'primary' => false, 'auto_increment' => false],
        ]));
        $this->assertEquals('datetime', $this->invokeMethod($generator, 'resolveCastType', [
            ['name' => 'logged_at', 'type' => 'timestamp', 'primary' => false, 'auto_increment' => false],
        ]));
        $this->assertEquals('boolean', $this->invokeMethod($generator, 'resolveCastType', [
            ['name' => 'flag', 'type' => 'binary', 'primary' => false, 'auto_increment' => false],
        ]));
        $this->assertNull($this->invokeMethod($generator, 'resolveCastType', [
            ['name' => 'name', 'type' => 'varchar', 'primary' => false, 'auto_increment' => false],
        ]));
    }

    #[Test]
    public function it_skips_datetime_cast_for_created_at_and_updated_at()
    {
        $generator = $this->makeGenerator();

        $result = $this->invokeMethod($generator, 'resolveCastType', [
            ['name' => 'created_at', 'type' => 'datetime', 'primary' => false, 'auto_increment' => false],
        ]);

        $this->assertNull($result);
    }

    #[Test]
    public function it_builds_use_statements_from_traits_and_relations()
    {
        $generator = $this->makeGenerator();

        $traits = ['Illuminate\\Database\\Eloquent\\SoftDeletes'];
        $relations = <<<'PHP'

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

PHP;

        $result = $this->invokeMethod($generator, 'getUseStatements', [$traits, $relations]);

        $this->assertStringContainsString('use Illuminate\\Database\\Eloquent\\SoftDeletes;', $result);
        $this->assertStringContainsString('use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;', $result);
    }

    #[Test]
    public function it_returns_empty_use_statements_when_no_traits_or_relations()
    {
        $generator = $this->makeGenerator();

        $result = $this->invokeMethod($generator, 'getUseStatements', [[], '']);

        $this->assertEquals('', $result);
    }

    #[Test]
    public function it_builds_trait_uses()
    {
        $generator = $this->makeGenerator();

        $traits = ['Illuminate\\Database\\Eloquent\\SoftDeletes'];
        $result = $this->invokeMethod($generator, 'getTraitUses', [$traits]);

        $this->assertStringContainsString('use SoftDeletes;', $result);
    }

    #[Test]
    public function it_returns_empty_trait_uses_when_no_traits()
    {
        $generator = $this->makeGenerator();

        $result = $this->invokeMethod($generator, 'getTraitUses', [[]]);

        $this->assertEquals('', $result);
    }

    #[Test]
    public function it_removes_consecutive_empty_lines()
    {
        $generator = $this->makeGenerator();

        $input = "line1\n\n\nline2\n\nline3";
        $result = $this->invokeMethod($generator, 'cleanupEmptyLines', [$input]);

        $this->assertEquals("line1\n\nline2\n\nline3", $result);
    }

    #[Test]
    public function it_does_not_alter_content_without_consecutive_empty_lines()
    {
        $generator = $this->makeGenerator();

        $input = "line1\nline2\nline3";
        $result = $this->invokeMethod($generator, 'cleanupEmptyLines', [$input]);

        $this->assertEquals($input, $result);
    }

    protected function makeGenerator()
    {
        return new ModelGenerator;
    }

    protected function invokeMethod($object, $method, array $parameters = [])
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $parameters);
    }
}
