<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud\Generators;

use Mostafizurmaruf\BingoCrud\CrudNames;
use Mostafizurmaruf\BingoCrud\Field;

interface Generator
{
    /**
     * Generate the artefact(s) for this generator.
     *
     * @param  list<Field>  $fields
     * @return list<array{path: string, action: string}>  One entry per file touched.
     */
    public function generate(CrudNames $names, array $fields, bool $force): array;
}
