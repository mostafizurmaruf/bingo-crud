<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud\Generators;

use Mostafizurmaruf\BingoCrud\CrudNames;

final class RouteGenerator extends AbstractGenerator
{
    public function generate(CrudNames $names, array $fields, bool $force): array
    {
        $path = base_path('routes/web.php');

        if (! $this->files->exists($path)) {
            $this->files->put($path, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        }

        $contents = $this->files->get($path);
        $useStatement = "use App\\Http\\Controllers\\{$names->controller};";

        if (str_contains($contents, "Route::resource('{$names->routeName}'")) {
            return [['path' => 'routes/web.php', 'action' => 'skipped']];
        }

        $contents = $this->ensureUseStatement($contents, $names, $useStatement);

        $resourceLine = "Route::resource('{$names->routeName}', {$names->controller}::class);";
        $contents = rtrim($contents, "\n") . "\n" . $resourceLine . "\n";

        $this->files->put($path, $contents);

        return [['path' => 'routes/web.php', 'action' => 'updated']];
    }

    private function ensureUseStatement(string $contents, CrudNames $names, string $useStatement): string
    {
        if (str_contains($contents, $useStatement)) {
            return $contents;
        }

        $lines = preg_split('/\R/', $contents) ?: [];
        $lastUse = -1;

        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'use ')) {
                $lastUse = $index;
            }
        }

        if ($lastUse === -1) {
            // Insert right after the opening "<?php" tag.
            foreach ($lines as $index => $line) {
                if (str_starts_with(trim($line), '<?php')) {
                    $lastUse = $index;

                    break;
                }
            }
        }

        if ($lastUse === -1) {
            return $useStatement . "\n" . $contents;
        }

        array_splice($lines, $lastUse + 1, 0, [$useStatement]);

        return implode("\n", $lines);
    }
}
