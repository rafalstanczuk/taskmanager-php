<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Controllers\TodoController;
use App\Repositories\TodoRepositoryInterface;

final class TodoControllerTest extends TestCase
{
    private $mockRepo;
    private $controller;

    protected function setUp(): void
    {
        $this->mockRepo = $this->createMock(TodoRepositoryInterface::class);
        $this->controller = new TodoController($this->mockRepo);
    }

    public function testShowWithValidId(): void
    {
        $mockTodo = ['id' => 1, 'title' => 'Test Task', 'completed' => false];
        
        $this->mockRepo->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($mockTodo);
        
        $result = $this->controller->show(['id' => '1']);
        
        $this->assertSame($mockTodo, $result);
    }
    
    public function testShowWithInvalidId(): void
    {
        // Set up output buffering to capture json_response output
        ob_start();
        $result = $this->controller->show(['id' => 'invalid']);
        ob_end_clean();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Invalid id', $result['error']);
    }
    
    public function testShowWithNonExistentId(): void
    {
        $this->mockRepo->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);
        
        // Set up output buffering to capture json_response output
        ob_start();
        $result = $this->controller->show(['id' => '999']);
        ob_end_clean();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Not found', $result['error']);
    }
    
    public function testCreateWithValidData(): void
    {
        $mockTodo = [
            'id' => 1, 
            'title' => 'New Task', 
            'completed' => false,
            'description' => 'Test description',
            'priority' => 1,
            'due_date' => '2025-12-31T17:00:00Z'
        ];
        
        $this->mockRepo->expects($this->once())
            ->method('create')
            ->with(
                'New Task',
                false,
                'Test description',
                null,
                '2025-12-31T17:00:00Z',
                1
            )
            ->willReturn($mockTodo);
        
        // Set mock input data
        \App\set_test_input([
            'title' => 'New Task',
            'completed' => false,
            'description' => 'Test description',
            'due_date' => '2025-12-31T17:00:00Z',
            'priority' => 1
        ]);
        
        // Set up output buffering to capture json_response output
        ob_start();
        $result = $this->controller->create();
        ob_end_clean();
        
        $this->assertSame($mockTodo, $result);
    }
    
    public function testCreateWithEmptyTitle(): void
    {
        // Set mock input data
        \App\set_test_input([
            'title' => '',
            'description' => 'Test description'
        ]);
        
        // Set up output buffering to capture json_response output
        ob_start();
        $result = $this->controller->create();
        ob_end_clean();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('title is required', $result['error']);
    }
    
    public function testCreateWithInvalidDateFormat(): void
    {
        // Set mock input data
        \App\set_test_input([
            'title' => 'Valid Title',
            'due_date' => '2025-12-31' // Not ISO format
        ]);
        
        // Set up output buffering to capture json_response output
        ob_start();
        $result = $this->controller->create();
        ob_end_clean();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('valid ISO date', $result['error']);
    }
    
    public function testCreateWithInvalidPriority(): void
    {
        // Set mock input data
        \App\set_test_input([
            'title' => 'Valid Title',
            'priority' => 5 // Out of range
        ]);
        
        // Set up output buffering to capture json_response output
        ob_start();
        $result = $this->controller->create();
        ob_end_clean();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('priority must be between', $result['error']);
    }
    
    public function testUpdateWithValidData(): void
    {
        $mockTodo = [
            'id' => 1, 
            'title' => 'Updated Task', 
            'completed' => true,
            'description' => 'Updated description',
            'priority' => 2
        ];
        
        $this->mockRepo->expects($this->once())
            ->method('update')
            ->with(
                1,
                'Updated Task',
                true,
                'Updated description',
                null,
                null,
                2
            )
            ->willReturn($mockTodo);
        
        // Set mock input data
        \App\set_test_input([
            'title' => 'Updated Task',
            'completed' => true,
            'description' => 'Updated description',
            'priority' => 2
        ]);
        
        // Set up output buffering to capture json_response output
        ob_start();
        $result = $this->controller->update(['id' => '1']);
        ob_end_clean();
        
        $this->assertSame($mockTodo, $result);
    }
    
    public function testUpdateWithEmptyTitle(): void
    {
        // Set mock input data
        \App\set_test_input([
            'title' => ''
        ]);
        
        // Set up output buffering to capture json_response output
        ob_start();
        $result = $this->controller->update(['id' => '1']);
        ob_end_clean();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('title cannot be empty', $result['error']);
    }
    
    public function testUpdateWithNoChanges(): void
    {
        // Set mock input data
        \App\set_test_input([]);
        
        // Set up output buffering to capture json_response output
        ob_start();
        $result = $this->controller->update(['id' => '1']);
        ob_end_clean();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Nothing to update', $result['error']);
    }
    
    public function testDestroyWithValidId(): void
    {
        $this->mockRepo->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);
        
        // Set up output buffering to capture http_response_code
        ob_start();
        $result = $this->controller->destroy(['id' => '1']);
        ob_end_clean();
        
        $this->assertNull($result);
    }
    
    public function testDestroyWithNonExistentId(): void
    {
        $this->mockRepo->expects($this->once())
            ->method('delete')
            ->with(999)
            ->willReturn(false);
        
        // Set up output buffering to capture json_response output
        ob_start();
        $result = $this->controller->destroy(['id' => '999']);
        ob_end_clean();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Not found', $result['error']);
    }
}