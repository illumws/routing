<?php

use Illum\Routing\Router;

test('static call', function () {
	expect(Router::routes())->toBeArray();
});

test('set 404', function () {
    $router = new Router;
    $router->set404(function () {
		echo '404';
	});

	ob_start();
    $router->run();

	expect(ob_get_contents())->toBe('404');
	ob_end_clean();
});

test('set down', function () {
	$router = new Router;

    $router->configure(['app.down' => true]);

	$router->setDown(function () {
		echo 'down';
	});

	ob_start();
	$router->run();

	expect(ob_get_contents())->toBe('down');
	ob_end_clean();

	// clean up
	$router->configure(['app.down' => false]);
});

test('container instance', function () {
    Router::setContainerInstance(new \Illuminate\Container\Container());
    expect(Router::getContainerInstance())->toBeInstanceOf(\Illuminate\Container\Container::class);
});

test('container instance without set', function () {
    expect(Router::getContainerInstance())->toBeInstanceOf(\Illuminate\Container\Container::class);
});

