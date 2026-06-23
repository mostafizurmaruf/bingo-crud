<?php

declare(strict_types=1);

namespace Mostafizurmaruf\BingoCrud\Generators;

use Mostafizurmaruf\BingoCrud\CrudNames;

final class ControllerGenerator extends AbstractGenerator
{
    public function generate(CrudNames $names, array $fields, bool $force): array
    {
        $contents = $this->stubs->render('controller.stub', [
            'CONTROLLER' => $names->controller,
            'REQUEST' => $names->request,
            'MODEL' => $names->model,
            'MODEL_VARIABLE' => $names->modelVariable,
            'PLURAL_VARIABLE' => $names->pluralVariable,
            'VIEW_FOLDER' => $names->viewFolder,
            'ROUTE' => $names->routeName,
            'TITLE' => $names->title,
        ]);

        $path = app_path('Http/Controllers/' . $names->controller . '.php');

        return [$this->writeFile($path, $contents, $force)];
    }
}
