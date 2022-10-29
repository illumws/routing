<?php

use illum\Routing\Router;

test('router match with controller', function () {
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $_SERVER['REQUEST_URI'] = '/put';

    \App\Controllers\Bar::$val = true;

    $router = new Router;
    $router->match('PUT', '/put', [\App\Controllers\Bar::class, 'val']);
    $router->run();

    expect(\App\Controllers\Bar::$val)->toBe(false);
});

test('get route with controller', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
    \App\Controllers\Bar::$val = true;

    $router = new Router;
    $router->get('/', [\App\Controllers\Bar::class, 'val']);
    $router->run();

    expect(\App\Controllers\Bar::$val)->toBe(false);
});

test('post route with controller', function () {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/delete';

    \App\Controllers\Bar::$val = true;

    $router = new Router;
    $router->post('/delete', [\App\Controllers\Bar::class, 'val']);
    $router->run();

    expect(\App\Controllers\Bar::$val)->toBe(false);
});

test('put route with controller', function () {
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $_SERVER['REQUEST_URI'] = '/patch';

    \App\Controllers\Bar::$val = true;

    $router = new Router;
    $router->put('/patch', [\App\Controllers\Bar::class, 'val']);
    $router->run();

    expect(\App\Controllers\Bar::$val)->toBe(false);
});

test('patch route with controller', function () {
    $_SERVER['REQUEST_METHOD'] = 'PATCH';
    $_SERVER['REQUEST_URI'] = '/';

    \App\Controllers\Bar::$val = true;

    $router = new Router;
    $router->patch('/', [\App\Controllers\Bar::class, 'val']);
    $router->run();

    expect(\App\Controllers\Bar::$val)->toBe(false);
});

test('options route with controller', function () {
    $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
    $_SERVER['REQUEST_URI'] = '/';

    \App\Controllers\Bar::$val = true;

    $router = new Router;
    $router->options('/', [\App\Controllers\Bar::class, 'val']);
    $router->run();

    expect(\App\Controllers\Bar::$val)->toBe(false);
});

test('delete route with controller', function () {
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    $_SERVER['REQUEST_URI'] = '/';

    \App\Controllers\Bar::$val = true;

    $router = new Router;
    $router->delete('/', [\App\Controllers\Bar::class, 'val']);
    $router->run();

    expect(\App\Controllers\Bar::$val)->toBe(false);
});

test('get route with controller and dependency injection', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';

    \App\Controllers\Bar::$val = true;

    $router = new Router;
    $router->get('/', [\App\Controllers\Foo::class, 'valFromBar']);
    $router->run();

    expect(\App\Controllers\Bar::$val)->toBe(false);
});