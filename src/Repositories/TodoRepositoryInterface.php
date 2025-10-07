<?php declare(strict_types=1);

namespace App\Repositories;

/**
 * Interface for Todo repository implementations
 */
interface TodoRepositoryInterface
{
    /**
     * Get all todos
     * 
     * @return array List of todos
     */
    public function list(): array;

    /**
     * Find todo by ID
     * 
     * @param int $id Todo ID
     * @return array|null Todo data or null if not found
     */
    public function findById(int $id): ?array;

    /**
     * Create a new todo
     * 
     * @param string $title Todo title
     * @param bool $completed Completion status
     * @param string $description Todo description
     * @param string|null $startDateIso Start date in ISO format
     * @param string|null $dueDateIso Due date in ISO format
     * @param int $priority Priority level
     * @return array Created todo data
     */
    public function create(string $title, bool $completed = false, string $description = '', ?string $startDateIso = null, ?string $dueDateIso = null, int $priority = 0): array;

    /**
     * Update a todo
     * 
     * @param int $id Todo ID
     * @param string|null $title Updated title
     * @param bool|null $completed Updated completion status
     * @param string|null $description Updated description
     * @param string|null $startDateIso Updated start date
     * @param string|null $dueDateIso Updated due date
     * @param int|null $priority Updated priority
     * @return array|null Updated todo data or null if not found
     */
    public function update(int $id, ?string $title, ?bool $completed, ?string $description, ?string $startDateIso, ?string $dueDateIso, ?int $priority): ?array;

    /**
     * Delete a todo
     * 
     * @param int $id Todo ID
     * @return bool True if deleted, false if not found
     */
    public function delete(int $id): bool;
}
