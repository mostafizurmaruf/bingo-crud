<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud\Generators;

use Mostafizurmaruf\BingoCrud\CrudNames;
use Mostafizurmaruf\BingoCrud\Field;

final class RequestGenerator extends AbstractGenerator
{
    public function generate(CrudNames $names, array $fields, bool $force): array
    {
        $hasUnique = $this->hasUnique($fields);

        $contents = $this->stubs->render('request.stub', [
            'REQUEST' => $names->request,
            'RULE_IMPORT' => $hasUnique ? "use Illuminate\\Validation\\Rule;\n" : '',
            'KEY_LOOKUP' => $hasUnique ? $this->keyLookup($names) : '',
            'RULES' => $this->buildRules($names, $fields),
        ]);

        $path = app_path('Http/Requests/' . $names->request . '.php');

        return [$this->writeFile($path, $contents, $force)];
    }

    /**
     * @param  list<Field>  $fields
     */
    private function hasUnique(array $fields): bool
    {
        foreach ($fields as $field) {
            if ($field->unique) {
                return true;
            }
        }

        return false;
    }

    private function keyLookup(CrudNames $names): string
    {
        return "        \$id = \$this->route('{$names->modelVariable}')?->getKey();\n\n";
    }

    /**
     * @param  list<Field>  $fields
     */
    private function buildRules(CrudNames $names, array $fields): string
    {
        $lines = [];

        foreach ($fields as $field) {
            $rules = $this->rulesFor($names, $field);
            $lines[] = "            '{$field->name}' => [" . implode(', ', $rules) . '],';
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function rulesFor(CrudNames $names, Field $field): array
    {
        $rules = [];

        // Presence rule. A checkbox always submits a value thanks to the
        // hidden companion input, so booleans can stay required too.
        $rules[] = $field->nullable ? "'nullable'" : "'required'";

        foreach ($field->definition()['validation'] as $rule) {
            if (str_starts_with($rule, 'max:') && $field->length() !== null) {
                $rule = 'max:' . $field->length();
            }

            $rules[] = "'{$rule}'";
        }

        if ($field->isSelect() && $field->options() !== []) {
            $rules[] = "'in:" . implode(',', $field->options()) . "'";
        }

        if ($field->unique) {
            $rules[] = "Rule::unique('{$names->table}', '{$field->name}')->ignore(\$id)";
        }

        return $rules;
    }
}
