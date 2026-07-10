<?php

namespace Adereksisusanto\Laravel\Generator\Tests;

use Adereksisusanto\Laravel\Generator\Commands\GenerateModelCommand;
use Adereksisusanto\Laravel\Generator\Generators\MigrationGenerator;
use Adereksisusanto\Laravel\Generator\Generators\ModelGenerator;
use Adereksisusanto\Laravel\Generator\Generators\SeederGenerator;

class GenerateModelCommandTest extends TestCase
{
    /** @test */
    public function it_has_migration_and_seeder_options_in_signature()
    {
        $generator = $this->createMock(ModelGenerator::class);
        $migrationGenerator = $this->createMock(MigrationGenerator::class);
        $seederGenerator = $this->createMock(SeederGenerator::class);

        $reflection = new \ReflectionClass(GenerateModelCommand::class);
        $property = $reflection->getProperty('signature');
        $property->setAccessible(true);

        $command = new GenerateModelCommand($generator, $migrationGenerator, $seederGenerator);
        $signature = $property->getValue($command);

        $this->assertNotFalse(strpos($signature, '{--m|migration'), 'Signature should contain --migration option');
        $this->assertNotFalse(strpos($signature, '{--s|seeder'), 'Signature should contain --seeder option');
    }

    /** @test */
    public function it_accepts_three_generators_in_constructor()
    {
        $generator = $this->createMock(ModelGenerator::class);
        $migrationGenerator = $this->createMock(MigrationGenerator::class);
        $seederGenerator = $this->createMock(SeederGenerator::class);

        $command = new GenerateModelCommand($generator, $migrationGenerator, $seederGenerator);

        $reflection = new \ReflectionClass($command);
        $this->assertNotNull($reflection->getProperty('generator'));
        $this->assertNotNull($reflection->getProperty('migrationGenerator'));
        $this->assertNotNull($reflection->getProperty('seederGenerator'));
    }

    /** @test */
    public function it_stores_generators_from_constructor()
    {
        $generator = $this->createMock(ModelGenerator::class);
        $migrationGenerator = $this->createMock(MigrationGenerator::class);
        $seederGenerator = $this->createMock(SeederGenerator::class);

        $command = new GenerateModelCommand($generator, $migrationGenerator, $seederGenerator);

        $reflection = new \ReflectionClass($command);
        $genProp = $reflection->getProperty('generator');
        $genProp->setAccessible(true);
        $this->assertSame($generator, $genProp->getValue($command));

        $migProp = $reflection->getProperty('migrationGenerator');
        $migProp->setAccessible(true);
        $this->assertSame($migrationGenerator, $migProp->getValue($command));

        $seedProp = $reflection->getProperty('seederGenerator');
        $seedProp->setAccessible(true);
        $this->assertSame($seederGenerator, $seedProp->getValue($command));
    }
}
