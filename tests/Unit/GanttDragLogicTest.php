<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gantt drag-and-drop logic
 * 
 * These tests document the JavaScript drag detection algorithm
 * and serve as specifications for the pixel-based implementation.
 */
final class GanttDragLogicTest extends TestCase
{
    /**
     * Test: Constants for pixel-based positioning
     */
    public function test_pixel_positioning_constants(): void
    {
        $MIN_DRAG_DISTANCE = 5; // pixels - click vs drag threshold
        $PIXELS_PER_DAY = 80;   // pixels - Gantt chart scale
        
        $this->assertEquals(5, $MIN_DRAG_DISTANCE);
        $this->assertEquals(80, $PIXELS_PER_DAY);
        $this->assertIsInt($MIN_DRAG_DISTANCE);
        $this->assertIsInt($PIXELS_PER_DAY);
        $this->assertGreaterThan(0, $MIN_DRAG_DISTANCE);
        $this->assertGreaterThan(0, $PIXELS_PER_DAY);
    }

    /**
     * Test: Drag state initialization with pixel positions
     */
    public function test_drag_state_initialization(): void
    {
        // When mousedown occurs, dragState is created with pixel positions
        $dragState = [
            'type' => 'move',
            'taskBar' => 'HTMLElement',
            'task' => ['id' => 1, 'title' => 'Test'],
            'startX' => 100,
            'startLeft' => 320.0,   // pixels (day 4 * 80px)
            'startWidth' => 240.0,  // pixels (3 days * 80px)
            'hasMoved' => false
        ];
        
        $this->assertFalse($dragState['hasMoved']);
        $this->assertEquals('move', $dragState['type']);
        $this->assertIsFloat($dragState['startLeft']);
        $this->assertIsFloat($dragState['startWidth']);
        $this->assertGreaterThanOrEqual(0, $dragState['startLeft']);
        $this->assertGreaterThanOrEqual(80, $dragState['startWidth']); // Minimum 1 day
    }

    /**
     * Test: Distance calculation from mouse movement
     */
    public function test_distance_calculation(): void
    {
        $startX = 100;
        $currentX = 104;
        
        $deltaX = abs($currentX - $startX);
        
        $this->assertEquals(4, $deltaX);
    }

    /**
     * Test: Movement below threshold doesn't trigger drag
     */
    public function test_movement_below_threshold_not_considered_drag(): void
    {
        $MIN_DRAG_DISTANCE = 5;
        $startX = 100;
        
        // Test movements below threshold
        $movements = [0, 1, 2, 3, 4]; // All below 5px
        
        foreach ($movements as $distance) {
            $currentX = $startX + $distance;
            $deltaX = abs($currentX - $startX);
            
            $shouldDrag = $deltaX > $MIN_DRAG_DISTANCE;
            $this->assertFalse($shouldDrag, "Movement of {$distance}px should not trigger drag");
        }
    }

    /**
     * Test: Movement above threshold triggers drag
     */
    public function test_movement_above_threshold_triggers_drag(): void
    {
        $MIN_DRAG_DISTANCE = 5;
        $startX = 100;
        
        // Test movements above threshold (must be > 5, not >= 5)
        $movements = [6, 10, 20, 50]; // All > 5px
        
        foreach ($movements as $distance) {
            $currentX = $startX + $distance;
            $deltaX = abs($currentX - $startX);
            
            $shouldDrag = $deltaX > $MIN_DRAG_DISTANCE;
            $this->assertTrue($shouldDrag, "Movement of {$distance}px should trigger drag");
        }
    }

    /**
     * Test: Negative movement (left) also triggers drag
     */
    public function test_negative_movement_triggers_drag(): void
    {
        $MIN_DRAG_DISTANCE = 5;
        $startX = 100;
        $currentX = 94; // Moved 6px left
        
        $deltaX = abs($currentX - $startX);
        
        $this->assertEquals(6, $deltaX);
        $this->assertTrue($deltaX > $MIN_DRAG_DISTANCE);
    }

    /**
     * Test: hasMoved flag transition
     */
    public function test_has_moved_flag_transition(): void
    {
        $MIN_DRAG_DISTANCE = 5;
        $hasMoved = false;
        $startX = 100;
        
        // First movement: 3px (below threshold)
        $currentX = 103;
        $deltaX = abs($currentX - $startX);
        if ($deltaX > $MIN_DRAG_DISTANCE && !$hasMoved) {
            $hasMoved = true;
        }
        $this->assertFalse($hasMoved);
        
        // Second movement: 6px (above threshold)
        $currentX = 106;
        $deltaX = abs($currentX - $startX);
        if ($deltaX > $MIN_DRAG_DISTANCE && !$hasMoved) {
            $hasMoved = true;
        }
        $this->assertTrue($hasMoved);
        
        // Once true, stays true
        $currentX = 110;
        $this->assertTrue($hasMoved);
    }

    /**
     * Test: Mouse up without movement
     */
    public function test_mouseup_without_movement_is_click(): void
    {
        $hasMoved = false;
        
        // On mouseup, check hasMoved
        $isClick = !$hasMoved;
        $isDrag = $hasMoved;
        
        $this->assertTrue($isClick);
        $this->assertFalse($isDrag);
    }

    /**
     * Test: Mouse up after movement
     */
    public function test_mouseup_after_movement_is_drag(): void
    {
        $hasMoved = true; // Set by mousemove when deltaX > 5
        
        // On mouseup, check hasMoved
        $isClick = !$hasMoved;
        $isDrag = $hasMoved;
        
        $this->assertFalse($isClick);
        $this->assertTrue($isDrag);
    }

    /**
     * Test: Drag types
     */
    public function test_drag_types(): void
    {
        $validTypes = ['move', 'resize-start', 'resize-end'];
        
        // Test move type
        $dragState = ['type' => 'move'];
        $this->assertContains($dragState['type'], $validTypes);
        
        // Test resize-start type
        $dragState = ['type' => 'resize-start'];
        $this->assertContains($dragState['type'], $validTypes);
        
        // Test resize-end type
        $dragState = ['type' => 'resize-end'];
        $this->assertContains($dragState['type'], $validTypes);
    }

    /**
     * Test: Position calculation for move (pixel-based)
     */
    public function test_position_calculation_for_move(): void
    {
        $startLeft = 320.0; // pixels (day 4 * 80px)
        $startX = 100; // mouse start position
        $currentX = 150; // mouse current position
        
        $deltaXPixels = $currentX - $startX; // 50px movement
        $newLeft = $startLeft + $deltaXPixels; // 370px
        
        $this->assertEquals(50.0, $deltaXPixels);
        $this->assertEquals(370.0, $newLeft);
        
        // Convert to days for verification
        $dayPosition = $newLeft / 80; // 4.625 days
        $this->assertEquals(4.625, $dayPosition);
    }

    /**
     * Test: Boundary clamping for move (pixel-based)
     */
    public function test_boundary_clamping_for_move(): void
    {
        $chartWidth = 5200; // 65 days * 80px
        $startWidth = 240; // Task width: 3 days * 80px
        
        // Test left boundary (negative position)
        $newLeft = -10.0;
        $clamped = max(0, min($chartWidth - $startWidth, $newLeft));
        $this->assertEquals(0, $clamped);
        
        // Test right boundary (beyond chart)
        $newLeft = 5300.0;
        $clamped = max(0, min($chartWidth - $startWidth, $newLeft));
        $this->assertEquals(4960, $clamped); // 5200 - 240
        
        // Test valid range
        $newLeft = 800.0; // Day 10
        $clamped = max(0, min($chartWidth - $startWidth, $newLeft));
        $this->assertEquals(800, $clamped);
    }

    /**
     * Test: Width calculation for resize (pixel-based)
     */
    public function test_width_calculation_for_resize(): void
    {
        $startWidth = 240.0; // pixels (3 days * 80px)
        $deltaXPixels = 80.0; // pixels (1 day movement)
        
        // Resize from right (increase width)
        $newWidth = $startWidth + $deltaXPixels;
        $this->assertEquals(320.0, $newWidth); // 4 days
        
        // Resize from left (decrease width when moving right)
        $newWidth = $startWidth - $deltaXPixels;
        $this->assertEquals(160.0, $newWidth); // 2 days
    }

    /**
     * Test: Minimum width enforcement (pixel-based)
     */
    public function test_minimum_width_enforcement(): void
    {
        $MIN_WIDTH = 80.0; // pixels - minimum 1 day
        
        // Test width below minimum
        $newWidth = 50.0;
        $clamped = max($MIN_WIDTH, $newWidth);
        $this->assertEquals(80.0, $clamped);
        
        // Test width above minimum
        $newWidth = 240.0;
        $clamped = max($MIN_WIDTH, $newWidth);
        $this->assertEquals(240.0, $clamped);
        
        // Test exactly at minimum
        $newWidth = 80.0;
        $clamped = max($MIN_WIDTH, $newWidth);
        $this->assertEquals(80.0, $clamped);
    }

    /**
     * Test: Date calculation from pixel position
     */
    public function test_date_calculation_from_position(): void
    {
        $PIXELS_PER_DAY = 80;
        $pixelPosition = 800.0; // pixels
        
        // Mock Gantt chart dates
        $minDate = new \DateTimeImmutable('2025-10-01');
        
        // Calculate day offset from pixel position
        $dayOffset = (int)round($pixelPosition / $PIXELS_PER_DAY); // 10 days
        
        $newDate = $minDate->modify("+{$dayOffset} days");
        
        $this->assertEquals(10, $dayOffset);
        $this->assertEquals('2025-10-11', $newDate->format('Y-m-d'));
        
        // Test another position
        $pixelPosition = 1600.0; // 20 days
        $dayOffset = (int)round($pixelPosition / $PIXELS_PER_DAY);
        $newDate = $minDate->modify("+{$dayOffset} days");
        
        $this->assertEquals(20, $dayOffset);
        $this->assertEquals('2025-10-21', $newDate->format('Y-m-d'));
    }

    /**
     * Test: Drag state cleanup on mouseup
     */
    public function test_drag_state_cleanup_on_mouseup(): void
    {
        $dragState = [
            'type' => 'move',
            'taskBar' => 'HTMLElement',
            'task' => ['id' => 1],
            'startX' => 100,
            'hasMoved' => true
        ];
        
        $this->assertNotNull($dragState);
        
        // After mouseup, dragState is set to null
        $dragState = null;
        
        $this->assertNull($dragState);
    }

    /**
     * Test: Click detection algorithm
     */
    public function test_click_detection_algorithm(): void
    {
        $MIN_DRAG_DISTANCE = 5;
        
        // Scenario 1: Pure click (no movement)
        $startX = 100;
        $endX = 100;
        $hasMoved = false;
        
        $deltaX = abs($endX - $startX);
        if ($deltaX > $MIN_DRAG_DISTANCE) {
            $hasMoved = true;
        }
        
        $isClick = !$hasMoved;
        $this->assertTrue($isClick);
        
        // Scenario 2: Small jitter (< threshold)
        $startX = 100;
        $endX = 103;
        $hasMoved = false;
        
        $deltaX = abs($endX - $startX);
        if ($deltaX > $MIN_DRAG_DISTANCE) {
            $hasMoved = true;
        }
        
        $isClick = !$hasMoved;
        $this->assertTrue($isClick);
        
        // Scenario 3: Actual drag (>= threshold)
        $startX = 100;
        $endX = 106;
        $hasMoved = false;
        
        $deltaX = abs($endX - $startX);
        if ($deltaX > $MIN_DRAG_DISTANCE) {
            $hasMoved = true;
        }
        
        $isClick = !$hasMoved;
        $this->assertFalse($isClick);
    }

    /**
     * Test: Edge case - exactly at threshold
     */
    public function test_exactly_at_threshold(): void
    {
        $MIN_DRAG_DISTANCE = 5;
        $startX = 100;
        $currentX = 105; // Exactly 5px
        
        $deltaX = abs($currentX - $startX);
        $shouldDrag = $deltaX > $MIN_DRAG_DISTANCE;
        
        // 5 is NOT > 5, so this is NOT a drag
        $this->assertFalse($shouldDrag);
    }

    /**
     * Test: Just over threshold
     */
    public function test_just_over_threshold(): void
    {
        $MIN_DRAG_DISTANCE = 5;
        $startX = 100;
        $currentX = 105.1; // Just over 5px
        
        $deltaX = abs($currentX - $startX);
        $shouldDrag = $deltaX > $MIN_DRAG_DISTANCE;
        
        // 5.1 IS > 5, so this IS a drag
        $this->assertTrue($shouldDrag);
    }
}

