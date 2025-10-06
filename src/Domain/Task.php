<?php declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

final class Task
{
    public function __construct(
        public ?int $id,
        public string $title,
        public string $description,
        public bool $completed,
        public ?DateTimeImmutable $startDate,
        public ?DateTimeImmutable $dueDate,
        public int $priority,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    /** @param array{id:int,title:string,description:string,completed:bool|int|string,start_date:?(string),due_date:?(string),priority:int|string,created_at:string,updated_at:string} $row */
    public static function fromRow(array $row): self
    {
        $completed = is_bool($row['completed'])
            ? $row['completed']
            : (bool)((int)$row['completed']);
        $start = $row['start_date'] !== null && $row['start_date'] !== ''
            ? new DateTimeImmutable((string)$row['start_date'])
            : null;
        $due = $row['due_date'] !== null && $row['due_date'] !== ''
            ? new DateTimeImmutable((string)$row['due_date'])
            : null;
        return new self(
            id: (int)$row['id'],
            title: (string)$row['title'],
            description: (string)($row['description'] ?? ''),
            completed: $completed,
            startDate: $start,
            dueDate: $due,
            priority: (int)($row['priority'] ?? 0),
            createdAt: new DateTimeImmutable((string)$row['created_at']),
            updatedAt: new DateTimeImmutable((string)$row['updated_at'])
        );
    }

    /** Serialize to API-friendly array. */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'completed' => $this->completed,
            'start_date' => $this->startDate?->format(DateTimeImmutable::ATOM),
            'due_date' => $this->dueDate?->format(DateTimeImmutable::ATOM),
            'priority' => $this->priority,
            'created_at' => $this->createdAt->format('Y-m-d H:i:sP'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:sP'),
        ];
    }
}


