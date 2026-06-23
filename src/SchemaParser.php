<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud;

use InvalidArgumentException;

/**
 * Parses a compact schema definition string into a list of {@see Field}
 * value objects.
 *
 * Grammar (whitespace around tokens is ignored):
 *
 *   schema   := field ("," field)*
 *   field    := name ":" type (":" modifier)*
 *   type     := word ( "(" args ")" )?
 *   modifier := "nullable" | "unique" | "index" | "default(" value ")"
 *
 * Example:
 *   "name:string, price:decimal(8,2):nullable, status:enum(draft,published):default(draft)"
 */
final class SchemaParser
{
    private const MODIFIERS = ['nullable', 'unique', 'index'];

    /**
     * @return list<Field>
     */
    public function parse(string $schema): array
    {
        $fields = [];

        foreach ($this->splitTopLevel($schema, ',') as $definition) {
            $definition = trim($definition);

            if ($definition === '') {
                continue;
            }

            $fields[] = $this->parseField($definition);
        }

        if ($fields === []) {
            throw new InvalidArgumentException('The schema does not contain any fields.');
        }

        return $fields;
    }

    private function parseField(string $definition): Field
    {
        $parts = array_map('trim', $this->splitTopLevel($definition, ':'));

        $name = array_shift($parts);
        $typeToken = array_shift($parts) ?? '';

        if ($name === '' || $name === null) {
            throw new InvalidArgumentException("Missing field name in \"{$definition}\".");
        }

        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid field name \"{$name}\".");
        }

        [$type, $arguments] = $this->parseTypeToken($typeToken);

        if (! TypeDefinitions::has($type)) {
            throw new InvalidArgumentException(
                "Unknown field type \"{$type}\" for field \"{$name}\". "
                . 'Supported types: ' . implode(', ', TypeDefinitions::names()) . '.'
            );
        }

        $nullable = false;
        $unique = false;
        $index = false;
        $default = null;

        foreach ($parts as $modifier) {
            $modifier = trim($modifier);

            if ($modifier === '') {
                continue;
            }

            [$modifierName, $modifierArgs] = $this->parseTypeToken($modifier);

            match ($modifierName) {
                'nullable' => $nullable = true,
                'unique' => $unique = true,
                'index' => $index = true,
                'default' => $default = $modifierArgs[0] ?? '',
                default => throw new InvalidArgumentException(
                    "Unknown modifier \"{$modifierName}\" for field \"{$name}\". "
                    . 'Supported modifiers: ' . implode(', ', [...self::MODIFIERS, 'default(value)']) . '.'
                ),
            };
        }

        return new Field(
            name: $name,
            type: $type,
            arguments: $arguments,
            nullable: $nullable,
            unique: $unique,
            index: $index,
            default: $default,
        );
    }

    /**
     * Splits "decimal(8,2)" into ['decimal', ['8', '2']].
     *
     * @return array{0: string, 1: list<string>}
     */
    private function parseTypeToken(string $token): array
    {
        if (! preg_match('/^(?<name>[a-zA-Z_][a-zA-Z0-9_]*)\s*(\((?<args>.*)\))?$/s', trim($token), $matches)) {
            throw new InvalidArgumentException("Unable to parse token \"{$token}\".");
        }

        $name = $matches['name'];
        $arguments = [];

        if (isset($matches['args']) && trim($matches['args']) !== '') {
            $arguments = array_values(array_filter(
                array_map('trim', explode(',', $matches['args'])),
                static fn (string $value): bool => $value !== '',
            ));
        }

        return [$name, $arguments];
    }

    /**
     * Splits a string on a delimiter while ignoring delimiters nested
     * inside parentheses, so "decimal(8,2)" stays intact.
     *
     * @return list<string>
     */
    private function splitTopLevel(string $value, string $delimiter): array
    {
        $segments = [];
        $buffer = '';
        $depth = 0;

        foreach (str_split($value) as $char) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth = max(0, $depth - 1);
            }

            if ($char === $delimiter && $depth === 0) {
                $segments[] = $buffer;
                $buffer = '';

                continue;
            }

            $buffer .= $char;
        }

        $segments[] = $buffer;

        return $segments;
    }
}
