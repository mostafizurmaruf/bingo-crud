<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud\Generators;

use Mostafizurmaruf\BingoCrud\CrudNames;
use Mostafizurmaruf\BingoCrud\Field;

final class ViewGenerator extends AbstractGenerator
{
    public function generate(CrudNames $names, array $fields, bool $force): array
    {
        $results = [];

        $viewDir = resource_path('views/' . $names->viewFolder);

        $shared = [
            'MODEL' => $names->model,
            'MODEL_VARIABLE' => $names->modelVariable,
            'PLURAL_VARIABLE' => $names->pluralVariable,
            'VIEW_FOLDER' => $names->viewFolder,
            'ROUTE' => $names->routeName,
            'TITLE' => $names->title,
            'TITLE_PLURAL' => $names->titlePlural,
        ];

        $results[] = $this->writeFile(
            $viewDir . '/index.blade.php',
            $this->stubs->render('views/index.blade.stub', $shared + [
                'TABLE_HEADERS' => $this->tableHeaders($fields),
                'TABLE_ROWS' => $this->tableRows($names, $fields),
            ]),
            $force,
        );

        $results[] = $this->writeFile(
            $viewDir . '/_form.blade.php',
            $this->stubs->render('views/form.blade.stub', $shared + [
                'FORM_FIELDS' => $this->formFields($names, $fields),
            ]),
            $force,
        );

        $results[] = $this->writeFile(
            $viewDir . '/create.blade.php',
            $this->stubs->render('views/create.blade.stub', $shared),
            $force,
        );

        $results[] = $this->writeFile(
            $viewDir . '/edit.blade.php',
            $this->stubs->render('views/edit.blade.stub', $shared),
            $force,
        );

        $results[] = $this->writeFile(
            $viewDir . '/show.blade.php',
            $this->stubs->render('views/show.blade.stub', $shared + [
                'SHOW_ROWS' => $this->showRows($names, $fields),
            ]),
            $force,
        );

        // Provide a layout the generated views can extend, but never
        // overwrite a layout the application already ships with.
        $layoutPath = resource_path('views/layouts/app.blade.php');

        if (! $this->files->exists($layoutPath)) {
            $results[] = $this->writeFile(
                $layoutPath,
                $this->stubs->render('views/layout.blade.stub', []),
                $force,
            );
        }

        return $results;
    }

    /**
     * @param  list<Field>  $fields
     */
    private function tableHeaders(array $fields): string
    {
        $lines = array_map(
            static fn (Field $field): string => "                    <th>{$field->label()}</th>",
            $this->displayFields($fields),
        );

        return implode("\n", $lines);
    }

    /**
     * @param  list<Field>  $fields
     */
    private function tableRows(CrudNames $names, array $fields): string
    {
        $variable = '$' . $names->modelVariable;
        $lines = [];

        foreach ($this->displayFields($fields) as $field) {
            $lines[] = '                        <td>' . $this->cellValue($variable, $field) . '</td>';
        }

        return implode("\n", $lines);
    }

    private function cellValue(string $variable, Field $field): string
    {
        $accessor = $variable . '->' . $field->name;

        if ($field->isBoolean()) {
            return '{{ ' . $accessor . " ? 'Yes' : 'No' }}";
        }

        if ($field->cast() === 'array') {
            return '{{ \Illuminate\Support\Str::limit(json_encode(' . $accessor . '), 50) }}';
        }

        return '{{ \Illuminate\Support\Str::limit((string) ' . $accessor . ', 50) }}';
    }

    /**
     * @param  list<Field>  $fields
     */
    private function showRows(CrudNames $names, array $fields): string
    {
        $variable = '$' . $names->modelVariable;
        $lines = [];

        foreach ($fields as $field) {
            $accessor = $variable . '->' . $field->name;

            $value = match (true) {
                $field->isBoolean() => '{{ ' . $accessor . " ? 'Yes' : 'No' }}",
                $field->cast() === 'array' => '<pre class="mb-0">{{ json_encode(' . $accessor . ', JSON_PRETTY_PRINT) }}</pre>',
                default => '{{ ' . $accessor . ' }}',
            };

            $lines[] = '        <dt class="col-sm-3">' . $field->label() . '</dt>';
            $lines[] = '        <dd class="col-sm-9">' . $value . '</dd>';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<Field>  $fields
     */
    private function formFields(CrudNames $names, array $fields): string
    {
        $variable = '$' . $names->modelVariable;
        $blocks = [];

        foreach ($fields as $field) {
            $blocks[] = $this->formField($variable, $field);
        }

        return implode("\n\n", $blocks);
    }

    private function formField(string $variable, Field $field): string
    {
        $accessor = "{$variable}->{$field->name}";

        // Array-cast attributes (json) must be serialised before they reach the form.
        $current = $field->cast() === 'array'
            ? "is_array({$accessor}) ? json_encode({$accessor}, JSON_PRETTY_PRINT) : {$accessor}"
            : $accessor;

        $old = "old('{$field->name}', {$current})";

        return match (true) {
            $field->isBoolean() => $this->checkboxField($field, $variable),
            $field->isSelect() => $this->selectField($field, $old),
            $field->isTextarea() => $this->textareaField($field, $old),
            default => $this->inputField($field, $old),
        };
    }

    private function inputField(Field $field, string $old): string
    {
        $value = $field->isHidden() ? '' : ' value="{{ ' . $old . ' }}"';

        return <<<BLADE
            <div class="mb-3">
                <label for="{$field->name}" class="form-label">{$field->label()}</label>
                <input type="{$field->inputType()}" id="{$field->name}" name="{$field->name}"{$value}
                       class="form-control @error('{$field->name}') is-invalid @enderror">
                @error('{$field->name}')
                    <div class="invalid-feedback">{{ \$message }}</div>
                @enderror
            </div>
        BLADE;
    }

    private function textareaField(Field $field, string $old): string
    {
        return <<<BLADE
            <div class="mb-3">
                <label for="{$field->name}" class="form-label">{$field->label()}</label>
                <textarea id="{$field->name}" name="{$field->name}" rows="4"
                          class="form-control @error('{$field->name}') is-invalid @enderror">{{ {$old} }}</textarea>
                @error('{$field->name}')
                    <div class="invalid-feedback">{{ \$message }}</div>
                @enderror
            </div>
        BLADE;
    }

    private function selectField(Field $field, string $old): string
    {
        $options = "['" . implode("', '", $field->options()) . "']";

        return <<<BLADE
            <div class="mb-3">
                <label for="{$field->name}" class="form-label">{$field->label()}</label>
                <select id="{$field->name}" name="{$field->name}"
                        class="form-select @error('{$field->name}') is-invalid @enderror">
                    <option value="">— Select —</option>
                    @foreach ({$options} as \$option)
                        <option value="{{ \$option }}" @selected((string) {$old} === \$option)>{{ ucfirst(\$option) }}</option>
                    @endforeach
                </select>
                @error('{$field->name}')
                    <div class="invalid-feedback">{{ \$message }}</div>
                @enderror
            </div>
        BLADE;
    }

    private function checkboxField(Field $field, string $variable): string
    {
        $old = "old('{$field->name}', {$variable}->{$field->name})";

        return <<<BLADE
            <div class="mb-3">
                <div class="form-check">
                    <input type="hidden" name="{$field->name}" value="0">
                    <input type="checkbox" id="{$field->name}" name="{$field->name}" value="1"
                           class="form-check-input @error('{$field->name}') is-invalid @enderror" @checked({$old})>
                    <label for="{$field->name}" class="form-check-label">{$field->label()}</label>
                </div>
                @error('{$field->name}')
                    <div class="invalid-feedback d-block">{{ \$message }}</div>
                @enderror
            </div>
        BLADE;
    }

    /**
     * Limit the index table to a sensible number of columns.
     *
     * @param  list<Field>  $fields
     * @return list<Field>
     */
    private function displayFields(array $fields): array
    {
        $visible = array_values(array_filter(
            $fields,
            static fn (Field $field): bool => ! $field->isHidden() && ! $field->isTextarea(),
        ));

        return array_slice($visible === [] ? $fields : $visible, 0, 5);
    }
}
