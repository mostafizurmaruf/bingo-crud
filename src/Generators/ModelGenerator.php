<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud\Generators;

use Mostafizurmaruf\BingoCrud\CrudNames;
use Mostafizurmaruf\BingoCrud\Field;

final class ModelGenerator extends AbstractGenerator
{
    public function generate(CrudNames $names, array $fields, bool $force): array
    {
        $contents = $this->stubs->render('model.stub', [
            'MODEL' => $names->model,
            'FILLABLE' => $this->buildFillable($fields),
            'CASTS' => $this->buildCasts($fields),
        ]);

        $path = app_path('Models/' . $names->model . '.php');

        return [$this->writeFile($path, $contents, $force)];
    }

    /**
     * @param  list<Field>  $fields
     */
    private function buildFillable(array $fields): string
    {
        $lines = array_map(
            static fn (Field $field): string => "        '{$field->name}',",
            $fields,
        );

        return implode("\n", $lines);
    }

    /**
     * @param  list<Field>  $fields
     */
    private function buildCasts(array $fields): string
    {
        $entries = [];

        foreach ($fields as $field) {
            $cast = $field->cast();

            if ($cast !== null) {
                $entries[] = "            '{$field->name}' => '{$cast}',";
            }
        }

        if ($entries === []) {
            return '';
        }

        $lines = [
            '',
            '    /**',
            '     * Get the attributes that should be cast.',
            '     *',
            '     * @return array<string, string>',
            '     */',
            '    protected function casts(): array',
            '    {',
            '        return [',
            ...$entries,
            '        ];',
            '    }',
        ];

        return implode("\n", $lines);
    }
}
