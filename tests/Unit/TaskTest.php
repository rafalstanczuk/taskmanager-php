<?php declare(strict_types=1);

use App\Domain\Task;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    public function testFromRowAndToArray(): void
    {
        $row = [
            'id' => 10,
            'title' => 'T',
            'description' => 'D',
            'completed' => 1,
            'start_date' => '2025-12-25T09:00:00+00:00',
            'due_date' => '2025-12-31T12:00:00+00:00',
            'priority' => 2,
            'created_at' => '2025-01-01 00:00:00+00',
            'updated_at' => '2025-01-02 00:00:00+00',
        ];
        $task = Task::fromRow($row);
        $arr = $task->toArray();
        $this->assertSame(10, $arr['id']);
        $this->assertSame('T', $arr['title']);
        $this->assertTrue($arr['completed']);
        $this->assertSame(2, $arr['priority']);
        $this->assertSame('2025-12-25T09:00:00+00:00', $arr['start_date']);
        $this->assertSame('2025-12-31T12:00:00+00:00', $arr['due_date']);
    }

    public function testFromRowWithNullDescription(): void
    {
        $row = [
            'id' => 1,
            'title' => 'Test Task',
            'description' => null,
            'completed' => 0,
            'start_date' => null,
            'due_date' => null,
            'priority' => 1,
            'created_at' => '2025-01-01 00:00:00+00',
            'updated_at' => '2025-01-01 00:00:00+00',
        ];
        $task = Task::fromRow($row);
        $arr = $task->toArray();
        
        $this->assertSame(1, $arr['id']);
        $this->assertSame('Test Task', $arr['title']);
        // Task converts null description to empty string
        $this->assertSame('', $arr['description']);
        $this->assertFalse($arr['completed']);
        $this->assertNull($arr['start_date']);
        $this->assertNull($arr['due_date']);
        $this->assertSame(1, $arr['priority']);
    }

    public function testFromRowWithCompletedAsInteger(): void
    {
        $row = [
            'id' => 2,
            'title' => 'Task',
            'description' => 'Desc',
            'completed' => 1,
            'start_date' => null,
            'due_date' => '2025-10-15T10:00:00+00:00',
            'priority' => 0,
            'created_at' => '2025-01-01 00:00:00+00',
            'updated_at' => '2025-01-01 00:00:00+00',
        ];
        $task = Task::fromRow($row);
        $arr = $task->toArray();
        
        $this->assertTrue($arr['completed']);
    }

    public function testFromRowWithCompletedAsFalse(): void
    {
        $row = [
            'id' => 3,
            'title' => 'Incomplete Task',
            'description' => '',
            'completed' => false,
            'start_date' => null,
            'due_date' => null,
            'priority' => 2,
            'created_at' => '2025-01-01 00:00:00+00',
            'updated_at' => '2025-01-01 00:00:00+00',
        ];
        $task = Task::fromRow($row);
        $arr = $task->toArray();
        
        $this->assertFalse($arr['completed']);
    }

    public function testToArrayIncludesAllFields(): void
    {
        $row = [
            'id' => 5,
            'title' => 'Complete Task',
            'description' => 'Full description',
            'completed' => 1,
            'start_date' => '2025-12-20T09:00:00+00:00',
            'due_date' => '2025-12-25T00:00:00+00:00',
            'priority' => 2,
            'created_at' => '2025-10-01 12:00:00+00',
            'updated_at' => '2025-10-06 15:00:00+00',
        ];
        $task = Task::fromRow($row);
        $arr = $task->toArray();
        
        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('title', $arr);
        $this->assertArrayHasKey('description', $arr);
        $this->assertArrayHasKey('completed', $arr);
        $this->assertArrayHasKey('start_date', $arr);
        $this->assertArrayHasKey('due_date', $arr);
        $this->assertArrayHasKey('priority', $arr);
        $this->assertArrayHasKey('created_at', $arr);
        $this->assertArrayHasKey('updated_at', $arr);
    }

    public function testPriorityLevels(): void
    {
        $priorities = [0, 1, 2];
        
        foreach ($priorities as $priority) {
            $row = [
                'id' => $priority,
                'title' => "Priority $priority",
                'description' => null,
                'completed' => 0,
                'start_date' => null,
                'due_date' => null,
                'priority' => $priority,
                'created_at' => '2025-01-01 00:00:00+00',
                'updated_at' => '2025-01-01 00:00:00+00',
            ];
            $task = Task::fromRow($row);
            $arr = $task->toArray();
            
            $this->assertSame($priority, $arr['priority']);
        }
    }
}


