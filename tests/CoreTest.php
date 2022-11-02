<?php

use Illum\Routing\Router;

test('static call', function () {
    $router = new Router(new \Illuminate\Container\Container());
	expect($router->routes())->toBeArray();
});

test('set 404', function () {
    $router = new Router(new \Illuminate\Container\Container());
    $router->set404(function () {
		echo '404';
	});

	ob_start();
    $router->run();

	expect(ob_get_contents())->toBe('404');
	ob_end_clean();
});

test('set down', function () {
	$router = new Router(new \Illuminate\Container\Container(), ['app.down' => true]);

	$router->setDown(function () {
		echo 'down';
	});

	ob_start();
	$router->run();

	expect(ob_get_contents())->toBe('down');
	ob_end_clean();
});

test('get container instance', function () {
    $router = new Router(new \Illuminate\Container\Container());
    expect($router->getContainerInstance())->toBeInstanceOf(\Illuminate\Container\Container::class);
});

test('get router instance on container', function (){
    $container = new Illuminate\Container\Container();
    $router = new Router($container);

    $routerFromContainer = $container->get('router');
    expect($router)->toBe($routerFromContainer);
});
