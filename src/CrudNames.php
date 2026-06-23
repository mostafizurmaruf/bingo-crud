<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud;

use Illuminate\Support\Str;

/**
 * Derives every name (class, variable, table, route, view) needed by the
 * generators from a single model name supplied on the command line.
 */
final class CrudNames
{
    public readonly string $model;
    public readonly string $modelVariable;
    public readonly string $modelPlural;
    public readonly string $pluralVariable;
    public readonly string $table;
    public readonly string $controller;
    public readonly string $request;
    public readonly string $routeName;
    public readonly string $routeUri;
    public readonly string $viewFolder;
    public readonly string $title;
    public readonly string $titlePlural;

    public function __construct(string $name)
    {
        $singular = Str::studly(Str::singular(class_basename($name)));

        $this->model = $singular;
        $this->modelVariable = Str::camel($singular);
        $this->modelPlural = Str::pluralStudly($singular);
        $this->pluralVariable = Str::camel(Str::pluralStudly($singular));
        $this->table = Str::snake(Str::pluralStudly($singular));
        $this->controller = $singular . 'Controller';
        $this->request = $singular . 'Request';
        $this->routeName = $this->table;
        $this->routeUri = str_replace('_', '-', $this->table);
        $this->viewFolder = $this->table;
        $this->title = Str::headline($singular);
        $this->titlePlural = Str::headline(Str::pluralStudly($singular));
    }
}
