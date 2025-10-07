<?php declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\TodoRepository;
use App\Repositories\TodoRepositoryInterface;
use function App\json_response;
use function App\read_json_input;

/**
 * HTTP handlers for Todo REST endpoints.
 * 
 * Handles CRUD operations for todo items and renders the UI.
 */
final class TodoController
{
    /**
     * Todo repository instance.
     */
    private TodoRepositoryInterface $repo;

    /**
     * Constructor with dependency injection.
     *
     * @param TodoRepositoryInterface|null $repository Optional repository instance for testing
     */
    public function __construct(?TodoRepositoryInterface $repository = null)
    {
        $this->repo = $repository ?? new TodoRepository();
    }

    /**
     * GET /todos
     * - If Accept header contains text/html, render a simple UI page
     * - Otherwise, return JSON list
     *
     * @return array|null Array of todos or null if HTML response
     */
    public function index(): ?array
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (stripos($accept, 'text/html') !== false) {
            $todos = $this->repo->list();
            $this->setHtmlHeaders();
            
            // Get the requested view from query parameters
            $view = $_GET['view'] ?? 'list';
            
            echo $this->renderHtml($todos, $view);
            return null;
        }
        return $this->repo->list();
    }
    
    /**
     * Set common HTML response headers
     *
     * @return void
     */
    private function setHtmlHeaders(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function renderHtml(array $todos, string $view = 'list'): string
    {
        $rows = '';
        foreach ($todos as $t) {
            $id = (int)$t['id'];
            $title = htmlspecialchars((string)$t['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $checked = $t['completed'] ? 'checked' : '';
            $rows .= "<li data-id=\"$id\"><input type=\"checkbox\" class=\"toggle\" $checked/> <span class=\"title\">$title</span> <button class=\"delete\">Delete</button></li>";
        }

        $head = <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
  <meta http-equiv="Pragma" content="no-cache" />
  <meta http-equiv="Expires" content="0" />
  <title>Todos</title>
  <style>
    * { box-sizing: border-box; }
    body { 
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; 
      max-width: 1200px; 
      margin: 0 auto; 
      padding: 2rem 1.5rem;
      background: #f5f7fa;
      color: #2c3e50;
    }
    h1 { margin-top: 0; color: #1e293b; font-weight: 600; }
    
    /* Toolbar */
    .toolbar { 
      display: flex; 
      gap: .75rem; 
      align-items: center; 
      margin-bottom: 1rem;
      background: white;
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      flex-wrap: wrap;
    }
    .toolbar label {
      display: flex;
      align-items: center;
      gap: .5rem;
      cursor: pointer;
      font-size: 14px;
      color: #475569;
      font-weight: 500;
    }
    
    /* Form */
    form { 
      display: flex; 
      gap: .75rem; 
      margin-bottom: 1.5rem; 
      background: white;
      padding: 1rem;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      flex-wrap: wrap;
    }
    input[type=text], input[type=date], select { 
      flex: 1; 
      min-width: 150px;
      padding: .65rem .85rem; 
      border: 1px solid #e2e8f0; 
      border-radius: 6px;
      font-size: 14px;
      transition: all 0.2s;
      color: #1e293b;
    }
    input[type=text]:focus, input[type=date]:focus, select:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* Buttons */
    button { 
      padding: .65rem 1.25rem; 
      background: #3b82f6; 
      color: white; 
      border: none; 
      border-radius: 6px; 
      cursor: pointer;
      font-weight: 500;
      font-size: 14px;
      transition: all 0.2s;
    }
    button:hover { background: #2563eb; transform: translateY(-1px); }
    button.delete { background: #ef4444; }
    button.delete:hover { background: #dc2626; }
    
    /* Checkboxes */
    input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
      accent-color: #3b82f6;
      margin: 0;
    }
    
    /* Task list */
    .task-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      overflow: hidden;
    }
    .task-header {
      display: flex;
      align-items: center;
      gap: .85rem;
      padding: 0.75rem 1.25rem;
      background: #f8fafc;
      border-bottom: 2px solid #e2e8f0;
      font-size: 0.75rem;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .task-header > span:nth-child(1) { width: 20px; text-align: center; }
    .task-header > span:nth-child(2) { width: 20px; text-align: center; }
    .task-header > span:nth-child(3) { min-width: 42px; text-align: center; }
    .task-header > span:nth-child(4) { flex: 1; min-width: 200px; }
    .task-header > span:nth-child(5) { min-width: 110px; }
    .task-header > span:nth-child(6) { min-width: 110px; }
    .task-header > span:nth-child(7) { width: 80px; }
    
    ul { 
      list-style: none; 
      padding: 0; 
      margin: 0;
    }
    li { 
      display: flex; 
      align-items: center; 
      gap: .85rem; 
      padding: 1rem 1.25rem; 
      border-bottom: 1px solid #f1f5f9;
      transition: background 0.15s;
    }
    li:last-child { border-bottom: none; }
    li:hover { background: #f8fafc; }
    li.completed { opacity: 0.6; }
    li.completed .title { text-decoration: line-through; color: #94a3b8; }
    
    li .select { width: 20px; flex-shrink: 0; }
    li .toggle { width: 20px; flex-shrink: 0; }
    li .pill { flex-shrink: 0; }
    li .title { 
      flex: 1; 
      padding: .35rem .65rem;
      border-radius: 4px;
      min-width: 200px;
      color: #1e293b;
      font-size: 15px;
    }
    li .title:focus {
      outline: none;
      background: #eff6ff;
      box-shadow: 0 0 0 2px #bfdbfe;
    }
    
    .muted { 
      color: #64748b; 
      font-size: .875rem;
      min-width: 110px;
      flex-shrink: 0;
    }
    li .actions {
      display: flex;
      gap: 0.5rem;
      flex-shrink: 0;
    }
    
    /* Priority pills */
    .pill { 
      padding: .3rem .7rem; 
      border-radius: 12px; 
      font-size: .75rem;
      font-weight: 600;
      min-width: 42px;
      text-align: center;
      letter-spacing: 0.3px;
    }
    .pill[data-priority="2"] { background: #fee2e2; color: #991b1b; }
    .pill[data-priority="1"] { background: #fef3c7; color: #92400e; }
    .pill[data-priority="0"] { background: #dbeafe; color: #1e40af; }
    
    /* Navigation Menu */
    .nav-menu {
      background: white;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      margin-bottom: 1.5rem;
      overflow: hidden;
    }
    .nav-list {
      display: flex;
      list-style: none;
      padding: 0;
      margin: 0;
      border-bottom: 2px solid #f1f5f9;
    }
    .nav-item {
      margin: 0;
      padding: 0;
    }
    .nav-item a {
      display: block;
      padding: 1rem 1.5rem;
      color: #64748b;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.95rem;
      transition: all 0.2s;
      border-bottom: 3px solid transparent;
      margin-bottom: -2px;
    }
    .nav-item a:hover {
      color: #3b82f6;
      background: #f8fafc;
    }
    .nav-item.active a {
      color: #3b82f6;
      border-bottom: 3px solid #3b82f6;
    }
  </style>
</head>
<body>
  <h1>📋 Task Manager <span class="muted" style="font-weight: 400; font-size: 0.9rem;">(served from /todos)</span></h1>
  
  <!-- Navigation Menu -->
  <div class="nav-menu">
    <ul class="nav-list">
      <li class="nav-item"><a href="/gantt">📊 Gantt Chart</a></li>
      <li class="nav-item active"><a href="/todos">📑 List View</a></li>
      <li class="nav-item"><a href="/todos?view=kanban">📌 Kanban View</a></li>
      <li class="nav-item"><a href="/todos?view=calendar">📅 Calendar View</a></li>
    </ul>
  </div>
  
  <div class="toolbar">
    <label><input type="checkbox" id="show-completed" /> Show completed</label>
    <select id="priority-filter">
      <option value="">All priorities</option>
      <option value="2">🔴 High priority</option>
      <option value="1">🟡 Medium priority</option>
      <option value="0">🔵 Low priority</option>
    </select>
    <button id="bulk-complete">✓ Mark selected complete</button>
    <button id="bulk-delete" class="delete">🗑 Delete selected</button>
  </div>
  
  <form id="create-form">
    <input id="title" type="text" placeholder="Task title..." required />
    <input id="description" type="text" placeholder="Description (optional)" />
    <input id="due-date" type="date" />
    <select id="priority">
      <option value="0">🔵 Low</option>
      <option value="1" selected>🟡 Medium</option>
      <option value="2">🔴 High</option>
    </select>
    <button type="submit">➕ Add Task</button>
  </form>
  <div class="task-container">
    <div class="task-header">
      <span title="Select for bulk actions">☑</span>
      <span title="Mark as completed">✓</span>
      <span>Priority</span>
      <span>Task</span>
      <span>Description</span>
      <span>Due Date</span>
      <span>Actions</span>
    </div>
    <ul id="list">
HTML;

        $tail = <<<'HTML'
    </ul>
  </div>

  <script>
    // Get current view from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const currentView = urlParams.get('view') || 'list';
    
    // Update active navigation item
    document.querySelectorAll('.nav-item').forEach(item => {
      item.classList.remove('active');
    });
    
    // Set active class based on current view
    if (currentView === 'list') {
      document.querySelector('.nav-item:nth-child(2)').classList.add('active');
    } else if (currentView === 'kanban') {
      document.querySelector('.nav-item:nth-child(3)').classList.add('active');
    } else if (currentView === 'calendar') {
      document.querySelector('.nav-item:nth-child(4)').classList.add('active');
    } else {
      // Default to list view
      document.querySelector('.nav-item:nth-child(2)').classList.add('active');
    }
    
    const listEl = document.getElementById('list');
    const formEl = document.getElementById('create-form');
    const titleEl = document.getElementById('title');
    const descEl = document.getElementById('description');
    const dueEl = document.getElementById('due-date');
    const priorityEl = document.getElementById('priority');
    const showCompletedEl = document.getElementById('show-completed');
    const priorityFilterEl = document.getElementById('priority-filter');
    const bulkCompleteEl = document.getElementById('bulk-complete');
    const bulkDeleteEl = document.getElementById('bulk-delete');

    let allItems = [];

    async function refresh() {
      const res = await fetch('/todos', { headers: { 'Accept': 'application/json' } });
      allItems = await res.json();
      const filtered = allItems.filter(t => (showCompletedEl.checked || !t.completed) && (!priorityFilterEl.value || String(t.priority) === priorityFilterEl.value));
      listEl.innerHTML = filtered.map(t => {
        const priorityLabel = t.priority === 2 ? 'High' : t.priority === 1 ? 'Med' : 'Low';
        const completedClass = t.completed ? ' completed' : '';
        return `
        <li data-id="${t.id}" class="${completedClass}">
          <input type="checkbox" class="select" title="Select for bulk action" />
          <input type="checkbox" class="toggle" ${t.completed ? 'checked' : ''} title="Mark as completed" />
          <span class="pill" data-priority="${t.priority}">${priorityLabel}</span>
          <span class="title" contenteditable="true" spellcheck="false">${escapeHtml(t.title)}</span>
          <span class="muted" title="Description">${escapeHtml(t.description || '')}</span>
          <span class="muted" title="Due date">${t.due_date ? new Date(t.due_date).toLocaleDateString() : ''}</span>
          <div class="actions">
            <button class="save">Save</button>
            <button class="delete">Delete</button>
          </div>
        </li>
      `;
      }).join('');
    }

    formEl.addEventListener('submit', async (e) => {
      e.preventDefault();
      const title = titleEl.value.trim();
      if (!title) return;
      await fetch('/todos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title, description: descEl.value.trim(), due_date: dueEl.value ? new Date(dueEl.value).toISOString() : null, priority: Number(priorityEl.value), completed: false })
      });
      titleEl.value = '';
      descEl.value = '';
      dueEl.value = '';
      priorityEl.value = '0';
      await refresh();
    });

    listEl.addEventListener('click', async (e) => {
      const li = e.target.closest('li');
      if (!li) return;
      const id = li.getAttribute('data-id');
      if (e.target.classList.contains('delete')) {
        await fetch(`/todos/${id}`, { method: 'DELETE' });
        await refresh();
      } else if (e.target.classList.contains('save')) {
        const title = li.querySelector('.title').textContent.trim();
        await fetch(`/todos/${id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ title })
        });
        await refresh();
      }
    });

    listEl.addEventListener('change', async (e) => {
      const li = e.target.closest('li');
      if (!li) return;
      const id = li.getAttribute('data-id');
      if (e.target.classList.contains('toggle')) {
        const completed = e.target.checked;
        await fetch(`/todos/${id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ completed })
        });
        await refresh();
      }
    });

    function escapeHtml(str) {
      return str
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    bulkCompleteEl.addEventListener('click', async () => {
      const ids = [...document.querySelectorAll('#list .select:checked')].map(cb => cb.closest('li').getAttribute('data-id'));
      for (const id of ids) {
        await fetch(`/todos/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ completed: true }) });
      }
      await refresh();
    });

    bulkDeleteEl.addEventListener('click', async () => {
      const ids = [...document.querySelectorAll('#list .select:checked')].map(cb => cb.closest('li').getAttribute('data-id'));
      for (const id of ids) {
        await fetch(`/todos/${id}`, { method: 'DELETE' });
      }
      await refresh();
    });

    showCompletedEl.addEventListener('change', () => {
      const filtered = allItems.filter(t => (showCompletedEl.checked || !t.completed) && (!priorityFilterEl.value || String(t.priority) === priorityFilterEl.value));
      listEl.innerHTML = filtered.map(t => {
        const priorityLabel = t.priority === 2 ? 'High' : t.priority === 1 ? 'Med' : 'Low';
        const completedClass = t.completed ? ' completed' : '';
        return `
        <li data-id="${t.id}" class="${completedClass}">
          <input type="checkbox" class="select" title="Select for bulk action" />
          <input type="checkbox" class="toggle" ${t.completed ? 'checked' : ''} title="Mark as completed" />
          <span class="pill" data-priority="${t.priority}">${priorityLabel}</span>
          <span class="title" contenteditable="true" spellcheck="false">${escapeHtml(t.title)}</span>
          <span class="muted" title="Description">${escapeHtml(t.description || '')}</span>
          <span class="muted" title="Due date">${t.due_date ? new Date(t.due_date).toLocaleDateString() : ''}</span>
          <div class="actions">
            <button class="save">Save</button>
            <button class="delete">Delete</button>
          </div>
        </li>
      `;
      }).join('');
    });

    priorityFilterEl.addEventListener('change', () => {
      const filtered = allItems.filter(t => (showCompletedEl.checked || !t.completed) && (!priorityFilterEl.value || String(t.priority) === priorityFilterEl.value));
      listEl.innerHTML = filtered.map(t => {
        const priorityLabel = t.priority === 2 ? 'High' : t.priority === 1 ? 'Med' : 'Low';
        const completedClass = t.completed ? ' completed' : '';
        return `
        <li data-id="${t.id}" class="${completedClass}">
          <input type="checkbox" class="select" title="Select for bulk action" />
          <input type="checkbox" class="toggle" ${t.completed ? 'checked' : ''} title="Mark as completed" />
          <span class="pill" data-priority="${t.priority}">${priorityLabel}</span>
          <span class="title" contenteditable="true" spellcheck="false">${escapeHtml(t.title)}</span>
          <span class="muted" title="Description">${escapeHtml(t.description || '')}</span>
          <span class="muted" title="Due date">${t.due_date ? new Date(t.due_date).toLocaleDateString() : ''}</span>
          <div class="actions">
            <button class="save">Save</button>
            <button class="delete">Delete</button>
          </div>
        </li>
      `;
      }).join('');
    });

    // Task Detail Card functionality
    const detailCard = document.getElementById('task-detail-card');
    const detailOverlay = document.getElementById('task-detail-overlay');
    const closeDetail = document.getElementById('close-detail');
    let currentDetailTask = null;

    function showTaskDetail(task) {
      currentDetailTask = task;
      document.getElementById('detail-title').textContent = task.title;
      document.getElementById('detail-status').textContent = task.completed ? 'Completed' : 'Pending';
      document.getElementById('detail-status').className = `task-detail-status ${task.completed ? 'completed' : 'pending'}`;
      
      const priorityLabels = ['🔵 Low', '🟡 Medium', '🔴 High'];
      document.getElementById('detail-priority').textContent = priorityLabels[task.priority] || 'Unknown';
      document.getElementById('detail-description').textContent = task.description || 'No description';
      document.getElementById('detail-start-date').textContent = task.start_date ? new Date(task.start_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : 'No start date';
      document.getElementById('detail-due-date').textContent = task.due_date ? new Date(task.due_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : 'No due date';
      document.getElementById('detail-created').textContent = new Date(task.created_at).toLocaleString();
      
      detailCard.classList.add('active');
      detailOverlay.classList.add('active');
    }

    function closeTaskDetail() {
      detailCard.classList.remove('active');
      detailOverlay.classList.remove('active');
      currentDetailTask = null;
    }

    closeDetail.addEventListener('click', closeTaskDetail);
    detailOverlay.addEventListener('click', closeTaskDetail);

    document.getElementById('detail-edit').addEventListener('click', () => {
      if (!currentDetailTask) return;
      const newTitle = prompt('Edit task title:', currentDetailTask.title);
      if (newTitle && newTitle.trim()) {
        fetch(`/todos/${currentDetailTask.id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ title: newTitle.trim() })
        }).then(() => {
          closeTaskDetail();
          refresh();
        });
      }
    });

    document.getElementById('detail-toggle').addEventListener('click', () => {
      if (!currentDetailTask) return;
      fetch(`/todos/${currentDetailTask.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ completed: !currentDetailTask.completed })
      }).then(() => {
        closeTaskDetail();
        refresh();
      });
    });

    document.getElementById('detail-delete').addEventListener('click', () => {
      if (!currentDetailTask) return;
      if (confirm(`Delete task "${currentDetailTask.title}"?`)) {
        fetch(`/todos/${currentDetailTask.id}`, { method: 'DELETE' })
          .then(() => {
            closeTaskDetail();
            refresh();
          });
      }
    });

    // Gantt chart drag and drop with resize
    let dragState = null; // {type: 'move'|'resize-start'|'resize-end', taskBar, task, startX, startLeft, startWidth, hasMoved}
    const MIN_DRAG_DISTANCE = 5; // pixels - minimum movement to consider it a drag
    
    // Store date range info for drag calculations (updated on each render)
    let ganttDateRange = {
      minDate: null,
      maxDate: null,
      dayCount: 0
    };

    // Helper to calculate date from pixel position
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

    // Click to show detail (only if not dragging/resizing)
    document.addEventListener('click', (e) => {
      if (dragState) return; // Don't show detail while dragging
      
      const taskBar = e.target.closest('.gantt-task-bar');
      const handle = e.target.closest('.gantt-resize-handle');
      
      if (taskBar && !handle) {
        const taskId = parseInt(taskBar.getAttribute('data-task-id'));
        const task = allItems.find(t => t.id === taskId);
        if (task) {
          showTaskDetail(task);
        }
      }
    });

    // Mouse down on task bar or resize handle
    document.addEventListener('mousedown', (e) => {
      const handle = e.target.closest('.gantt-resize-handle');
      const taskBar = e.target.closest('.gantt-task-bar');
      
      if (!taskBar) return;
      
      const taskId = parseInt(taskBar.getAttribute('data-task-id'));
      const task = allItems.find(t => t.id === taskId);
      if (!task) return;
      
      // Get current pixel positions
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

    // Mouse move for dragging/resizing
    document.addEventListener('mousemove', (e) => {
      if (!dragState) return;
      
      const { type, taskBar, startX, startLeft, startWidth } = dragState;
      const deltaX = Math.abs(e.clientX - startX);
      
      // Mark as moved if dragged beyond threshold
      if (deltaX > MIN_DRAG_DISTANCE && !dragState.hasMoved) {
        dragState.hasMoved = true;
      }
      
      // Only update visuals if moved beyond threshold
      if (!dragState.hasMoved) return;
      
      e.preventDefault();
      
      const deltaXPixels = e.clientX - startX;
      const timelineWidth = ganttDateRange.dayCount * 80;
      
      if (type === 'move') {
        // Move the entire bar (in pixels)
        const newLeft = Math.max(0, Math.min(timelineWidth - startWidth, startLeft + deltaXPixels));
        taskBar.style.left = newLeft + 'px';
        
      } else if (type === 'resize-start') {
        // Resize from the left (change start date)
        const newLeft = Math.max(0, startLeft + deltaXPixels);
        const newWidth = Math.max(80, startWidth - deltaXPixels); // Minimum 1 day (80px)
        
        // Don't let it go past the right edge
        if (newLeft + newWidth <= timelineWidth) {
          taskBar.style.left = newLeft + 'px';
          taskBar.style.width = newWidth + 'px';
        }
        
      } else if (type === 'resize-end') {
        // Resize from the right (change end date)
        const newWidth = Math.max(80, startWidth + deltaXPixels); // Minimum 1 day (80px)
        
        // Don't let it go past timeline width
        if (startLeft + newWidth <= timelineWidth) {
          taskBar.style.width = newWidth + 'px';
        }
      }
    });

    // Mouse up to save changes
    document.addEventListener('mouseup', async (e) => {
      if (!dragState) return;
      
      const { type, taskBar, task, hasMoved } = dragState;
      
      taskBar.classList.remove('dragging', 'resizing');
      
      // If not moved beyond threshold, treat as a click - don't save changes
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
          // Calculate duration in days
          const startDate = task.start_date ? new Date(task.start_date) : (task.created_at ? new Date(task.created_at) : new Date());
          const dueDate = new Date(task.due_date);
          const duration = Math.ceil((dueDate - startDate) / (1000 * 60 * 60 * 24));
          
          // Calculate new start and due dates from pixel positions
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
          // Update start date only
          const newStart = calculateDateFromPixelPosition(finalLeftPx);
          
          await fetch(`/todos/${task.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ start_date: newStart.toISOString() })
          });
          
        } else if (type === 'resize-end') {
          // Update due date only
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

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && detailCard.classList.contains('active')) {
        closeTaskDetail();
      }
    });

    // Initial refresh to normalize server state
    refresh();
  </script>
</body>
</html>
HTML;

        return $head . "\n    " . $rows . "\n" . $tail;
    }

    /**
     * GET /todos/{id}
     * 
     * @param array $params Route parameters
     * @return array|null Todo item or error response
     */
    public function show(array $params): ?array
    {
        $id = $this->validateId($params['id'] ?? null);
        if ($id === null) {
            return json_response(['error' => 'Invalid id'], 400);
        }
        
        $todo = $this->repo->findById($id);
        if (!$todo) {
            return json_response(['error' => 'Not found'], 404);
        }
        
        return $todo;
    }
    
    /**
     * Validate and convert ID parameter
     * 
     * @param mixed $id ID to validate
     * @return int|null Valid ID or null if invalid
     */
    private function validateId($id): ?int
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        return $id > 0 ? $id : null;
    }

    /**
     * POST /todos
     * Create a new todo item
     * 
     * @return array Created todo or error response
     */
    public function create(): array
    {
        $body = read_json_input();
        $validationResult = $this->validateTodoData($body);
        
        if (isset($validationResult['error'])) {
            return json_response($validationResult, 400);
        }
        
        $created = $this->repo->create(
            $validationResult['title'],
            $validationResult['completed'],
            $validationResult['description'],
            $validationResult['start_date'],
            $validationResult['due_date'],
            $validationResult['priority']
        );
        
        return json_response($created, 201);
    }
    
    /**
     * Validate todo input data
     * 
     * @param array $data Input data to validate
     * @return array Validated data or error
     */
    private function validateTodoData(array $data): array
    {
        // Validate title (required)
        $title = isset($data['title']) && is_string($data['title']) ? trim($data['title']) : '';
        if ($title === '') {
            return ['error' => 'title is required'];
        }
        
        // Process other fields
        $completed = isset($data['completed']) ? (bool)$data['completed'] : false;
        $description = isset($data['description']) && is_string($data['description']) ? trim($data['description']) : '';
        $startDateIso = isset($data['start_date']) && is_string($data['start_date']) && $data['start_date'] !== '' ? $data['start_date'] : null;
        $dueDateIso = isset($data['due_date']) && is_string($data['due_date']) && $data['due_date'] !== '' ? $data['due_date'] : null;
        $priority = isset($data['priority']) ? (int)$data['priority'] : 0;
        
        // Validate date formats if provided
        if ($startDateIso !== null && !$this->isValidIsoDate($startDateIso)) {
            return ['error' => 'start_date must be a valid ISO date string'];
        }
        
        if ($dueDateIso !== null && !$this->isValidIsoDate($dueDateIso)) {
            return ['error' => 'due_date must be a valid ISO date string'];
        }
        
        // Validate priority range
        if ($priority < 0 || $priority > 2) {
            return ['error' => 'priority must be between 0 and 2'];
        }
        
        return [
            'title' => $title,
            'completed' => $completed,
            'description' => $description,
            'start_date' => $startDateIso,
            'due_date' => $dueDateIso,
            'priority' => $priority
        ];
    }
    
    /**
     * Check if a string is a valid ISO date
     * 
     * @param string $dateString Date string to validate
     * @return bool True if valid ISO date
     */
    private function isValidIsoDate(string $dateString): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/', $dateString)) {
            $date = date_create($dateString);
            return $date !== false && date_format($date, 'Y-m-d\TH:i:s\Z') !== false;
        }
        return false;
    }

    /**
     * PUT /todos/{id}
     * Update an existing todo item
     * 
     * @param array $params Route parameters
     * @return array|null Updated todo or error response
     */
    public function update(array $params): ?array
    {
        $id = $this->validateId($params['id'] ?? null);
        if ($id === null) {
            return json_response(['error' => 'Invalid id'], 400);
        }
        
        $body = read_json_input();
        $validationResult = $this->validateUpdateData($body);
        
        if (isset($validationResult['error'])) {
            return json_response($validationResult, 400);
        }
        
        $updated = $this->repo->update(
            $id,
            $validationResult['title'] ?? null,
            $validationResult['completed'] ?? null,
            $validationResult['description'] ?? null,
            $validationResult['start_date'] ?? null,
            $validationResult['due_date'] ?? null,
            $validationResult['priority'] ?? null
        );
        
        if (!$updated) {
            return json_response(['error' => 'Not found'], 404);
        }
        
        return $updated;
    }
    
    /**
     * Validate todo update data
     * 
     * @param array $data Input data to validate
     * @return array Validated data or error
     */
    private function validateUpdateData(array $data): array
    {
        $result = [];
        $hasUpdates = false;
        
        // Process title if present
        if (array_key_exists('title', $data)) {
            $title = is_string($data['title']) ? trim($data['title']) : null;
            if ($title === '') {
                return ['error' => 'title cannot be empty'];
            }
            $result['title'] = $title;
            $hasUpdates = true;
        }
        
        // Process other fields if present
        if (array_key_exists('completed', $data)) {
            $result['completed'] = (bool)$data['completed'];
            $hasUpdates = true;
        }
        
        if (array_key_exists('description', $data)) {
            $result['description'] = is_string($data['description']) ? trim($data['description']) : null;
            $hasUpdates = true;
        }
        
        if (array_key_exists('start_date', $data)) {
            $startDateIso = is_string($data['start_date']) && $data['start_date'] !== '' ? $data['start_date'] : null;
            if ($startDateIso !== null && !$this->isValidIsoDate($startDateIso)) {
                return ['error' => 'start_date must be a valid ISO date string'];
            }
            $result['start_date'] = $startDateIso;
            $hasUpdates = true;
        }
        
        if (array_key_exists('due_date', $data)) {
            $dueDateIso = is_string($data['due_date']) && $data['due_date'] !== '' ? $data['due_date'] : null;
            if ($dueDateIso !== null && !$this->isValidIsoDate($dueDateIso)) {
                return ['error' => 'due_date must be a valid ISO date string'];
            }
            $result['due_date'] = $dueDateIso;
            $hasUpdates = true;
        }
        
        if (array_key_exists('priority', $data)) {
            $priority = (int)$data['priority'];
            if ($priority < 0 || $priority > 2) {
                return ['error' => 'priority must be between 0 and 2'];
            }
            $result['priority'] = $priority;
            $hasUpdates = true;
        }
        
        if (!$hasUpdates) {
            return ['error' => 'Nothing to update'];
        }
        
        return $result;
    }

    /**
     * DELETE /todos/{id}
     * Delete a todo item
     * 
     * @param array $params Route parameters
     * @return array|null Null for success or error response
     */
    public function destroy(array $params): ?array
    {
        $id = $this->validateId($params['id'] ?? null);
        if ($id === null) {
            return json_response(['error' => 'Invalid id'], 400);
        }
        
        $deleted = $this->repo->delete($id);
        if (!$deleted) {
            return json_response(['error' => 'Not found'], 404);
        }
        
        http_response_code(204);
        return null; // no content
    }
}


