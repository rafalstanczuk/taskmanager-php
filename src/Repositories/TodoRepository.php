<?php declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use App\Domain\Task;
use PDO;

/** Data access for the todos table. */
final class TodoRepository implements TodoRepositoryInterface
{
    public function list(): array
    {
        $pdo = Connection::get();
        $stmt = $pdo->query('SELECT id, title, description, completed, start_date, due_date, priority, created_at, updated_at FROM todos ORDER BY priority DESC, id ASC');
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $task = Task::fromRow($row);
            $result[] = $task->toArray();
        }
        return $result;
    }

    public function findById(int $id): ?array
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare('SELECT id, title, description, completed, start_date, due_date, priority, created_at, updated_at FROM todos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return Task::fromRow($row)->toArray();
    }

    public function create(string $title, bool $completed = false, string $description = '', ?string $startDateIso = null, ?string $dueDateIso = null, int $priority = 0): array
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare('INSERT INTO todos (title, description, completed, start_date, due_date, priority) VALUES (:title, :description, :completed, :start_date, :due_date, :priority) RETURNING id, title, description, completed, start_date, due_date, priority, created_at, updated_at');
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':completed' => $completed ? 'true' : 'false',
            ':start_date' => $startDateIso,
            ':due_date' => $dueDateIso,
            ':priority' => $priority,
        ]);
        $row = $stmt->fetch();
        return Task::fromRow($row)->toArray();
    }

    public function update(int $id, ?string $title, ?bool $completed, ?string $description, ?string $startDateIso, ?string $dueDateIso, ?int $priority): ?array
    {
        $pdo = Connection::get();

        $sets = [];
        $params = [':id' => $id];
        if ($title !== null) {
            $sets[] = 'title = :title';
            $params[':title'] = $title;
        }
        if ($description !== null) {
            $sets[] = 'description = :description';
            $params[':description'] = $description;
        }
        if ($completed !== null) {
            $sets[] = 'completed = :completed';
            $params[':completed'] = $completed ? 'true' : 'false';
        }
        if ($startDateIso !== null) {
            $sets[] = 'start_date = :start_date';
            $params[':start_date'] = $startDateIso;
        }
        if ($dueDateIso !== null) {
            $sets[] = 'due_date = :due_date';
            $params[':due_date'] = $dueDateIso;
        }
        if ($priority !== null) {
            $sets[] = 'priority = :priority';
            $params[':priority'] = $priority;
        }
        if (!$sets) {
            return $this->findById($id);
        }

        $sets[] = 'updated_at = NOW()';
        $sql = 'UPDATE todos SET ' . implode(', ', $sets) . ' WHERE id = :id RETURNING id, title, description, completed, start_date, due_date, priority, created_at, updated_at';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return Task::fromRow($row)->toArray();
    }

    public function delete(int $id): bool
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare('DELETE FROM todos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}


