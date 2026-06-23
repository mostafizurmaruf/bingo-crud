<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud;

use Illuminate\Support\Str;

/**
 * Immutable value object describing a single database/form field that
 * was parsed out of the schema string.
 */
final class Field
{
    /**
     * @param  list<string>  $arguments  Type arguments, e.g. decimal(8,2) => ['8', '2'].
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly array $arguments = [],
        public readonly bool $nullable = false,
        public readonly bool $unique = false,
        public readonly bool $index = false,
        public readonly ?string $default = null,
    ) {
    }

    /**
     * Human readable label, e.g. "is_active" => "Is Active".
     */
    public function label(): string
    {
        return Str::headline($this->name);
    }

    /**
     * @return array{migration: string, validation: list<string>, input: string, cast: ?string}
     */
    public function definition(): array
    {
        return TypeDefinitions::for($this->type);
    }

    public function migrationMethod(): string
    {
        return $this->definition()['migration'];
    }

    public function inputType(): string
    {
        return $this->definition()['input'];
    }

    /**
     * The Eloquent cast for this field, or null when it does not need one.
     */
    public function cast(): ?string
    {
        $cast = $this->definition()['cast'];

        if ($cast === 'decimal') {
            return 'decimal:' . ($this->arguments[1] ?? '2');
        }

        return $cast;
    }

    public function isBoolean(): bool
    {
        return $this->type === 'boolean';
    }

    public function isTextarea(): bool
    {
        return in_array($this->inputType(), ['textarea'], true);
    }

    public function isSelect(): bool
    {
        return $this->inputType() === 'select';
    }

    public function isHidden(): bool
    {
        return $this->type === 'password';
    }

    /**
     * Available options for an enum/select field.
     *
     * @return list<string>
     */
    public function options(): array
    {
        return $this->isSelect() ? $this->arguments : [];
    }

    /**
     * The maximum string length when one was provided, e.g. string(100).
     */
    public function length(): ?int
    {
        if (in_array($this->type, ['string', 'char'], true) && isset($this->arguments[0])) {
            return (int) $this->arguments[0];
        }

        return null;
    }
}
