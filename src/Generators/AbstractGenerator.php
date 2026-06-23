<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud\Generators;

use Mostafizurmaruf\BingoCrud\StubRenderer;
use Illuminate\Filesystem\Filesystem;

/**
 * Shared file-writing helpers for the concrete generators.
 */
abstract class AbstractGenerator implements Generator
{
    public function __construct(
        protected readonly Filesystem $files,
        protected readonly StubRenderer $stubs,
    ) {
    }

    /**
     * Write a file, honouring the --force flag and never silently
     * clobbering existing application code.
     *
     * @return array{path: string, action: string}
     */
    protected function writeFile(string $path, string $contents, bool $force): array
    {
        $relative = $this->relativePath($path);

        if ($this->files->exists($path) && ! $force) {
            return ['path' => $relative, 'action' => 'skipped'];
        }

        $action = $this->files->exists($path) ? 'overwritten' : 'created';

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);

        return ['path' => $relative, 'action' => $action];
    }

    protected function relativePath(string $path): string
    {
        $base = function_exists('base_path') ? base_path() : getcwd();

        return ltrim(str_replace([$base, '\\'], ['', '/'], $path), '/');
    }

    protected function indent(string $value, int $spaces): string
    {
        $pad = str_repeat(' ', $spaces);

        return implode("\n", array_map(
            static fn (string $line): string => $line === '' ? '' : $pad . $line,
            explode("\n", $value),
        ));
    }
}
