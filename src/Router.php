<?php declare(strict_types=1);

namespace App;

class Router
{
    /** @var array<int, array{0:string,1:array{0:string,1:array<int,string>},2:callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [$method, $this->compilePattern($pattern), $handler];
    }

    /** @return array{0:string,1:array<int,string>} */
    private function compilePattern(string $pattern): array
    {
        $paramNames = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            function (array $m) use (&$paramNames) {
                $paramNames[] = $m[1];
                return '([A-Za-z0-9\-_]+)';
            },
            $pattern
        );
        $regex = '#^' . rtrim((string)$regex, '/') . '$#';
        return [$regex, $paramNames];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes as [$routeMethod, [$regex, $paramNames], $handler]) {
            if ($method !== $routeMethod) {
                continue;
            }
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches); // drop full match
                $params = $paramNames ? (array)array_combine($paramNames, $matches) : [];
                // Detect handler arity to avoid passing params to zero-arg handlers
                $expectsParams = false;
                if (is_array($handler) && isset($handler[0], $handler[1]) && is_object($handler[0]) && is_string($handler[1])) {
                    $ref = new \ReflectionMethod($handler[0], $handler[1]);
                    $expectsParams = $ref->getNumberOfParameters() > 0;
                } else {
                    $ref = new \ReflectionFunction($handler);
                    $expectsParams = $ref->getNumberOfParameters() > 0;
                }
                $result = $expectsParams ? $handler($params) : $handler();
                if ($result !== null) {
                    json_response($result);
                }
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
}


