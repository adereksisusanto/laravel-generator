<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Specify the database connection to use for reading table schemas.
    | Set to null to use the default Laravel database connection.
    |
    */
    'connection' => env('GENERATOR_DB_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Table Selection
    |--------------------------------------------------------------------------
    |
    | Define which tables to include or exclude from generation.
    | Leave includes empty to process all tables (except excluded).
    |
    */
    'tables' => [
        'includes' => [],
        'excludes' => [
            'migrations',
            'failed_jobs',
            'password_resets',
            'personal_access_tokens',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Paths
    |--------------------------------------------------------------------------
    |
    | Where to save generated models and migrations.
    | Paths are relative to your Laravel application root.
    | Automatically adjusted for Laravel 7 vs 8 conventions.
    |
    */
    'paths' => [
        'model' => app_path('Models'),
        'migration' => database_path('migrations'),
        'seeder' => database_path('seeders'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | Namespace for generated models.
    | Automatically adjusted for Laravel 7 (App) vs 8+ (App\Models).
    |
    */
    'namespace' => 'App\Models',

    /*
    |--------------------------------------------------------------------------
    | Model Options
    |--------------------------------------------------------------------------
    |
    | - timestamps: Add $timestamps property to model
    | - soft_deletes: Add SoftDeletes trait to model
    | - guarded: Use $guarded instead of $fillable
    | - connection: Add $connection property to model
    | - relationships: Auto-detect foreign keys and generate relationships
    | - accessors: Generate attribute accessors for special column types
    |
    */
    'model' => [
        'timestamps' => true,
        'soft_deletes' => false,
        'guarded' => false,
        'connection' => false,
        'relationships' => true,
        'accessors' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Options
    |--------------------------------------------------------------------------
    |
    | - generate: Enable migration generation
    | - prefix: Date prefix for migration file names (e.g., Y_m_d_His)
    |
    */
    'migration' => [
        'generate' => true,
        'prefix' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder Options
    |--------------------------------------------------------------------------
    |
    | - generate: Enable seeder generation
    | - limit: Max number of records to seed per table (0 = unlimited)
    |
    */
    'seeder' => [
        'generate' => true,
        'limit' => 100,
    ],
];
