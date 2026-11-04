<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/** A route parameter that does not match the controller's model argument silently injects an empty model. */
class RouteBindingTest extends TestCase
{
    public function test_route_parameters_match_controller_model_arguments(): void
    {
        $problems = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction('controller');
            if (! $action || ! str_contains($action, '@')) {
                continue;
            }
            [$class, $method] = explode('@', $action);
            if (! class_exists($class)) {
                continue;
            }

            $params = array_map(fn ($n) => Str::camel($n), $route->parameterNames());
            foreach ((new ReflectionMethod($class, $method))->getParameters() as $arg) {
                $type = $arg->getType();
                $isModel = $type && ! $type->isBuiltin() && is_subclass_of($type->getName(), Model::class);
                if ($isModel && ! in_array($arg->getName(), $params, true)) {
                    $problems[] = "{$route->uri()} -> {$class}@{$method}(\${$arg->getName()}) but route has {" . implode('},{', $route->parameterNames()) . '}';
                }
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }
}
