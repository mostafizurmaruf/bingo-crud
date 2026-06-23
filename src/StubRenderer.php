<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * Loads a stub file and replaces its {{ UPPERCASE }} placeholders.
 *
 * Uppercase placeholder keys are used so they never collide with the
 * Blade `{{ $variable }}` echoes that live inside the view stubs.
 */
final class StubRenderer
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $stubPath,
    ) {
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function render(string $stub, array $replacements): string
    {
        $path = $this->path($stub);

        if (! $this->files->exists($path)) {
            throw new RuntimeException("Stub [{$stub}] not found at [{$path}].");
        }

        $contents = $this->files->get($path);

        foreach ($replacements as $key => $value) {
            $contents = str_replace(
                ['{{ ' . $key . ' }}', '{{' . $key . '}}'],
                $value,
                $contents,
            );
        }

        return $contents;
    }

    public function path(string $stub): string
    {
        return rtrim($this->stubPath, '/\\') . DIRECTORY_SEPARATOR . ltrim($stub, '/\\');
    }
}
