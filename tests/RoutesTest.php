<?php

use illum\Routing\Router;

class TRoute
{
    static $val = true;
}

test('router match', function () {
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $_SERVER['REQUEST_URI'] = '/';

    TRoute::$val = true;

    $router = new Router;
    $router->match('PUT', '/', function () {
        TRoute::$val = false;
    });
    $router->run();

    expect(TRoute::$val)->toBe(false);
});

test('get route', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';

    TRoute::$val = true;

    $router = new Router;
    $router->get('/', function () {
        TRoute::$val = false;
    });
    $router->run();

    expect(TRoute::$val)->toBe(false);
});

test('post route', function () {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/';

    TRoute::$val = true;

    $router = new Router;
    $router->post('/', function () {
        TRoute::$val = false;
    });
    $router->run();

    expect(TRoute::$val)->toBe(false);
});

test('put route', function () {
    $_SERVER['REQUEST_METHOD'] = 'PUT';
    $_SERVER['REQUEST_URI'] = '/';

    TRoute::$val = true;

    $router = new Router;
    $router->put('/', function () {
        TRoute::$val = false;
    });
    $router->run();

    expect(TRoute::$val)->toBe(false);
});

test('patch route', function () {
    $_SERVER['REQUEST_METHOD'] = 'PATCH';
    $_SERVER['REQUEST_URI'] = '/';

    TRoute::$val = true;

    $router = new Router;
    $router->patch('/', function () {
        TRoute::$val = false;
    });
    $router->run();

    expect(TRoute::$val)->toBe(false);
});

test('options route', function () {
    $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
    $_SERVER['REQUEST_URI'] = '/';

    TRoute::$val = true;

    $router = new Router;
    $router->options('/', function () {
        TRoute::$val = false;
    });
    $router->run();

    expect(TRoute::$val)->toBe(false);
});

test('delete route', function () {
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    $_SERVER['REQUEST_URI'] = '/';

    TRoute::$val = true;

    $router = new Router;
    $router->delete('/', function () {
        TRoute::$val = false;
    });
    $router->run();

    expect(TRoute::$val)->toBe(false);
});
