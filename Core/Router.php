<?php
namespace Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, string $controllerAction, array $middlewares = []): self
    {
        $this->addRoute('GET', $path, $controllerAction, $middlewares);
        return $this;
    }

    public function post(string $path, string $controllerAction, array $middlewares = []): self
    {
        $this->addRoute('POST', $path, $controllerAction, $middlewares);
        return $this;
    }

    private function addRoute(string $method, string $path, string $controllerAction, array $middlewares): void
    {
        $this->routes[] = [
            'method'   => $method,
            'path'     => $path,
            'handler'  => $controllerAction,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(): void
    {
        if (class_exists(\App\TrafficLog::class)) {
            \App\TrafficLog::startRequest();
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            $pattern = $this->pathToRegex($route['path']);
            if ($route['method'] === $method && preg_match($pattern, $uri, $params)) {
                array_shift($params);
                $handler = $route['handler'];
                [$controller, $action] = explode('@', $handler);
                $controllerClass = "App\\Controllers\\{$controller}";
                if (class_exists($controllerClass)) {
                    $instance = new $controllerClass();
                    call_user_func_array([$instance, $action], $params);
                    return;
                }
            }
        }
        http_response_code(404);
        echo '404 Not Found';
    }

    private function pathToRegex(string $path): string
    {
        $path = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        return '#^' . $path . '$#';
    }
}
