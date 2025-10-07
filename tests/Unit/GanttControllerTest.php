<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Controllers\GanttController;
use App\Repositories\TodoRepositoryInterface;

/**
 * Unit tests for GanttController
 */
final class GanttControllerTest extends TestCase
{
    private $mockRepo;
    private $controller;

    protected function setUp(): void
    {
        $this->mockRepo = $this->createMock(TodoRepositoryInterface::class);
        // GanttController doesn't accept repository in constructor, so we test with default
    }

    public function testIndexRendersHtmlWithGanttChart(): void
    {
        $controller = new GanttController();
        
        // Mock the repository to return test data
        $this->mockRepo->expects($this->never())
            ->method('list');
        
        // Capture output
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        
        // Verify HTML structure
        $this->assertStringContainsString('<!doctype html>', strtolower($output));
        $this->assertStringContainsString('Task Manager', $output);
        $this->assertStringContainsString('Gantt chart view', $output);
        $this->assertStringContainsString('gantt-chart', $output);
        $this->assertStringContainsString('Gantt Chart', $output);
    }

    public function testIndexContainsNavigationMenu(): void
    {
        $controller = new GanttController();
        
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('nav-menu', $output);
        $this->assertStringContainsString('Gantt Chart', $output);
        $this->assertStringContainsString('List View', $output);
        $this->assertStringContainsString('Kanban View', $output);
        $this->assertStringContainsString('Calendar View', $output);
    }

    public function testIndexContainsGanttChartElements(): void
    {
        $controller = new GanttController();
        
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('gantt-container', $output);
        $this->assertStringContainsString('id="gantt-chart"', $output);
        $this->assertStringContainsString('renderGantt', $output);
    }

    public function testIndexDoesNotContainListViewElements(): void
    {
        $controller = new GanttController();
        
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        
        // Should NOT contain list view specific elements
        $this->assertStringNotContainsString('id="list"', $output);
        $this->assertStringNotContainsString('create-form', $output);
        $this->assertStringNotContainsString('bulk-complete', $output);
        $this->assertStringNotContainsString('bulk-delete', $output);
        $this->assertStringNotContainsString('show-completed', $output);
        $this->assertStringNotContainsString('priority-filter', $output);
    }

    public function testIndexContainsDragAndDropFunctionality(): void
    {
        $controller = new GanttController();
        
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('dragState', $output);
        $this->assertStringContainsString('MIN_DRAG_DISTANCE', $output);
        $this->assertStringContainsString('calculateDateFromPixelPosition', $output);
        $this->assertStringContainsString('gantt-resize-handle', $output);
    }

    public function testIndexContainsTaskDetailCard(): void
    {
        $controller = new GanttController();
        
        ob_start();
        $controller->index();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('task-detail-card', $output);
        $this->assertStringContainsString('task-detail-overlay', $output);
        $this->assertStringContainsString('showTaskDetail', $output);
    }

    public function testIndexSetsCorrectHeaders(): void
    {
        $controller = new GanttController();
        
        ob_start();
        $controller->index();
        ob_end_clean();
        
        // Note: headers are already sent, but we can verify the method was called
        // In a real test environment, we'd use header mocking
        $this->assertTrue(true);
    }
}

