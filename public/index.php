<?php declare(strict_types=1);

// Composer autoloader (optional). If absent, a simple PSR-4 autoloader is used.
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $path = __DIR__ . '/../src/' . str_replace('App\\', '', $class) . '.php';
            $path = str_replace('\\', '/', $path);
            if (is_file($path)) {
                require $path;
            }
        }
    });
}

require __DIR__ . '/../config/bootstrap.php';

use App\Router;
use App\Controllers\TodoController;
use App\Controllers\GanttController;

$router = new Router();

// Health check
$router->add('GET', '/health', fn () => ['status' => 'ok']);

// Todo endpoints
$todo = new TodoController();
$router->add('GET', '/todos', [$todo, 'index']);
$router->add('GET', '/todos/{id}', [$todo, 'show']);
$router->add('POST', '/todos', [$todo, 'create']);
$router->add('PUT', '/todos/{id}', [$todo, 'update']);
$router->add('DELETE', '/todos/{id}', [$todo, 'destroy']);

// Gantt view route
$gantt = new GanttController();
$router->add('GET', '/gantt', [$gantt, 'index']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');


