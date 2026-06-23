<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud;

use Illuminate\Support\ServiceProvider;
use Mostafizurmaruf\BingoCrud\Console\Commands\MakeCrudCommand;

class CrudServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            MakeCrudCommand::class,
        ]);

        // Optional: `php artisan vendor:publish --tag=crud-stubs`
        // copies the templates into the host project so they can be customised.
        $this->publishes([
            dirname(__DIR__) . '/stubs/crud' => base_path('stubs/crud'),
        ], 'crud-stubs');
    }
}
