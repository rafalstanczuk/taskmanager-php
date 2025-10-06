<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class TodoApiTest extends TestCase
{
    private static string $base = 'http://localhost:8000';

    public static function setUpBeforeClass(): void
    {
        // assume server is already running via docker compose run -p
    }

    public function testHealth(): void
    {
        $json = shell_exec('curl -s ' . escapeshellarg(self::$base . '/health'));
        $this->assertStringContainsString('ok', (string)$json);
    }

    public function testTodoCrud(): void
    {
        $payload = json_encode(['title' => 'IT', 'description' => 'D', 'priority' => 1, 'completed' => false]);
        $created = shell_exec('curl -s -X POST -H "Content-Type: application/json" -d ' . escapeshellarg($payload) . ' ' . escapeshellarg(self::$base . '/todos'));
        $this->assertNotFalse($created);
        $data = json_decode((string)$created, true);
        $this->assertIsArray($data);
        $this->assertSame('IT', $data['title']);
        $id = (int)$data['id'];

        $got = shell_exec('curl -s ' . escapeshellarg(self::$base . '/todos/' . $id));
        $this->assertStringContainsString('IT', (string)$got);

        $upd = json_encode(['completed' => true]);
        $updated = shell_exec('curl -s -X PUT -H "Content-Type: application/json" -d ' . escapeshellarg($upd) . ' ' . escapeshellarg(self::$base . '/todos/' . $id));
        $this->assertStringContainsString('true', (string)$updated);

        $del = shell_exec('curl -s -o /dev/null -w "%{http_code}" -X DELETE ' . escapeshellarg(self::$base . '/todos/' . $id));
        $this->assertSame('204', trim((string)$del));
    }

    public function testGetAllTodos(): void
    {
        $response = shell_exec('curl -s ' . escapeshellarg(self::$base . '/todos'));
        $data = json_decode((string)$response, true);
        
        $this->assertIsArray($data);
        // Should have at least some tasks (from seed)
        $this->assertGreaterThanOrEqual(0, count($data));
    }

    public function testCreateTodoWithAllFields(): void
    {
        $payload = json_encode([
            'title' => 'Integration Test Task',
            'description' => 'Full test description',
            'completed' => false,
            'due_date' => '2025-12-31T17:00:00Z',
            'priority' => 2
        ]);
        
        $response = shell_exec('curl -s -X POST -H "Content-Type: application/json" -d ' . escapeshellarg($payload) . ' ' . escapeshellarg(self::$base . '/todos'));
        $data = json_decode((string)$response, true);
        
        $this->assertIsArray($data);
        $this->assertSame('Integration Test Task', $data['title']);
        $this->assertSame('Full test description', $data['description']);
        $this->assertFalse($data['completed']);
        $this->assertStringContainsString('2025-12-31', $data['due_date']);
        $this->assertSame(2, $data['priority']);
        
        // Clean up
        shell_exec('curl -s -X DELETE ' . escapeshellarg(self::$base . '/todos/' . $data['id']));
    }

    public function testCreateTodoValidationMissingTitle(): void
    {
        $payload = json_encode(['description' => 'No title']);
        $response = shell_exec('curl -s -w "\n%{http_code}" -X POST -H "Content-Type: application/json" -d ' . escapeshellarg($payload) . ' ' . escapeshellarg(self::$base . '/todos'));
        
        $this->assertStringContainsString('400', (string)$response);
        $this->assertStringContainsString('required', (string)$response);
    }

    public function testCreateTodoValidationEmptyTitle(): void
    {
        $payload = json_encode(['title' => '   ']);
        $response = shell_exec('curl -s -w "\n%{http_code}" -X POST -H "Content-Type: application/json" -d ' . escapeshellarg($payload) . ' ' . escapeshellarg(self::$base . '/todos'));
        
        $this->assertStringContainsString('400', (string)$response);
    }

    public function testGetTodoNotFound(): void
    {
        $response = shell_exec('curl -s -w "\n%{http_code}" ' . escapeshellarg(self::$base . '/todos/999999'));
        
        $this->assertStringContainsString('404', (string)$response);
        $this->assertStringContainsString('Not found', (string)$response);
    }

    public function testUpdateTodoPartialFields(): void
    {
        // Create a task
        $payload = json_encode(['title' => 'Update Test', 'priority' => 0]);
        $created = shell_exec('curl -s -X POST -H "Content-Type: application/json" -d ' . escapeshellarg($payload) . ' ' . escapeshellarg(self::$base . '/todos'));
        $task = json_decode((string)$created, true);
        $id = $task['id'];
        
        // Update only priority
        $updatePayload = json_encode(['priority' => 2]);
        $updated = shell_exec('curl -s -X PUT -H "Content-Type: application/json" -d ' . escapeshellarg($updatePayload) . ' ' . escapeshellarg(self::$base . '/todos/' . $id));
        $updatedTask = json_decode((string)$updated, true);
        
        $this->assertSame(2, $updatedTask['priority']);
        $this->assertSame('Update Test', $updatedTask['title']); // Should remain unchanged
        
        // Clean up
        shell_exec('curl -s -X DELETE ' . escapeshellarg(self::$base . '/todos/' . $id));
    }

    public function testUpdateTodoNotFound(): void
    {
        $payload = json_encode(['title' => 'Updated']);
        $response = shell_exec('curl -s -w "\n%{http_code}" -X PUT -H "Content-Type: application/json" -d ' . escapeshellarg($payload) . ' ' . escapeshellarg(self::$base . '/todos/999999'));
        
        $this->assertStringContainsString('404', (string)$response);
    }

    public function testUpdateTodoValidationEmptyTitle(): void
    {
        // Create a task
        $payload = json_encode(['title' => 'Valid Task']);
        $created = shell_exec('curl -s -X POST -H "Content-Type: application/json" -d ' . escapeshellarg($payload) . ' ' . escapeshellarg(self::$base . '/todos'));
        $task = json_decode((string)$created, true);
        $id = $task['id'];
        
        // Try to update with empty title
        $updatePayload = json_encode(['title' => '']);
        $response = shell_exec('curl -s -w "\n%{http_code}" -X PUT -H "Content-Type: application/json" -d ' . escapeshellarg($updatePayload) . ' ' . escapeshellarg(self::$base . '/todos/' . $id));
        
        $this->assertStringContainsString('400', (string)$response);
        $this->assertStringContainsString('empty', (string)$response);
        
        // Clean up
        shell_exec('curl -s -X DELETE ' . escapeshellarg(self::$base . '/todos/' . $id));
    }

    public function testDeleteTodoNotFound(): void
    {
        $response = shell_exec('curl -s -o /dev/null -w "%{http_code}" -X DELETE ' . escapeshellarg(self::$base . '/todos/999999'));
        
        $this->assertSame('404', trim((string)$response));
    }

    public function testHtmlUiRendering(): void
    {
        $response = shell_exec('curl -s -H "Accept: text/html" ' . escapeshellarg(self::$base . '/todos'));
        
        $this->assertStringContainsString('<!doctype html>', strtolower((string)$response));
        $this->assertStringContainsString('Task Manager', (string)$response);
        $this->assertStringContainsString('gantt', strtolower((string)$response));
    }

    public function testJsonApiRendering(): void
    {
        $response = shell_exec('curl -s -H "Accept: application/json" ' . escapeshellarg(self::$base . '/todos'));
        $data = json_decode((string)$response, true);
        
        $this->assertIsArray($data);
    }

    public function testPriorityLevels(): void
    {
        $priorities = [0, 1, 2];
        $ids = [];
        
        foreach ($priorities as $priority) {
            $payload = json_encode(['title' => "Priority $priority", 'priority' => $priority]);
            $created = shell_exec('curl -s -X POST -H "Content-Type: application/json" -d ' . escapeshellarg($payload) . ' ' . escapeshellarg(self::$base . '/todos'));
            $task = json_decode((string)$created, true);
            
            $this->assertSame($priority, $task['priority']);
            $ids[] = $task['id'];
        }
        
        // Clean up
        foreach ($ids as $id) {
            shell_exec('curl -s -X DELETE ' . escapeshellarg(self::$base . '/todos/' . $id));
        }
    }

    public function testCompletedStatusToggle(): void
    {
        $payload = json_encode(['title' => 'Toggle Test', 'completed' => false]);
        $created = shell_exec('curl -s -X POST -H "Content-Type: application/json" -d ' . escapeshellarg($payload) . ' ' . escapeshellarg(self::$base . '/todos'));
        $task = json_decode((string)$created, true);
        $id = $task['id'];
        
        $this->assertFalse($task['completed']);
        
        // Toggle to true
        $updatePayload = json_encode(['completed' => true]);
        $updated = shell_exec('curl -s -X PUT -H "Content-Type: application/json" -d ' . escapeshellarg($updatePayload) . ' ' . escapeshellarg(self::$base . '/todos/' . $id));
        $updatedTask = json_decode((string)$updated, true);
        
        $this->assertTrue($updatedTask['completed']);
        
        // Toggle back to false
        $updatePayload2 = json_encode(['completed' => false]);
        $updated2 = shell_exec('curl -s -X PUT -H "Content-Type: application/json" -d ' . escapeshellarg($updatePayload2) . ' ' . escapeshellarg(self::$base . '/todos/' . $id));
        $updatedTask2 = json_decode((string)$updated2, true);
        
        $this->assertFalse($updatedTask2['completed']);
        
        // Clean up
        shell_exec('curl -s -X DELETE ' . escapeshellarg(self::$base . '/todos/' . $id));
    }
}


