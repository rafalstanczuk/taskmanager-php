<?php declare(strict_types=1);

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


