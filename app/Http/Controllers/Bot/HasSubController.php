<?php

namespace App\Http\Controllers\Bot;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\MiddlewareNameResolver;
use Illuminate\Routing\SortedMiddleware;


trait HasSubController{
    public function runController($controller, $method = 'index')
    {
        $middleware = $this->gatherControllerMiddleware($controller, $method);

        $middleware = $this->sortMiddleware($middleware);

        return $response = (new Pipeline(app()))
            ->send(request())
            ->through($middleware)
            ->then(function ($request) use ($controller, $method) {
                return app('router')->prepareResponse(
                    $request, (new ControllerDispatcher(app()))->dispatch(
                    app('router')->current(), $controller, $method
                )
                );
            });
    }

    protected function gatherControllerMiddleware($controller, $method)
    {
        return collect($this->controllerMidlleware($controller, $method))->map(function ($name) {
            return (array)MiddlewareNameResolver::resolve($name, app('router')->getMiddleware(), app('router')->getMiddlewareGroups());
        })->flatten();
    }

    protected function controllerMidlleware($controller, $method)
    {
        return (new ControllerDispatcher(app()))->getMiddleware(
            $controller, $method
        );
    }

    protected function sortMiddleware($middleware)
    {
        return (new SortedMiddleware(app('router')->middlewarePriority, $middleware))->all();
    }
}

