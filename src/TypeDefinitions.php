<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud;

/**
 * Central registry that maps a logical schema type onto the concrete
 * migration column method, the base validation rules, the HTML input
 * type used in Blade forms and the Eloquent cast.
 */
final class TypeDefinitions
{
    /**
     * The full type map.
     *
     * @return array<string, array{migration: string, validation: list<string>, input: string, cast: ?string}>
     */
    public static function map(): array
    {
        return [
            'string' => [
                'migration' => 'string',
                'validation' => ['string', 'max:255'],
                'input' => 'text',
                'cast' => null,
            ],
            'char' => [
                'migration' => 'char',
                'validation' => ['string', 'max:255'],
                'input' => 'text',
                'cast' => null,
            ],
            'email' => [
                'migration' => 'string',
                'validation' => ['string', 'email', 'max:255'],
                'input' => 'email',
                'cast' => null,
            ],
            'password' => [
                'migration' => 'string',
                'validation' => ['string', 'min:8'],
                'input' => 'password',
                'cast' => 'hashed',
            ],
            'text' => [
                'migration' => 'text',
                'validation' => ['string'],
                'input' => 'textarea',
                'cast' => null,
            ],
            'longText' => [
                'migration' => 'longText',
                'validation' => ['string'],
                'input' => 'textarea',
                'cast' => null,
            ],
            'integer' => [
                'migration' => 'integer',
                'validation' => ['integer'],
                'input' => 'number',
                'cast' => 'integer',
            ],
            'bigInteger' => [
                'migration' => 'bigInteger',
                'validation' => ['integer'],
                'input' => 'number',
                'cast' => 'integer',
            ],
            'unsignedInteger' => [
                'migration' => 'unsignedInteger',
                'validation' => ['integer', 'min:0'],
                'input' => 'number',
                'cast' => 'integer',
            ],
            'unsignedBigInteger' => [
                'migration' => 'unsignedBigInteger',
                'validation' => ['integer', 'min:0'],
                'input' => 'number',
                'cast' => 'integer',
            ],
            'boolean' => [
                'migration' => 'boolean',
                'validation' => ['boolean'],
                'input' => 'checkbox',
                'cast' => 'boolean',
            ],
            'decimal' => [
                'migration' => 'decimal',
                'validation' => ['numeric'],
                'input' => 'number',
                'cast' => 'decimal',
            ],
            'float' => [
                'migration' => 'float',
                'validation' => ['numeric'],
                'input' => 'number',
                'cast' => 'float',
            ],
            'double' => [
                'migration' => 'double',
                'validation' => ['numeric'],
                'input' => 'number',
                'cast' => 'double',
            ],
            'date' => [
                'migration' => 'date',
                'validation' => ['date'],
                'input' => 'date',
                'cast' => 'date',
            ],
            'datetime' => [
                'migration' => 'dateTime',
                'validation' => ['date'],
                'input' => 'datetime-local',
                'cast' => 'datetime',
            ],
            'timestamp' => [
                'migration' => 'timestamp',
                'validation' => ['date'],
                'input' => 'datetime-local',
                'cast' => 'datetime',
            ],
            'time' => [
                'migration' => 'time',
                'validation' => ['date_format:H:i'],
                'input' => 'time',
                'cast' => null,
            ],
            'year' => [
                'migration' => 'year',
                'validation' => ['integer', 'digits:4'],
                'input' => 'number',
                'cast' => 'integer',
            ],
            'json' => [
                'migration' => 'json',
                'validation' => ['json'],
                'input' => 'textarea',
                'cast' => 'array',
            ],
            'enum' => [
                'migration' => 'enum',
                'validation' => ['string'],
                'input' => 'select',
                'cast' => null,
            ],
            'uuid' => [
                'migration' => 'uuid',
                'validation' => ['uuid'],
                'input' => 'text',
                'cast' => null,
            ],
        ];
    }

    public static function has(string $type): bool
    {
        return array_key_exists($type, self::map());
    }

    /**
     * @return array{migration: string, validation: list<string>, input: string, cast: ?string}
     */
    public static function for(string $type): array
    {
        return self::map()[$type] ?? self::map()['string'];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::map());
    }
}
