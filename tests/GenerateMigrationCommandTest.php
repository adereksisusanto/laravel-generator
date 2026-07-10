<?php

namespace Adereksisusanto\Laravel\Generator\Tests;

use Adereksisusanto\Laravel\Generator\Commands\GenerateMigrationCommand;
use Adereksisusanto\Laravel\Generator\Generators\MigrationGenerator;

class GenerateMigrationCommandTest extends TestCase
{
    /** @test */
    public function it_has_single_option_in_signature()
    {
        $generator = $this->createMock(MigrationGenerator::class);

        $reflection = new \ReflectionClass(GenerateMigrationCommand::class);
        $property = $reflection->getProperty('signature');
        $property->setAccessible(true);

        $command = new GenerateMigrationCommand($generator);
        $signature = $property->getValue($command);

        $this->assertNotFalse(strpos($signature, '{--single'), 'Signature should contain --single option');
    }

    /** @test */
    public function it_has_handle_single_method()
    {
        $generator = $this->createMock(MigrationGenerator::class);
        $command = new GenerateMigrationCommand($generator);

        $this->assertTrue(method_exists($command, 'handleSingle'));
    }
}
