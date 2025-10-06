<?php declare(strict_types=1);

use App\Database\Connection;

// Autoload
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

$pdo = Connection::get();

$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS todos (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    completed BOOLEAN NOT NULL DEFAULT FALSE,
    start_date TIMESTAMPTZ NULL,
    due_date TIMESTAMPTZ NULL,
    priority INT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Ensure columns exist when table was created earlier without them
ALTER TABLE todos
    ADD COLUMN IF NOT EXISTS description TEXT NOT NULL DEFAULT '';
ALTER TABLE todos
    ADD COLUMN IF NOT EXISTS start_date TIMESTAMPTZ NULL;
ALTER TABLE todos
    ADD COLUMN IF NOT EXISTS due_date TIMESTAMPTZ NULL;
ALTER TABLE todos
    ADD COLUMN IF NOT EXISTS priority INT NOT NULL DEFAULT 0;

-- Create or replace the trigger function (idempotent)
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $func$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$func$ LANGUAGE plpgsql;

-- Recreate trigger to ensure it exists and matches function
DROP TRIGGER IF EXISTS todos_set_updated_at ON todos;
CREATE TRIGGER todos_set_updated_at
BEFORE UPDATE ON todos
FOR EACH ROW
EXECUTE PROCEDURE set_updated_at();
SQL;

$pdo->exec($sql);

fwrite(STDOUT, "Migration completed.\n");


