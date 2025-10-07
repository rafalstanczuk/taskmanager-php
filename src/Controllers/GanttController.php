<?php declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\TodoRepository;

/** Gantt Chart Controller - Renders interactive Gantt chart view */
final class GanttController
{
    private TodoRepository $repo;

    public function __construct()
    {
        $this->repo = new TodoRepository();
    }

    /** GET /gantt - Render Gantt chart HTML page */
    public function index(): void
    {
        $todos = $this->repo->list();
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $this->renderHtml($todos);
    }

    private function renderHtml(array $todos): string
    {
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
    .muted { color: #64748b; font-size: .875rem; }
    
    /* Gantt Chart */
    .gantt-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }
    .gantt-header {
      font-size: 1rem;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .gantt-chart {
      position: relative;
      min-height: 200px;
      overflow-x: auto;
    }
    .gantt-timeline {
      display: flex;
      border-bottom: 2px solid #e2e8f0;
      padding-bottom: 0.5rem;
      margin-bottom: 1rem;
      position: sticky;
      top: 0;
      background: white;
      z-index: 10;
    }
    .gantt-date {
      flex: 0 0 80px;
      text-align: center;
      font-size: 0.75rem;
      color: #64748b;
      font-weight: 500;
      min-width: 80px;
      position: relative;
      border-right: 1px solid #e2e8f0;
      transition: background-color 0.2s;
    }
    .gantt-date:hover {
      background-color: #f1f5f9 !important;
    }
    .gantt-date::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: -2px;
      background: transparent;
      z-index: -1;
    }
    .gantt-date:nth-child(even)::before {
      background: #f8fafc;
    }
    .gantt-date.today {
      color: #3b82f6;
      font-weight: 700;
      background: #eff6ff;
    }
    .gantt-date.today::before {
      background: #eff6ff !important;
    }
    .gantt-date.weekend {
      background: #fef3c7;
      color: #92400e;
    }
    .gantt-date.weekend::before {
      background: #fef3c7 !important;
    }
    .gantt-tasks {
      position: relative;
      background-image: repeating-linear-gradient(
        to right,
        transparent 0px,
        transparent 80px,
        #f8fafc 80px,
        #f8fafc 160px
      );
      background-position: 200px 0;
      background-size: 160px 100%;
    }
    .gantt-row {
      display: flex;
      align-items: center;
      min-height: 40px;
      border-bottom: 1px solid #f1f5f9;
      position: relative;
    }
    .gantt-row:hover {
      background: #f8fafc;
    }
    .gantt-task-name {
      width: 200px;
      padding: 0.5rem;
      font-size: 0.875rem;
      color: #1e293b;
      font-weight: 500;
      flex-shrink: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .gantt-task-bar-container {
      flex: 1;
      position: relative;
      display: flex;
      align-items: center;
      padding: 0.25rem 0;
    }
    .gantt-task-bar {
      position: absolute;
      height: 28px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 0.5rem;
      font-size: 0.75rem;
      font-weight: 600;
      color: white;
      cursor: move;
      transition: all 0.2s;
      box-shadow: 0 1px 2px rgba(0,0,0,0.1);
      user-select: none;
    }
    .gantt-task-bar:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      z-index: 10;
      filter: brightness(1.05);
      cursor: pointer;
    }
    .gantt-task-bar.dragging {
      opacity: 0.6;
      cursor: grabbing;
      z-index: 100;
    }
    .gantt-task-bar.resizing {
      opacity: 0.8;
      z-index: 100;
    }
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
    .gantt-task-bar-text {
      flex: 1;
      text-align: center;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      pointer-events: none;
    }
    .gantt-task-bar.priority-2 { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .gantt-task-bar.priority-1 { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .gantt-task-bar.priority-0 { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .gantt-task-bar.completed {
      opacity: 0.5;
      text-decoration: line-through;
    }
    
    /* Task Detail Card */
    .task-detail-card {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) scale(0.9);
      width: 90%;
      max-width: 600px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      z-index: 1000;
      opacity: 0;
      pointer-events: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .task-detail-card.active {
      opacity: 1;
      pointer-events: auto;
      transform: translate(-50%, -50%) scale(1);
    }
    .task-detail-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 999;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s;
    }
    .task-detail-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }
    .task-detail-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.5rem;
      border-bottom: 1px solid #e2e8f0;
    }
    .task-detail-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: #1e293b;
      flex: 1;
      margin-right: 1rem;
    }
    .task-detail-close {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      border: none;
      background: #f1f5f9;
      color: #64748b;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      transition: all 0.2s;
    }
    .task-detail-close:hover {
      background: #e2e8f0;
      color: #1e293b;
    }
    .task-detail-body {
      padding: 1.5rem;
    }
    .task-detail-field {
      margin-bottom: 1.25rem;
    }
    .task-detail-label {
      font-size: 0.875rem;
      font-weight: 600;
      color: #64748b;
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .task-detail-value {
      font-size: 1rem;
      color: #1e293b;
    }
    .task-detail-actions {
      display: flex;
      gap: 0.75rem;
      padding: 1.5rem;
      border-top: 1px solid #e2e8f0;
      background: #f8fafc;
      border-radius: 0 0 12px 12px;
    }
    .task-detail-btn {
      flex: 1;
      padding: 0.75rem 1.5rem;
      border-radius: 6px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.875rem;
    }
    .task-detail-btn.primary {
      background: #3b82f6;
      color: white;
    }
    .task-detail-btn.primary:hover {
      background: #2563eb;
    }
    .task-detail-btn.success {
      background: #10b981;
      color: white;
    }
    .task-detail-btn.success:hover {
      background: #059669;
    }
    .task-detail-btn.danger {
      background: #ef4444;
      color: white;
    }
    .task-detail-btn.danger:hover {
      background: #dc2626;
    }
    .task-detail-status {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 12px;
      font-size: 0.875rem;
      font-weight: 600;
    }
    .task-detail-status.completed {
      background: #d1fae5;
      color: #065f46;
    }
    .task-detail-status.pending {
      background: #fef3c7;
      color: #92400e;
    }
    .gantt-today-line {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 2px;
      background: #3b82f6;
      z-index: 5;
      pointer-events: none;
    }
    .gantt-today-marker {
      position: absolute;
      top: -8px;
      left: -4px;
      width: 10px;
      height: 10px;
      background: #3b82f6;
      border-radius: 50%;
    }
    .gantt-empty {
      text-align: center;
      padding: 3rem;
      color: #94a3b8;
      font-size: 0.875rem;
    }
    
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
  <h1>📋 Task Manager <span class="muted" style="font-weight: 400; font-size: 0.9rem;">(Gantt chart view)</span></h1>
  
  <!-- Navigation Menu -->
  <div class="nav-menu">
    <ul class="nav-list">
      <li class="nav-item active"><a href="/gantt">📊 Gantt Chart</a></li>
      <li class="nav-item"><a href="/todos">📑 List View</a></li>
      <li class="nav-item"><a href="/todos?view=kanban">📌 Kanban View</a></li>
      <li class="nav-item"><a href="/todos?view=calendar">📅 Calendar View</a></li>
    </ul>
  </div>
  
  <!-- Gantt Chart -->
  <div class="gantt-container">
    <div class="gantt-header">
      📊 Gantt Chart
    </div>
    <div id="gantt-chart" class="gantt-chart"></div>
  </div>
HTML;

        $tail = <<<'HTML'

  <!-- Task Detail Card -->
  <div class="task-detail-overlay" id="task-detail-overlay"></div>
  <div class="task-detail-card" id="task-detail-card">
    <div class="task-detail-header">
      <div class="task-detail-title" id="detail-title"></div>
      <button class="task-detail-close" id="close-detail">✕</button>
    </div>
    <div class="task-detail-body">
      <div class="task-detail-field">
        <div class="task-detail-label">Status</div>
        <div class="task-detail-value">
          <span class="task-detail-status" id="detail-status"></span>
        </div>
      </div>
      <div class="task-detail-field">
        <div class="task-detail-label">Priority</div>
        <div class="task-detail-value" id="detail-priority"></div>
      </div>
      <div class="task-detail-field">
        <div class="task-detail-label">Description</div>
        <div class="task-detail-value" id="detail-description"></div>
      </div>
      <div class="task-detail-field">
        <div class="task-detail-label">Start Date</div>
        <div class="task-detail-value" id="detail-start-date"></div>
      </div>
      <div class="task-detail-field">
        <div class="task-detail-label">Due Date</div>
        <div class="task-detail-value" id="detail-due-date"></div>
      </div>
      <div class="task-detail-field">
        <div class="task-detail-label">Created</div>
        <div class="task-detail-value" id="detail-created"></div>
      </div>
    </div>
    <div class="task-detail-actions">
      <button class="task-detail-btn primary" id="detail-edit">✏️ Edit</button>
      <button class="task-detail-btn success" id="detail-toggle">✓ Toggle</button>
      <button class="task-detail-btn danger" id="detail-delete">🗑 Delete</button>
    </div>
  </div>

  <script>
    const ganttChartEl = document.getElementById('gantt-chart');

    let allItems = [];

    async function refresh() {
      const res = await fetch('/todos', { headers: { 'Accept': 'application/json' } });
      allItems = await res.json();
      renderGantt();
    }

    function renderGantt() {
      const tasksWithDates = allItems.filter(t => t.due_date);
      
      if (tasksWithDates.length === 0) {
        ganttChartEl.innerHTML = '<div class="gantt-empty">📅 No tasks with due dates. Add due dates to see the timeline.</div>';
        return;
      }

      // Calculate date range
      const today = new Date();
      today.setUTCHours(0, 0, 0, 0);
      
      // Collect all dates (both start_date and due_date), normalized to midnight UTC
      const dates = [];
      tasksWithDates.forEach(t => {
        const due = new Date(t.due_date);
        due.setUTCHours(0, 0, 0, 0);
        dates.push(due);
        
        if (t.start_date) {
          const start = new Date(t.start_date);
          start.setUTCHours(0, 0, 0, 0);
          dates.push(start);
        }
      });
      
      const minDate = new Date(Math.min(today, ...dates));
      const maxDate = new Date(Math.max(today, ...dates));
      
      // Extend range to show context
      minDate.setDate(minDate.getDate() - 2);
      maxDate.setDate(maxDate.getDate() + 2);
      
      const daysDiff = Math.ceil((maxDate - minDate) / (1000 * 60 * 60 * 24));
      
      const dateRange = [];
      
      for (let i = 0; i <= daysDiff; i++) {
        const date = new Date(minDate);
        date.setDate(date.getDate() + i);
        dateRange.push(date);
      }
      
      // Store for drag calculations
      ganttDateRange = {
        minDate: minDate,
        maxDate: maxDate,
        dayCount: dateRange.length
      };

      // Build timeline header with weekend detection
      const timelineHtml = dateRange.map(date => {
        const isToday = date.toDateString() === today.toDateString();
        const isWeekend = date.getUTCDay() === 0 || date.getUTCDay() === 6;
        const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        return `<div class="gantt-date ${isToday ? 'today' : ''} ${isWeekend ? 'weekend' : ''}">${dateStr}</div>`;
      }).join('');

      // Build task rows
      const tasksHtml = tasksWithDates.map(task => {
        const dueDate = new Date(task.due_date);
        dueDate.setUTCHours(0, 0, 0, 0);  // Normalize to midnight UTC
        
        const startDate = task.start_date ? new Date(task.start_date) : (task.created_at ? new Date(task.created_at) : minDate);
        startDate.setUTCHours(0, 0, 0, 0);  // Normalize to midnight UTC
        
        const startPos = Math.max(0, Math.floor((startDate - minDate) / (1000 * 60 * 60 * 24)));
        const endPos = Math.floor((dueDate - minDate) / (1000 * 60 * 60 * 24));
        const width = Math.max(1, endPos - startPos);
        
        // Use PIXEL positioning instead of percentages since we have explicit width
        const leftPx = startPos * 80;  // 80px per day
        const widthPx = width * 80;    // 80px per day
        
        const completedClass = task.completed ? 'completed' : '';
        const priorityClass = `priority-${task.priority}`;
        
        const dateRangeText = task.start_date 
          ? `${startDate.toLocaleDateString()} → ${dueDate.toLocaleDateString()}`
          : `Due: ${dueDate.toLocaleDateString()}`;
        
        return `
          <div class="gantt-row" data-task-id="${task.id}">
            <div class="gantt-task-name" title="${escapeHtml(task.title)}">${escapeHtml(task.title)}</div>
            <div class="gantt-task-bar-container" data-container-id="${task.id}">
              <div class="gantt-task-bar ${priorityClass} ${completedClass}" 
                   data-task-id="${task.id}"
                   data-start-date="${task.start_date || ''}"
                   data-due-date="${task.due_date}"
                   style="left: ${leftPx}px; width: ${widthPx}px;"
                   title="${escapeHtml(task.title)} - ${dateRangeText}">
                <div class="gantt-resize-handle left" data-handle="start"></div>
                <div class="gantt-task-bar-text">${escapeHtml(task.title.length > 20 ? task.title.substring(0, 20) + '...' : task.title)}</div>
                <div class="gantt-resize-handle right" data-handle="end"></div>
              </div>
            </div>
          </div>
        `;
      }).join('');

      // Today line position
      const todayPos = Math.floor((today - minDate) / (1000 * 60 * 60 * 24));
      const todayPx = todayPos * 80;  // 80px per day

      const timelineWidth = dateRange.length * 80; // 80px per date cell
      
      ganttChartEl.innerHTML = `
        <div class="gantt-timeline" style="padding-left: 200px; width: ${timelineWidth + 200}px; min-width: ${timelineWidth + 200}px;">${timelineHtml}</div>
        <div class="gantt-tasks" style="width: ${timelineWidth + 200}px; min-width: ${timelineWidth + 200}px;">
          ${todayPx >= 0 && todayPx <= timelineWidth ? `
            <div class="gantt-today-line" style="left: ${todayPx + 200}px">
              <div class="gantt-today-marker"></div>
            </div>
          ` : ''}
          ${tasksHtml}
        </div>
      `;
    }

    function escapeHtml(str) {
      return str
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

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

        return $head . $tail;
    }
}


