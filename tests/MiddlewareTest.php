<?php

use Illum\Routing\Router;

class TMid
{
    static $callstack = '';
}

test('leaf middleware', function () {
	TMid::$callstack = '';

	class AppMid extends \Illum\Routing\Middleware {
		public function call()
		{
			TMid::$callstack .= '1';
		}
	}

	$_SERVER['REQUEST_METHOD'] = 'GET';
	$_SERVER['REQUEST_URI'] = '/';

    $router = new Router(new \Illuminate\Container\Container());

	$router->use(new AppMid);
	$router->get('/', function () {
		TMid::$callstack .= '2';
	});

	$router->run();

	expect(TMid::$callstack)->toBe('12');
});

test('in-route middleware', function () {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_SERVER['REQUEST_URI'] = '/';

	$m = function () {
		echo '1';
	};

    $router = new Router(new \Illuminate\Container\Container());
	
	$router->post('/', ['middleware' => $m, function () {
		echo '2';
	}]);

	ob_start();
	$router->run();

	expect(ob_get_contents())->toBe('12');
	ob_end_clean();
});

test('before route middleware', function () {
	$_SERVER['REQUEST_METHOD'] = 'PUT';
	$_SERVER['REQUEST_URI'] = '/';

    $router = new Router(new \Illuminate\Container\Container());

	$router->before('PUT', '/', function () {
		echo '1';
	});
	$router->put('/', function () {
		echo '2';
	});

	ob_start();
	$router->run();

	expect(ob_get_contents())->toBe('12');
	ob_end_clean();
});

test('before router middleware', function () {
	$_SERVER['REQUEST_METHOD'] = 'PATCH';
	$_SERVER['REQUEST_URI'] = '/test';

    $router = new Router(new \Illuminate\Container\Container());


	$router->before('PATCH', '/.*', function () {
		echo '1';
	});
	$router->patch('/test', function () {
		echo '2';
	});

    ob_start();
	$router->run();

	expect(ob_get_contents())->toBe('12');
	ob_end_clean();
});
test('after router middleware', function () {
    ob_start();
	$_SERVER['REQUEST_METHOD'] = 'PUT';
	$_SERVER['REQUEST_URI'] = '/test';

    $router = new Router(new \Illuminate\Container\Container());

	$router->put('/test', function () {
		echo '1';
	});


	$router->run(function () {
        echo '2';
    });

	expect(ob_get_contents())->toBe('12');
	ob_end_clean();
});
