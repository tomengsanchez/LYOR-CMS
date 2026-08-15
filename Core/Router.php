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

    public function patch(string $path, string $controllerAction, array $middlewares = []): self
    {
        $this->addRoute('PATCH', $path, $controllerAction, $middlewares);
        return $this;
    }

    public function delete(string $path, string $controllerAction, array $middlewares = []): self
    {
        $this->addRoute('DELETE', $path, $controllerAction, $middlewares);
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
        $parts = preg_split('/(\{\w+\})/', $path, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $regex = '';
        foreach ($parts as $part) {
            if (preg_match('/^\{(\w+)\}$/', $part)) {
                $regex .= '([^/]+)';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }
        return '#^' . $regex . '$#';
    }
}