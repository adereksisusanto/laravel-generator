<?php

namespace Adereksisusanto\Laravel\Generator;

use Adereksisusanto\Laravel\Generator\Commands\GenerateCommand;
use Adereksisusanto\Laravel\Generator\Commands\GenerateMigrationCommand;
use Adereksisusanto\Laravel\Generator\Commands\GenerateModelCommand;
use Adereksisusanto\Laravel\Generator\Commands\GenerateSeederCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
                GenerateModelCommand::class,
                GenerateMigrationCommand::class,
                GenerateSeederCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/generator.php' => config_path('generator.php'),
            ], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/generator.php',
            'generator'
        );

        $config = $this->app->make('config');

        $config->set('generator.paths.model', $config->get('generator.paths.model', app_path('Models')));
        $config->set('generator.paths.seeder', $config->get('generator.paths.seeder', database_path('seeders')));
        $config->set('generator.namespace', $config->get('generator.namespace', 'App\\Models'));
    }
}
