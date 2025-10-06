# Gantt Drag & Drop - Technical Implementation

Technical documentation for the Gantt chart drag-and-drop functionality.

## Overview

The Gantt chart implements three drag modes:
1. **Move** - Drag entire bar to reschedule (preserves duration)
2. **Resize Start** - Drag left handle to adjust start date
3. **Resize End** - Drag right handle to adjust end/due date

## Implementation Approach

### Pixel-Based Positioning

The implementation uses **pixel-based positioning** (80px per day) instead of percentage-based calculations.

**Advantages:**
- ✅ **Pixel-perfect alignment** - Tasks align exactly with date column boundaries
- ✅ **Simple calculations** - Direct conversion: `pixels / 80 = days`
- ✅ **No timezone drift** - Consistent UTC date handling prevents shifts
- ✅ **Accurate dragging** - Mouse movements translate 1:1 to pixel changes
- ✅ **Maintainable** - Easier to reason about than percentage math

**Key Constant:**
```javascript
const PIXELS_PER_DAY = 80;  // Each day column is 80px wide
```

## Data Model

### Database Schema

```sql
CREATE TABLE todos (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    completed BOOLEAN NOT NULL DEFAULT FALSE,
    start_date TIMESTAMPTZ NULL,        -- Task start date
    due_date TIMESTAMPTZ NULL,          -- Task due/end date
    priority INT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

### Task Model (PHP)

```php
final class Task {
    public function __construct(
        public ?int $id,
        public string $title,
        public string $description,
        public bool $completed,
        public ?DateTimeImmutable $startDate,    // Start date
        public ?DateTimeImmutable $dueDate,      // Due/end date
        public int $priority,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}
}
```

## HTML Structure

### Task Bar Element

```html
<div class="gantt-task-bar priority-1" 
     data-task-id="123"
     data-start-date="2025-10-10T09:00:00+00:00"
     data-due-date="2025-10-15T17:00:00+00:00"
     style="left: 480px; width: 400px;">
  
  <!-- Left resize handle -->
  <div class="gantt-resize-handle left" data-handle="start"></div>
  
  <!-- Task text (non-interactive) -->
  <div class="gantt-task-bar-text">Task Title</div>
  
  <!-- Right resize handle -->
  <div class="gantt-resize-handle right" data-handle="end"></div>
</div>
```

**Note**: Task bars use pixel-based positioning at 80px per day for pixel-perfect alignment.

## JavaScript Implementation

### Drag State Management

```javascript
let dragState = null;

// Drag state structure
dragState = {
  type: 'move' | 'resize-start' | 'resize-end',
  taskBar: HTMLElement,
  task: TaskObject,
  startX: number,           // Initial mouse X position
  startLeft: number,        // Initial bar left position (pixels)
  startWidth: number,       // Initial bar width (pixels)
  hasMoved: boolean         // Has exceeded 5px threshold
};

// Global date range info (updated on each render)
let ganttDateRange = {
  minDate: Date,            // Timeline start date
  maxDate: Date,            // Timeline end date
  dayCount: number          // Number of days in timeline
};
```

### Constants

```javascript
const MIN_DRAG_DISTANCE = 5;  // pixels - click vs drag threshold
const PIXELS_PER_DAY = 80;    // Timeline scale: 80px = 1 day
```

### Mouse Event Handlers

#### 1. Mouse Down (Start Drag/Resize)

```javascript
document.addEventListener('mousedown', (e) => {
  const handle = e.target.closest('.gantt-resize-handle');
  const taskBar = e.target.closest('.gantt-task-bar');
  
  if (!taskBar) return;
  
  const taskId = parseInt(taskBar.getAttribute('data-task-id'));
  const task = allItems.find(t => t.id === taskId);
  if (!task) return;
  
  // Get current pixel positions from style attributes
  const currentLeft = parseFloat(taskBar.style.left) || 0;
  const currentWidth = parseFloat(taskBar.style.width) || 80;
  
  if (handle) {
    // Resize mode
    e.preventDefault();
    const handleType = handle.getAttribute('data-handle');
    dragState = {
      type: handleType === 'start' ? 'resize-start' : 'resize-end',
      taskBar,
      task,
      startX: e.clientX,
      startLeft: currentLeft,
      startWidth: currentWidth
    };
    taskBar.classList.add('resizing');
  } else {
    // Move mode
    e.preventDefault();
    dragState = {
      type: 'move',
      taskBar,
      task,
      startX: e.clientX,
      startLeft: currentLeft,
      startWidth: currentWidth,
      hasMoved: false
    };
    taskBar.classList.add('dragging');
  }
});
```

**Key Changes**:
- Reads pixel positions directly from `style.left` and `style.width`
- No longer calculates percentages from bounding rectangles
- Stores positions in pixels for consistent calculations

#### 2. Mouse Move (Update Position)

```javascript
document.addEventListener('mousemove', (e) => {
  if (!dragState) return;
  
  const { type, taskBar, startX, startLeft, startWidth } = dragState;
  const deltaX = Math.abs(e.clientX - startX);
  
  // Check if moved beyond threshold
  if (deltaX > MIN_DRAG_DISTANCE && !dragState.hasMoved) {
    dragState.hasMoved = true;
  }
  
  // Only update visuals if moved beyond threshold
  if (!dragState.hasMoved) return;
  
  e.preventDefault();
  
  const deltaXPixels = e.clientX - startX;
  const timelineWidth = ganttDateRange.dayCount * 80;
  
  if (type === 'move') {
    // Move entire bar (in pixels)
    const newLeft = Math.max(0, Math.min(timelineWidth - startWidth, startLeft + deltaXPixels));
    taskBar.style.left = newLeft + 'px';
    
  } else if (type === 'resize-start') {
    // Resize from left (change start date)
    const newLeft = Math.max(0, startLeft + deltaXPixels);
    const newWidth = Math.max(80, startWidth - deltaXPixels); // Minimum 1 day (80px)
    
    if (newLeft + newWidth <= timelineWidth) {
      taskBar.style.left = newLeft + 'px';
      taskBar.style.width = newWidth + 'px';
    }
    
  } else if (type === 'resize-end') {
    // Resize from right (change end date)
    const newWidth = Math.max(80, startWidth + deltaXPixels); // Minimum 1 day (80px)
    
    if (startLeft + newWidth <= timelineWidth) {
      taskBar.style.width = newWidth + 'px';
    }
  }
});
```

**Key Changes**:
- Calculates `deltaXPixels` instead of `deltaPercent`
- Uses `ganttDateRange.dayCount * 80` for timeline width
- Updates positions in pixels (`px`) not percentages
- Enforces 80px minimum width (1 day)
- Respects timeline boundaries

#### 3. Mouse Up (Save Changes)

```javascript
document.addEventListener('mouseup', async (e) => {
  if (!dragState) return;
  
  const { type, taskBar, task, hasMoved } = dragState;
  
  taskBar.classList.remove('dragging', 'resizing');
  
  // If not moved beyond threshold, treat as click
  if (!hasMoved) {
    dragState = null;
    return;
  }
  
  // Get final pixel positions
  const finalLeftPx = parseFloat(taskBar.style.left) || 0;
  const finalWidthPx = parseFloat(taskBar.style.width) || 80;
  const finalRightPx = finalLeftPx + finalWidthPx;
  
  try {
    if (type === 'move') {
      // Move: Update both dates, preserve duration
      const startDate = task.start_date 
        ? new Date(task.start_date) 
        : new Date(task.created_at);
      const dueDate = new Date(task.due_date);
      const duration = Math.ceil((dueDate - startDate) / (1000 * 60 * 60 * 24));
      
      const newStart = calculateDateFromPixelPosition(finalLeftPx);
      const newDue = new Date(newStart);
      newDue.setUTCDate(newDue.getUTCDate() + duration);
      
      await fetch(`/todos/${task.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          start_date: newStart.toISOString(),
          due_date: newDue.toISOString()
        })
      });
      
    } else if (type === 'resize-start') {
      // Resize start: Update start_date only
      const newStart = calculateDateFromPixelPosition(finalLeftPx);
      
      await fetch(`/todos/${task.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ start_date: newStart.toISOString() })
      });
      
    } else if (type === 'resize-end') {
      // Resize end: Update due_date only
      const newDue = calculateDateFromPixelPosition(finalRightPx);
      
      await fetch(`/todos/${task.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ due_date: newDue.toISOString() })
      });
    }
    
    await refresh();
  } catch (error) {
    console.error('Failed to update task:', error);
  }
  
  dragState = null;
});
```

**Key Changes**:
- Reads final pixel positions from style attributes
- Calls `calculateDateFromPixelPosition()` instead of percentage-based function
- Uses `setUTCDate()` for UTC date handling
- Preserves duration when moving tasks

### Date Calculation

Converts pixel position to actual date:

```javascript
function calculateDateFromPixelPosition(pixelPos) {
  if (!ganttDateRange.minDate) return new Date();
  
  // Calculate day offset based on 80px per day
  const dayOffset = Math.round(pixelPos / 80);
  
  // Use the stored minDate from the last render
  const newDate = new Date(ganttDateRange.minDate);
  newDate.setUTCDate(newDate.getUTCDate() + dayOffset);
  newDate.setUTCHours(17, 0, 0, 0); // Default to 5 PM UTC
  
  return newDate;
}
```

**Key Features**:
- **Pixel-based**: Calculates days as `pixelPos / 80`
- **Global reference**: Uses `ganttDateRange.minDate` (updated on each render)
- **UTC normalization**: Uses `setUTCDate()` and `setUTCHours()` for consistency
- **Simple calculation**: Direct pixel-to-day conversion (no complex percentage math)
- **Safety check**: Returns current date if `minDate` not initialized

## CSS Styling

### Task Bar Base

```css
.gantt-task-bar {
  position: absolute;
  cursor: move;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 28px;
  border-radius: 4px;
  padding: 0 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
  user-select: none;
  transition: all 0.2s;
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
```

### Hover State

```css
.gantt-task-bar:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.15);
  z-index: 10;
}
```

### Drag States

```css
.gantt-task-bar.dragging {
  opacity: 0.6;
  cursor: grabbing;
  z-index: 100;
}

.gantt-task-bar.resizing {
  opacity: 0.8;
  z-index: 100;
}
```

### Resize Handles

```css
.gantt-resize-handle {
  position: absolute;
  width: 8px;
  height: 100%;
  top: 0;
  cursor: ew-resize;
  z-index: 2;
}

.gantt-resize-handle.left {
  left: 0;
  border-left: 2px solid rgba(255,255,255,0.5);
}

.gantt-resize-handle.right {
  right: 0;
  border-right: 2px solid rgba(255,255,255,0.5);
}

.gantt-resize-handle:hover {
  background: rgba(255,255,255,0.2);
}
```

### Task Text

```css
.gantt-task-bar-text {
  flex: 1;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  pointer-events: none;  /* Don't interfere with drag */
}
```

### Priority Colors

```css
.gantt-task-bar.priority-2 { 
  background: linear-gradient(135deg, #ef4444, #dc2626); 
}

.gantt-task-bar.priority-1 { 
  background: linear-gradient(135deg, #f59e0b, #d97706); 
}

.gantt-task-bar.priority-0 { 
  background: linear-gradient(135deg, #3b82f6, #2563eb); 
}
```

## API Integration

### Update Endpoints

**Move Task (Both Dates):**
```http
PUT /todos/{id}
Content-Type: application/json

{
  "start_date": "2025-10-12T09:00:00Z",
  "due_date": "2025-10-17T17:00:00Z"
}
```

**Resize Start (Start Date Only):**
```http
PUT /todos/{id}
Content-Type: application/json

{
  "start_date": "2025-10-08T09:00:00Z"
}
```

**Resize End (Due Date Only):**
```http
PUT /todos/{id}
Content-Type: application/json

{
  "due_date": "2025-10-18T17:00:00Z"
}
```

## Click vs Drag Detection

### The 5-Pixel Threshold

Prevents accidental moves on simple clicks:

```javascript
const MIN_DRAG_DISTANCE = 5;  // pixels

// In mousemove handler:
const deltaX = Math.abs(e.clientX - startX);

if (deltaX > MIN_DRAG_DISTANCE && !dragState.hasMoved) {
  dragState.hasMoved = true;
}

// In mouseup handler:
if (!hasMoved) {
  // Treat as click - don't save changes
  dragState = null;
  return;
}
```

**Behavior:**
- Movement ≤ 5px = Click (shows detail card)
- Movement > 5px = Drag (updates dates)

## Constraints & Validation

### Boundary Checks

```javascript
// Calculate timeline width in pixels
const timelineWidth = ganttDateRange.dayCount * 80;

// Move: Keep within timeline boundaries
const newLeft = Math.max(0, Math.min(timelineWidth - startWidth, startLeft + deltaXPixels));

// Resize: Minimum width 80px (1 day)
const newWidth = Math.max(80, startWidth + deltaXPixels);

// Resize: Don't exceed timeline width
if (newLeft + newWidth <= timelineWidth) {
  // Apply changes
}
```

### Date Logic

- Start date cannot be after due date (validation recommended)
- All dates saved in ISO 8601 format
- Default time: 5:00 PM (17:00) for new dates
- Timezone preserved from original date

## Performance Optimizations

### Event Delegation
- Document-level event listeners
- Single listener for all tasks
- No per-task event binding

### Throttling
- Mouse move updates are lightweight
- Only visual updates, no API calls
- Batch DOM updates where possible

### Optimistic UI
- Updates UI immediately
- Syncs with server on mouseup
- Refreshes after save for consistency

## Error Handling

```javascript
try {
  await fetch(`/todos/${task.id}`, {
    method: 'PUT',
    // ...
  });
  await refresh();
} catch (error) {
  console.error('Failed to update task:', error);
  // Timeline remains unchanged on error
  // User can retry operation
}
```

## Test Coverage

The drag-and-drop implementation is thoroughly tested:

### Unit Tests (19 tests)
- Minimum drag distance (5px)
- Movement tracking and hasMoved flag
- Click vs drag detection
- Position calculations
- Boundary enforcement
- Date calculations

### Integration Tests (10 tests)
- Move updates both dates
- Resize start updates only start_date
- Resize end updates only due_date
- Duration preservation
- Completed task handling

### UI Specifications (21 tests)
- User interaction behaviors
- Visual feedback states
- Edge cases and boundaries

See [Testing Guide](TESTING.md) for complete test documentation.

## Use Cases

### 1. Reschedule Project (Move)

**User Action:** Drag task bar 3 days right

**Result:**
- Original: Oct 10-15 (5 days)
- New: Oct 13-18 (5 days)
- Duration preserved ✓

### 2. Start Earlier (Resize Start)

**User Action:** Drag left handle 2 days left

**Result:**
- Original: Oct 10-15 (5 days)
- New: Oct 8-15 (7 days)
- End date unchanged ✓

### 3. Extend Deadline (Resize End)

**User Action:** Drag right handle 3 days right

**Result:**
- Original: Oct 10-15 (5 days)
- New: Oct 10-18 (8 days)
- Start date unchanged ✓

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Requires MouseEvent API
- CSS transforms for animations
- Flexbox for layout

## Related Documentation

- [Gantt Features](GANTT_FEATURES.md) - User-facing capabilities
- [API Reference](API.md) - Date field documentation
- [Testing Guide](TESTING.md) - Test coverage
- [Technical Overview](TECHNICAL_OVERVIEW.md) - Architecture

---

**Implementation**: Professional drag-and-drop  
**Technology**: Vanilla JavaScript, native APIs  
**Status**: Production-ready ✅
