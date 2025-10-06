<?php declare(strict_types=1);

use App\Repositories\TodoRepository;
use App\Database\Connection;
use PHPUnit\Framework\TestCase;

final class TodoRepositoryTest extends TestCase
{
    private TodoRepository $repo;

    protected function setUp(): void
    {
        $this->repo = new TodoRepository();
        
        // Clean up test data
        $pdo = Connection::get();
        $pdo->exec("DELETE FROM todos WHERE title LIKE 'TEST:%'");
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        $pdo = Connection::get();
        $pdo->exec("DELETE FROM todos WHERE title LIKE 'TEST:%'");
    }

    public function testCreateAndFindById(): void
    {
        $task = $this->repo->create(
            'TEST: New Task',
            false,
            'Test description',
            '2025-12-20T09:00:00Z',
            '2025-12-31T23:59:59Z',
            1
        );

        $this->assertIsArray($task);
        $this->assertArrayHasKey('id', $task);
        $this->assertSame('TEST: New Task', $task['title']);
        $this->assertSame('Test description', $task['description']);
        $this->assertFalse($task['completed']);
        $this->assertSame(1, $task['priority']);

        $found = $this->repo->findById($task['id']);
        $this->assertIsArray($found);
        $this->assertSame($task['id'], $found['id']);
        $this->assertSame('TEST: New Task', $found['title']);
    }

    public function testFindByIdReturnsNullForNonexistent(): void
    {
        $result = $this->repo->findById(999999);
        $this->assertNull($result);
    }

    public function testList(): void
    {
        $this->repo->create('TEST: Task 1', false, '', null, null, 2);
        $this->repo->create('TEST: Task 2', true, '', null, null, 1);
        $this->repo->create('TEST: Task 3', false, '', null, null, 0);

        $list = $this->repo->list();
        
        $this->assertIsArray($list);
        $this->assertGreaterThanOrEqual(3, count($list));
        
        // Check that list contains our test tasks
        $titles = array_column($list, 'title');
        $this->assertContains('TEST: Task 1', $titles);
        $this->assertContains('TEST: Task 2', $titles);
        $this->assertContains('TEST: Task 3', $titles);
    }

    public function testListOrdersByPriorityDescThenIdAsc(): void
    {
        $task1 = $this->repo->create('TEST: Low Priority', false, '', null, null, 0);
        $task2 = $this->repo->create('TEST: High Priority', false, '', null, null, 2);
        $task3 = $this->repo->create('TEST: Medium Priority', false, '', null, null, 1);

        $list = $this->repo->list();
        
        // Find positions of our tasks
        $positions = [];
        foreach ($list as $index => $task) {
            if (str_starts_with($task['title'], 'TEST:')) {
                $positions[$task['title']] = $index;
            }
        }

        // High priority should come before medium, medium before low
        $this->assertLessThan($positions['TEST: Medium Priority'], $positions['TEST: High Priority']);
        $this->assertLessThan($positions['TEST: Low Priority'], $positions['TEST: Medium Priority']);
    }

    public function testUpdateTitle(): void
    {
        $task = $this->repo->create('TEST: Original', false);
        $updated = $this->repo->update($task['id'], 'TEST: Updated', null, null, null, null, null);

        $this->assertIsArray($updated);
        $this->assertSame('TEST: Updated', $updated['title']);
        $this->assertSame($task['id'], $updated['id']);
    }

    public function testUpdateCompleted(): void
    {
        $task = $this->repo->create('TEST: Incomplete', false);
        $updated = $this->repo->update($task['id'], null, true, null, null, null, null);

        $this->assertIsArray($updated);
        $this->assertTrue($updated['completed']);
    }

    public function testUpdateDescription(): void
    {
        $task = $this->repo->create('TEST: Task', false, 'Original desc');
        $updated = $this->repo->update($task['id'], null, null, 'Updated description', null, null, null);

        $this->assertIsArray($updated);
        $this->assertSame('Updated description', $updated['description']);
    }

    public function testUpdateDueDate(): void
    {
        $task = $this->repo->create('TEST: Task', false);
        $newDate = '2025-11-15T10:00:00Z';
        $updated = $this->repo->update($task['id'], null, null, null, null, $newDate, null);

        $this->assertIsArray($updated);
        $this->assertStringContainsString('2025-11-15', $updated['due_date']);
    }

    public function testUpdatePriority(): void
    {
        $task = $this->repo->create('TEST: Task', false, '', null, null, 0);
        $updated = $this->repo->update($task['id'], null, null, null, null, null, 2);

        $this->assertIsArray($updated);
        $this->assertSame(2, $updated['priority']);
    }

    public function testUpdateMultipleFields(): void
    {
        $task = $this->repo->create('TEST: Original', false, '', null, null, 0);
        $updated = $this->repo->update(
            $task['id'],
            'TEST: Multi Update',
            true,
            'New description',
            null,
            '2025-12-01T00:00:00Z',
            2
        );

        $this->assertIsArray($updated);
        $this->assertSame('TEST: Multi Update', $updated['title']);
        $this->assertTrue($updated['completed']);
        $this->assertSame('New description', $updated['description']);
        $this->assertStringContainsString('2025-12-01', $updated['due_date']);
        $this->assertSame(2, $updated['priority']);
    }

    public function testUpdateReturnsNullForNonexistent(): void
    {
        $result = $this->repo->update(999999, 'TEST: Nonexistent', null, null, null, null, null);
        $this->assertNull($result);
    }

    public function testUpdateWithNoChangesReturnsCurrent(): void
    {
        $task = $this->repo->create('TEST: No Change', false);
        $result = $this->repo->update($task['id'], null, null, null, null, null, null);

        $this->assertIsArray($result);
        $this->assertSame($task['id'], $result['id']);
        $this->assertSame('TEST: No Change', $result['title']);
    }

    public function testDelete(): void
    {
        $task = $this->repo->create('TEST: To Delete', false);
        $deleted = $this->repo->delete($task['id']);

        $this->assertTrue($deleted);

        $found = $this->repo->findById($task['id']);
        $this->assertNull($found);
    }

    public function testDeleteReturnsFalseForNonexistent(): void
    {
        $result = $this->repo->delete(999999);
        $this->assertFalse($result);
    }

    public function testCreateWithMinimalData(): void
    {
        $task = $this->repo->create('TEST: Minimal');

        $this->assertIsArray($task);
        $this->assertSame('TEST: Minimal', $task['title']);
        $this->assertFalse($task['completed']);
        $this->assertSame('', $task['description']);
        $this->assertNull($task['due_date']);
        $this->assertSame(0, $task['priority']);
    }

    public function testCreateWithCompletedTrue(): void
    {
        $task = $this->repo->create('TEST: Completed', true);

        $this->assertIsArray($task);
        $this->assertTrue($task['completed']);
    }

    public function testCreateSetsTimestamps(): void
    {
        $task = $this->repo->create('TEST: Timestamps', false);

        $this->assertArrayHasKey('created_at', $task);
        $this->assertArrayHasKey('updated_at', $task);
        $this->assertNotNull($task['created_at']);
        $this->assertNotNull($task['updated_at']);
    }
}

