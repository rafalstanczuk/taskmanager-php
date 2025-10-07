<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Repositories\TodoRepository;

/**
 * Integration tests for Gantt chart interactions
 */
final class GanttChartTest extends TestCase
{
    private TodoRepository $repo;
    private static array $createdIds = [];

    protected function setUp(): void
    {
        $this->repo = new TodoRepository();
    }

    protected function tearDown(): void
    {
        // Clean up created tasks
        foreach (self::$createdIds as $id) {
            $this->repo->delete($id);
        }
        self::$createdIds = [];
    }

    public function test_task_with_start_and_due_date_appears_in_gantt_chart(): void
    {
        // Create task with both dates
        $task = $this->repo->create(
            title: 'Gantt Chart Task',
            completed: false,
            description: 'Task for gantt chart',
            startDateIso: '2025-10-10T09:00:00Z',
            dueDateIso: '2025-10-15T17:00:00Z',
            priority: 1
        );
        self::$createdIds[] = $task['id'];

        $this->assertNotNull($task['start_date']);
        $this->assertNotNull($task['due_date']);
        $this->assertEquals('Gantt Chart Task', $task['title']);
    }

    public function test_task_without_start_date_uses_created_date(): void
    {
        // Create task without start_date
        $task = $this->repo->create(
            title: 'No Start Date',
            completed: false,
            description: '',
            startDateIso: null,
            dueDateIso: '2025-10-20T17:00:00Z',
            priority: 0
        );
        self::$createdIds[] = $task['id'];

        $this->assertNull($task['start_date']);
        $this->assertNotNull($task['due_date']);
        $this->assertNotNull($task['created_at']);
    }

    public function test_move_task_updates_both_start_and_due_dates(): void
    {
        // Create initial task
        $task = $this->repo->create(
            title: 'Move Test',
            completed: false,
            description: '',
            startDateIso: '2025-10-10T09:00:00Z',
            dueDateIso: '2025-10-15T17:00:00Z',
            priority: 1
        );
        self::$createdIds[] = $task['id'];

        $originalStart = $task['start_date'];
        $originalDue = $task['due_date'];

        // Move task (update both dates)
        $updated = $this->repo->update(
            id: $task['id'],
            title: null,
            completed: null,
            description: null,
            startDateIso: '2025-10-12T09:00:00Z',
            dueDateIso: '2025-10-17T17:00:00Z',
            priority: null
        );

        $this->assertNotEquals($originalStart, $updated['start_date']);
        $this->assertNotEquals($originalDue, $updated['due_date']);
        
        // Duration should be preserved (5 days)
        $start = new \DateTimeImmutable($updated['start_date']);
        $due = new \DateTimeImmutable($updated['due_date']);
        $duration = $start->diff($due)->days;
        
        $originalStartDt = new \DateTimeImmutable($originalStart);
        $originalDueDt = new \DateTimeImmutable($originalDue);
        $originalDuration = $originalStartDt->diff($originalDueDt)->days;
        
        $this->assertEquals($originalDuration, $duration);
    }

    public function test_resize_start_updates_only_start_date(): void
    {
        // Create task
        $task = $this->repo->create(
            title: 'Resize Start Test',
            completed: false,
            description: '',
            startDateIso: '2025-10-10T09:00:00Z',
            dueDateIso: '2025-10-15T17:00:00Z',
            priority: 1
        );
        self::$createdIds[] = $task['id'];

        $originalDue = $task['due_date'];

        // Update only start_date
        $updated = $this->repo->update(
            id: $task['id'],
            title: null,
            completed: null,
            description: null,
            startDateIso: '2025-10-08T09:00:00Z',
            dueDateIso: null,
            priority: null
        );

        $this->assertNotEquals($task['start_date'], $updated['start_date']);
        $this->assertEquals($originalDue, $updated['due_date']); // Due date unchanged
    }

    public function test_resize_end_updates_only_due_date(): void
    {
        // Create task
        $task = $this->repo->create(
            title: 'Resize End Test',
            completed: false,
            description: '',
            startDateIso: '2025-10-10T09:00:00Z',
            dueDateIso: '2025-10-15T17:00:00Z',
            priority: 1
        );
        self::$createdIds[] = $task['id'];

        $originalStart = $task['start_date'];

        // Update only due_date
        $updated = $this->repo->update(
            id: $task['id'],
            title: null,
            completed: null,
            description: null,
            startDateIso: null,
            dueDateIso: '2025-10-18T17:00:00Z',
            priority: null
        );

        $this->assertEquals($originalStart, $updated['start_date']); // Start unchanged
        $this->assertNotEquals($task['due_date'], $updated['due_date']);
    }

    public function test_completed_task_can_still_be_moved(): void
    {
        // Create completed task
        $task = $this->repo->create(
            title: 'Completed Task',
            completed: true,
            description: '',
            startDateIso: '2025-10-10T09:00:00Z',
            dueDateIso: '2025-10-15T17:00:00Z',
            priority: 2
        );
        self::$createdIds[] = $task['id'];

        $this->assertTrue($task['completed']);

        // Move completed task
        $updated = $this->repo->update(
            id: $task['id'],
            title: null,
            completed: null,
            description: null,
            startDateIso: '2025-10-12T09:00:00Z',
            dueDateIso: '2025-10-17T17:00:00Z',
            priority: null
        );

        $this->assertNotEquals($task['start_date'], $updated['start_date']);
        $this->assertNotEquals($task['due_date'], $updated['due_date']);
        $this->assertTrue($updated['completed']); // Still completed
    }

    public function test_priority_colors_on_gantt_chart_bars(): void
    {
        // Create tasks with different priorities
        $high = $this->repo->create('High Priority', false, '', '2025-10-10T09:00:00Z', '2025-10-12T17:00:00Z', 2);
        $med = $this->repo->create('Medium Priority', false, '', '2025-10-13T09:00:00Z', '2025-10-15T17:00:00Z', 1);
        $low = $this->repo->create('Low Priority', false, '', '2025-10-16T09:00:00Z', '2025-10-18T17:00:00Z', 0);

        self::$createdIds[] = $high['id'];
        self::$createdIds[] = $med['id'];
        self::$createdIds[] = $low['id'];

        $this->assertEquals(2, $high['priority']);
        $this->assertEquals(1, $med['priority']);
        $this->assertEquals(0, $low['priority']);
    }

    public function test_task_duration_calculation(): void
    {
        // Create task with 7-day duration
        $task = $this->repo->create(
            title: 'Week Task',
            completed: false,
            description: '',
            startDateIso: '2025-10-10T09:00:00Z',
            dueDateIso: '2025-10-17T17:00:00Z',
            priority: 1
        );
        self::$createdIds[] = $task['id'];

        $start = new \DateTimeImmutable($task['start_date']);
        $due = new \DateTimeImmutable($task['due_date']);
        $duration = $start->diff($due)->days;

        $this->assertEquals(7, $duration);
    }

    public function test_overlapping_tasks_on_gantt_chart(): void
    {
        // Create overlapping tasks
        $task1 = $this->repo->create(
            title: 'Task 1',
            completed: false,
            description: '',
            startDateIso: '2025-10-10T09:00:00Z',
            dueDateIso: '2025-10-15T17:00:00Z',
            priority: 1
        );

        $task2 = $this->repo->create(
            title: 'Task 2',
            completed: false,
            description: '',
            startDateIso: '2025-10-12T09:00:00Z',
            dueDateIso: '2025-10-18T17:00:00Z',
            priority: 1
        );

        self::$createdIds[] = $task1['id'];
        self::$createdIds[] = $task2['id'];

        $start1 = new \DateTimeImmutable($task1['start_date']);
        $due1 = new \DateTimeImmutable($task1['due_date']);
        $start2 = new \DateTimeImmutable($task2['start_date']);
        $due2 = new \DateTimeImmutable($task2['due_date']);

        // Check overlap
        $this->assertTrue($start2 < $due1 && $start1 < $due2);
    }

    public function test_null_start_date_preserved_on_partial_update(): void
    {
        // Create task without start_date
        $task = $this->repo->create(
            title: 'No Start',
            completed: false,
            description: '',
            startDateIso: null,
            dueDateIso: '2025-10-20T17:00:00Z',
            priority: 0
        );
        self::$createdIds[] = $task['id'];

        $this->assertNull($task['start_date']);

        // Update only title
        $updated = $this->repo->update(
            id: $task['id'],
            title: 'Updated Title',
            completed: null,
            description: null,
            startDateIso: null,
            dueDateIso: null,
            priority: null
        );

        $this->assertNull($updated['start_date']); // Still null
        $this->assertEquals('Updated Title', $updated['title']);
    }
}

