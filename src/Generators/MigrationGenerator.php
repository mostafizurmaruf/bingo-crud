<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud\Generators;

use Mostafizurmaruf\BingoCrud\CrudNames;
use Mostafizurmaruf\BingoCrud\Field;

final class MigrationGenerator extends AbstractGenerator
{
    public function generate(CrudNames $names, array $fields, bool $force): array
    {
        $contents = $this->stubs->render('migration.stub', [
            'TABLE' => $names->table,
            'COLUMNS' => $this->buildColumns($fields),
        ]);

        $fileName = date('Y_m_d_His') . '_create_' . $names->table . '_table.php';
        $path = database_path('migrations/' . $fileName);

        // A migration that creates this table may already exist; never overwrite it.
        if ($this->migrationExists($names->table) && ! $force) {
            return [['path' => 'database/migrations/*_create_' . $names->table . '_table.php', 'action' => 'skipped']];
        }

        return [$this->writeFile($path, $contents, $force)];
    }

    /**
     * @param  list<Field>  $fields
     */
    private function buildColumns(array $fields): string
    {
        $lines = [];

        foreach ($fields as $field) {
            $lines[] = '            ' . $this->columnDefinition($field);
        }

        return implode("\n", $lines);
    }

    private function columnDefinition(Field $field): string
    {
        $definition = '$table->' . $field->migrationMethod() . '(' . $this->columnArguments($field) . ')';

        if ($field->nullable) {
            $definition .= '->nullable()';
        }

        if ($field->unique) {
            $definition .= '->unique()';
        }

        if ($field->index && ! $field->unique) {
            $definition .= '->index()';
        }

        if ($field->default !== null) {
            $definition .= '->default(' . $this->defaultValue($field) . ')';
        }

        return $definition . ';';
    }

    private function columnArguments(Field $field): string
    {
        $name = "'{$field->name}'";

        return match ($field->type) {
            'enum' => $name . ', [' . $this->quotedList($field->arguments) . ']',
            'decimal', 'float', 'double' => $field->arguments === []
                ? $name
                : $name . ', ' . implode(', ', array_map('intval', $field->arguments)),
            'string', 'char' => $field->length() !== null
                ? $name . ', ' . $field->length()
                : $name,
            default => $name,
        };
    }

    private function defaultValue(Field $field): string
    {
        $value = (string) $field->default;

        if ($field->isBoolean()) {
            return in_array(strtolower($value), ['1', 'true'], true) ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return $value;
        }

        return "'" . str_replace("'", "\\'", $value) . "'";
    }

    /**
     * @param  list<string>  $values
     */
    private function quotedList(array $values): string
    {
        return implode(', ', array_map(
            static fn (string $value): string => "'" . str_replace("'", "\\'", $value) . "'",
            $values,
        ));
    }

    private function migrationExists(string $table): bool
    {
        foreach ($this->files->glob(database_path('migrations/*.php')) as $existing) {
            if (str_contains(basename($existing), 'create_' . $table . '_table')) {
                return true;
            }
        }

        return false;
    }
}
