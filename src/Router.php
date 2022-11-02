<?php

namespace Illum\Routing;

use Illum\Http\Headers;

class Router extends Core
{

    /**
     * Set the 404 handling function.
     *
     * @param object|callable $handler The function to be executed
     */
    public function set404($handler = null)
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Set a custom maintenance mode callback.
     *
     * @param callable|null $handler The function to be executed
     */
    public function setDown(?callable $handler = null)
    {
        $this->downHandler = $handler;
    }

    /**
     * Mounts a collection of callbacks onto a base route.
     *
     * @param string $path The route sub pattern/path to mount the callbacks on
     * @param callable|array $handler The callback method
     */
    public function mount(string $path, $handler)
    {
        $groupOptions = [
            'namespace' => null,
        ];

        list($handler, $groupOptions) = $this->mapHandler(
            $handler,
            $groupOptions
        );

        $namespace = $this->namespace;
        $groupRoute = $this->groupRoute;

        if ($groupOptions['namespace']) {
            $this->namespace = $groupOptions['namespace'];
        }

        $this->groupRoute = $path;

        call_user_func($handler);

        $this->namespace = $namespace;
        $this->groupRoute = $groupRoute;
    }

    /**
     * Alias for mount
     * 
     * @param string $path The route sub pattern/path to mount the callbacks on
     * @param callable|array $handler The callback method
     */
    public function group(string $path, $handler)
    {
        $this->mount($path, $handler);
    }

    // ------------------- main routing stuff -----------------------

    /**
     * Store a route and it's handler
     * 
     * @param string $methods Allowed HTTP methods (separated by `|`)
     * @param string $pattern The route pattern/path to match
     * @param string|array|callable $handler The handler for route when matched
     */
    public function match(string $methods, string $pattern, $handler)
    {
        $pattern = $this->groupRoute . '/' . trim($pattern, '/');
        $pattern = $this->groupRoute ? rtrim($pattern, '/') : $pattern;

        $routeOptions = [
            'name' => null,
            'middleware' => null,
            'namespace' => null,
        ];

        if (is_string($handler)) {
            $namespace = $this->namespace;

            if ($routeOptions['namespace']) {
                $this->namespace = $routeOptions['namespace'];
            }

            $handler = str_replace('\\\\', '\\', $this->namespace . "\\$handler");

            $this->namespace = $namespace;
        }

        list($handler, $routeOptions) = $this->mapHandler(
            $handler,
            $routeOptions
        );

        foreach (explode('|', $methods) as $method) {
            $this->routes[$method][] = [
                'pattern' => $pattern,
                'handler' => $handler,
                'name' => $routeOptions['name'] ?? ''
            ];
        }

        $this->appRoutes[] = [
            'methods' => explode('|', $methods),
            'pattern' => $pattern,
            'handler' => $handler,
            'name' => $routeOptions['name'] ?? ''
        ];

        if ($routeOptions['name']) {
            $this->namedRoutes[$routeOptions['name']] = $pattern;
        }

        if ($routeOptions['middleware']) {
            $this->before($methods, $pattern, $routeOptions['middleware']);
        }
    }

    /**
     * Add a route with all available HTTP methods
     * 
     * @param string $pattern The route pattern/path to match
     * @param string|array|callable $handler The handler for route when matched
     */
    public function all(string $pattern, $handler)
    {
        $this->match(
            'GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD',
            $pattern,
            $handler
        );
    }

    /**
     * Add a route with GET method
     * 
     * @param string $pattern The route pattern/path to match
     * @param string|array|callable $handler The handler for route when matched
     */
    public function get(string $pattern, $handler)
    {
        $this->match('GET', $pattern, $handler);
    }

    /**
     * Add a route with POST method
     * 
     * @param string $pattern The route pattern/path to match
     * @param string|array|callable $handler The handler for route when matched
     */
    public function post(string $pattern, $handler)
    {
        $this->match('POST', $pattern, $handler);
    }

    /**
     * Add a route with PUT method
     * 
     * @param string $pattern The route pattern/path to match
     * @param string|array|callable $handler The handler for route when matched
     */
    public function put(string $pattern, $handler)
    {
        $this->match('PUT', $pattern, $handler);
    }

    /**
     * Add a route with PATCH method
     * 
     * @param string $pattern The route pattern/path to match
     * @param string|array|callable $handler The handler for route when matched
     */
    public function patch(string $pattern, $handler)
    {
        $this->match('PATCH', $pattern, $handler);
    }

    /**
     * Add a route with OPTIONS method
     * 
     * @param string $pattern The route pattern/path to match
     * @param string|array|callable $handler The handler for route when matched
     */
    public function options(string $pattern, $handler)
    {
        $this->match('OPTIONS', $pattern, $handler);
    }

    /**
     * Add a route with DELETE method
     * 
     * @param string $pattern The route pattern/path to match
     * @param string|array|callable $handler The handler for route when matched
     */
    public function delete(string $pattern, $handler)
    {
        $this->match('DELETE', $pattern, $handler);
    }

    /**
     * Add a route that sends an HTTP redirect
     *
     * @param string $from The url to redirect from
     * @param string $to The url to redirect to
     * @param int $status The http status code for redirect
     */
    public function redirect(
        string $from,
        string $to,
        int $status = 302
    ) {
        $this->get($from, function () use ($to, $status) {
            Headers::set('location', $to, true, $status);
        });
    }

    /**
     * Create a resource route for using controllers.
     * 
     * This creates a routes that implement CRUD functionality in a controller
     * `/posts` creates:
     * - `/posts` - GET | HEAD - Controller@index
     * - `/posts` - POST - Controller@store
     * - `/posts/{id}` - GET | HEAD - Controller@show
     * - `/posts/create` - GET | HEAD - Controller@create
     * - `/posts/{id}/edit` - GET | HEAD - Controller@edit
     * - `/posts/{id}/edit` - POST | PUT | PATCH - Controller@update
     * - `/posts/{id}/delete` - POST | DELETE - Controller@destroy
     * 
     * @param string $pattern The base route to use eg: /post
     * @param string $controller to handle route eg: PostController
     */
    public function resource(string $pattern, string $controller)
    {
        $this->match('GET|HEAD', $pattern, "$controller@index");
        $this->post($pattern, "$controller@store");
        $this->match('GET|HEAD', "$pattern/create", "$controller@create");
        $this->match('POST|DELETE', "$pattern/{id}/delete", "$controller@destroy");
        $this->match('POST|PUT|PATCH', "$pattern/{id}/edit", "$controller@update");
        $this->match('GET|HEAD', "$pattern/{id}/edit", "$controller@edit");
        $this->match('GET|HEAD', "$pattern/{id}", "$controller@show");
    }

    /**
     * Create a resource route for using controllers without the create and edit actions.
     * 
     * This creates a routes that implement CRUD functionality in a controller
     * `/posts` creates:
     * - `/posts` - GET | HEAD - Controller@index
     * - `/posts` - POST - Controller@store
     * - `/posts/{id}` - GET | HEAD - Controller@show
     * - `/posts/{id}/edit` - POST | PUT | PATCH - Controller@update
     * - `/posts/{id}/delete` - POST | DELETE - Controller@destroy
     * 
     * @param string $pattern The base route to use eg: /post
     * @param string $controller to handle route eg: PostController
     */
    public function apiResource(string $pattern, string $controller)
    {
        $this->match('GET|HEAD', $pattern, "$controller@index");
        $this->post($pattern, "$controller@store");
        $this->match('POST|DELETE', "$pattern/{id}/delete", "$controller@destroy");
        $this->match('POST|PUT|PATCH', "$pattern/{id}/edit", "$controller@update");
        $this->match('GET|HEAD', "$pattern/{id}", "$controller@show");
    }

    /**
     * Redirect to another route
     * 
     * @param string|array $route The route to redirect to
     * @param array|null $data Data to pass to the next route
     */
    public function push($route, ?array $data = null)
    {
        if (is_array($route)) {
            if (!isset($this->namedRoutes[$route[0]])) {
                trigger_error('Route named ' . $route[0] . ' not found');
            }

            $route = $this->namedRoutes[$route[0]];
        }

        if ($data) {
            $args = '?';

            foreach ($data as $key => $value) {
                $args .= "$key=$value&";
            }

            $data = rtrim($args, '&');
        }
        Headers::set('location', "$route$data");
    }
}
