<?php

declare(strict_types=1);

namespace illum\Routing;

use Closure;
use illum\Http\Request;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;

class Core
{
    /**
     * Container instance
     * @var Container|null
     */
    protected static ?Container $container = null;

    /**
     * Callable to be invoked if no matching routes are found
     */
    protected static ?Closure $notFoundHandler = null;

    /**
     * Callable to be invoked if app is down
     */
    protected static ?Closure $downHandler = null;

    /**
     * Router configuration
     */
    protected static array $config = [
        'mode' => 'development',
        'debug' => true,
        'app.down' => false,
        'path.prefix' => false,
    ];

    /**
     * 'Middleware' to run at specific times
     */
    protected static array $hooks = [
        'router.before' => false,
        'router.before.route' => false,
        'router.before.dispatch' => false,
        'router.after.dispatch' => false,
        'router.after.route' => false,
        'router.after' => false,
    ];

    /**
     * Leaf app middleware
     */
    protected static ?array $middleware = [];

    /**
     * Route specific middleware
     */
    protected static ?array $routeSpecificMiddleware = [];

    /**
     * All added routes and their handlers
     */
    protected static ?array $routes = [];

    /**
     * Sorted list of routes and their handlers
     */
    protected static ?array $appRoutes = [];

    /**
     * All named routes
     */
    protected static ?array $namedRoutes = [];

    /**
     * Current group base path
     */
    protected static ?string $groupRoute = '';

    /**
     * Default controller namespace
     */
    protected static ?string $namespace = '';

    /**
     * The Request Method that needs to be handled
     */
    protected static ?string $requestedMethod = '';

    /**
     * The Server Base Path for Router Execution
     */
    protected static ?string $serverBasePath = '';

    /**
     * Configure leaf router
     */
    public static function configure(array $config)
    {
        static::$config = array_merge(static::$config, $config);
    }

    /**
     * Force call the Leaf URL handler
     *
     * @param string $method The method to call
     * @param string $url The uri to force
     */
    public static function handleUrl(string $method, string $url)
    {
        if (isset(static::$routes[$method])) {
            static::handle(
                static::$routes[$method],
                true,
                $url
            );
        }
    }

    /**
     * Get all routes registered in your leaf app
     */
    public static function routes(): array
    {
        return static::$appRoutes;
    }

    /**
     * Set a global namespace for your handlers
     *
     * @param string $namespace The global namespace to set
     */
    public static function setNamespace(string $namespace)
    {
        static::$namespace = $namespace;
    }

    /**
     * Get the global handler namespace.
     *
     * @return string The given namespace if exists
     */
    public static function getNamespace(): string
    {
        return static::$namespace;
    }

    /**
     * Map handler and options
     */
    protected static function mapHandler($handler, $options): array
    {
        if (is_array($handler)) {
            $handlerData = $handler;

            if (isset($handler['handler'])) {
                $handler = $handler['handler'];
                unset($handlerData['handler']);
            } else {
                foreach ($handler as $key => $value) {
                    if (
                        (is_numeric($key) && is_callable($value))
                        || is_numeric($key) && is_string($value) && strpos($value, '@')
                    ) {
                        $handler = $handler[$key];
                        unset($handlerData[$key]);
                        break;
                    }
                }
            }

            foreach ($handlerData as $key => $value) {
                if (isset($value)) {
                    $options[$key] = $value;
                }
            }
        }

        return [$handler, $options];
    }

    /**
     * Add a router hook
     *
     * Available hooks
     * - router.before
     * - router.before.route
     * - router.before.dispatch
     * - router.after.dispatch
     * - router.after.route
     * - router.after
     *
     * @param string $name The hook to set
     * @param callable|null $handler The hook handler
     */
    public static function hook(string $name, callable $handler)
    {
        if (!isset(static::$hooks[$name])) {
            trigger_error("$name is not a valid hook! Refer to the docs for all supported hooks");
        }

        static::$hooks[$name] = $handler;
    }

    /**
     * Call a router hook
     *
     * @param string $name The hook to call
     */
    private static function callHook(string $name)
    {
        return is_callable(static::$hooks[$name]) ? static::$hooks[$name]() : null;
    }

    /**
     * Add a route specific middleware
     *
     * @param string $methods Allowed methods, separated by |
     * @param string|array $path The path/route to apply middleware on
     * @param callable $handler The middleware handler
     */
    public static function before(string $methods, $path, callable $handler)
    {
        if (is_array($path)) {
            if (!isset(static::$namedRoutes[$path[0]])) {
                trigger_error('Route named ' . $path[0] . ' not found');
            }

            $path = static::$namedRoutes[$path[0]];
        }

        $path = static::$groupRoute . '/' . trim($path, '/');
        $path = static::$groupRoute ? rtrim($path, '/') : $path;

        foreach (explode('|', $methods) as $method) {
            static::$routeSpecificMiddleware[$method][] = [
                'pattern' => $path,
                'handler' => $handler,
            ];
        }
    }

    /**
     * Add middleware
     *
     * This method prepends new middleware to the application middleware stack.
     * The argument must be an instance that subclasses Leaf_Middleware.
     *
     * @param Middleware $newMiddleware The middleware to set
     */
    public static function use(Middleware $newMiddleware)
    {
        if (in_array($newMiddleware, static::$middleware)) {
            $middleware_class = get_class($newMiddleware);
            throw new \RuntimeException("Circular Middleware setup detected. Tried to queue the same Middleware instance ({$middleware_class}) twice.");
        }

        if (!empty(static::$middleware)) {
            $newMiddleware->setNextMiddleware(static::$middleware[0]);
        }

        array_unshift(static::$middleware, $newMiddleware);
    }

    /**
     * Explicilty sets the server base path. To be used when your entry script path differs from your entry URLs.
     * @see https://github.com/bramus/router/issues/82#issuecomment-466956078
     *
     * @param string $serverBasePath
     */
    public static function setBasePath(string $serverBasePath)
    {
        static::$serverBasePath = $serverBasePath;
    }

    /**
     * Define the current relative URI.
     *
     * @return string
     */
    public static function getCurrentUri(): string
    {
        if (self::$config['path.prefix']){
            $uri = str_replace(self::$config['path.prefix'], '', Request::getPathInfo());
        } else {
            $uri = Request::getPathInfo();
        }

        if (strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        return '/' . trim($uri, '/');
    }

    /**
     * Get route info of the current route
     *
     * @return array The route info array
     */
    public static function getRoute(): array
    {
        return [
            'path' => static::getCurrentUri(),
            'method' => Request::getMethod(),
        ];
    }

    /**
     * @param Container $container
     * @return void
     */
    public static function setContainerInstance(Container $container){
        self::$container = $container;
    }

    /**
     * @return Container
     */
    public static function getContainerInstance(): Container
    {
        return self::$container ?? new Container();
    }

    /**
     * Dispatch your application routes
     */
    public static function run(?callable $callback = null): bool
    {
        $config = static::$config;
        if ($config['app.down'] === true) {

            if (!static::$downHandler) {
                static::$downHandler = function () {
                    throw new \ErrorException('App is under maintainance, please check back soon.');
                };
            }

            return static::invoke(static::$downHandler);
        }

        $middleware = static::$middleware;

        if (is_callable($callback)) {
            static::hook('router.after', $callback);
        }

        static::callHook('router.before');

        if (count($middleware) > 0) {
            if (is_string($middleware[0])) {
                (new $middleware[0])->call();
            } else {
                $middleware[0]->call();
            }
        }

        static::callHook('router.before.route');

        static::$requestedMethod = Request::getMethod();

        if (isset(static::$routeSpecificMiddleware[static::$requestedMethod])) {
            static::handle(static::$routeSpecificMiddleware[static::$requestedMethod]);
        }

        static::callHook('router.before.dispatch');

        $numHandled = 0;

        if (isset(static::$routes[static::$requestedMethod])) {
            $numHandled = static::handle(
                static::$routes[static::$requestedMethod],
                true
            );
        }

        static::callHook('router.after.dispatch');

        if ($numHandled === 0) {
            if (!static::$notFoundHandler) {
                static::$notFoundHandler = function () {
                    throw new \ErrorException("Route not found");
                };
            }

            return static::invoke(static::$notFoundHandler);
        }

        // if it originally was a HEAD request, clean up after ourselves by emptying the output buffer
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_end_clean();
        }

        static::callHook('router.after.route');

        restore_error_handler();

        return static::callHook('router.after') ?? ($numHandled !== 0);
    }

    /**
     * Handle a set of routes: if a match is found, execute the relating handling function.
     *
     * @param array $routes Collection of route patterns and their handling functions
     * @param bool $quitAfterRun Does the handle function need to quit after one route was matched?
     * @param string|null $uri The URI to call (automatically set if nothing is passed).
     *
     * @return int The number of routes handled
     */
    private static function handle(array $routes, bool $quitAfterRun = false, ?string $uri = null): int
    {
        $numHandled = 0;
        $uri = $uri ?? static::getCurrentUri();

        foreach ($routes as $route) {
            // Replace all curly braces matches {} into word patterns (like Laravel)
            $route['pattern'] = preg_replace('/\/{(.*?)}/', '/(.*?)', $route['pattern']);

            // we have a match!
            if (preg_match_all('#^' . $route['pattern'] . '$#', $uri, $matches, PREG_OFFSET_CAPTURE)) {
                // Rework matches to only contain the matches, not the orig string
                $matches = array_slice($matches, 1);

                // Extract the matched URL parameters (and only the parameters)
                $params = array_map(function ($match, $index) use ($matches) {
                    // We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
                    if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                        return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                    }

                    // We have no following parameters: return the whole lot
                    return isset($match[0][0]) ? trim($match[0][0], '/') : null;
                }, $matches, array_keys($matches));

                // Call the handling function with the URL parameters if the desired input is callable
                static::invoke($route['handler'], $params);
                ++$numHandled;

                if ($quitAfterRun) {
                    break;
                }
            }
        }

        return $numHandled;
    }

    /**
     * @param $handler
     * @param array $params
     * @return true
     * @throws BindingResolutionException
     */
    private static function invoke($handler, array $params = []): bool
    {
        if (is_callable($handler) or is_array($handler)) {
            if (is_array($handler)){
                $handler[0] = self::getContainerInstance()->make($handler[0]);
            }
            call_user_func_array(
                $handler,
                $params
            );
        }
        // If not, check the existence of special parameters
        elseif (stripos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);

            if (!class_exists($controller)) {
                trigger_error("$controller not found. Cross-check the namespace if you're sure the file exists");
            }

            if (!method_exists($controller, $method)) {
                trigger_error("$method method not found in $controller");
            }

            // First check if is a static method, directly trying to invoke it.
            // If isn't a valid static method, we will try as a normal method invocation.
            if (call_user_func_array([self::getContainerInstance()->make($handler[0]), $method], $params) === false) {
                // Try to call the method as a non-static method. (the if does nothing, only avoids the notice)
                if (forward_static_call_array([$controller, $method], $params) === false) return true;
            }
        }
        return true;
    }
}
