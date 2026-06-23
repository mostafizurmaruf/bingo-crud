<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud\Console\Commands;

use Mostafizurmaruf\BingoCrud\CrudNames;
use Mostafizurmaruf\BingoCrud\Generators\ControllerGenerator;
use Mostafizurmaruf\BingoCrud\Generators\Generator;
use Mostafizurmaruf\BingoCrud\Generators\MigrationGenerator;
use Mostafizurmaruf\BingoCrud\Generators\ModelGenerator;
use Mostafizurmaruf\BingoCrud\Generators\RequestGenerator;
use Mostafizurmaruf\BingoCrud\Generators\RouteGenerator;
use Mostafizurmaruf\BingoCrud\Generators\ViewGenerator;
use Mostafizurmaruf\BingoCrud\SchemaParser;
use Mostafizurmaruf\BingoCrud\StubRenderer;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Throwable;

class MakeCrudCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'make:crud
        {name : The model name, e.g. Product}
        {--schema= : Field definitions, e.g. "name:string, price:decimal(8,2):nullable"}
        {--force : Overwrite files that already exist}';

    /**
     * @var string
     */
    protected $description = 'Generate a full CRUD stack (model, migration, request, controller, views and route) from a schema.';

    public function handle(Filesystem $files): int
    {
        try {
            $names = new CrudNames((string) $this->argument('name'));
            $fields = (new SchemaParser())->parse((string) $this->option('schema'));
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        // Prefer stubs published into the host app (so users can customise them);
        // otherwise fall back to the stubs bundled inside this package.
        $published = base_path('stubs/crud');
        $bundled = dirname(__DIR__, 3) . '/stubs/crud';
        $stubPath = $files->isDirectory($published) ? $published : $bundled;

        $stubs = new StubRenderer($files, $stubPath);

        if (! $files->isDirectory($stubs->path(''))) {
            $this->components->error('CRUD stubs are missing. Expected them in [' . $stubPath . '].');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        $this->components->info("Generating CRUD for [{$names->model}].");

        try {
            foreach ($this->generators($files, $stubs) as $generator) {
                foreach ($generator->generate($names, $fields, $force) as $result) {
                    $this->report($result['action'], $result['path']);
                }
            }
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('CRUD generated successfully.');
        $this->line("  Next steps:");
        $this->line("    1. php artisan migrate");
        $this->line("    2. Visit /{$names->routeUri}");

        return self::SUCCESS;
    }

    /**
     * @return list<Generator>
     */
    private function generators(Filesystem $files, StubRenderer $stubs): array
    {
        return [
            new MigrationGenerator($files, $stubs),
            new ModelGenerator($files, $stubs),
            new RequestGenerator($files, $stubs),
            new ControllerGenerator($files, $stubs),
            new ViewGenerator($files, $stubs),
            new RouteGenerator($files, $stubs),
        ];
    }

    private function report(string $action, string $path): void
    {
        $color = match ($action) {
            'created', 'updated' => 'green',
            'overwritten' => 'yellow',
            default => 'gray',
        };

        $this->components->twoColumnDetail(
            "  <fg={$color}>{$action}</>",
            $path,
        );
    }
}
