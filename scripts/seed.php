<?php declare(strict_types=1);

use App\Repositories\TodoRepository;

// Autoload
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $path = __DIR__ . '/../src/' . str_replace('App\\', '', $class) . '.php';
            $path = str_replace('\\', '/', $path);
            if (is_file($path)) {
                require $path;
            }
        }
    });
}
require __DIR__ . '/../config/bootstrap.php';

$repo = new TodoRepository();

$now = new DateTimeImmutable('now');
$mkDate = function (?string $rel) use ($now): ?string {
    if ($rel === null || $rel === '') { return null; }
    return $now->modify($rel)->format(DateTimeImmutable::ATOM);
};

// Seeds format: [title, description, completed, start_rel, due_rel, priority]
// 20 tasks covering exactly 2 months (60 days: -30 days to +30 days from today)
$seeds = [
    // Completed tasks (past month)
    ['Setup development environment', 'Install Docker, PHP, PostgreSQL', true, '-30 days', '-28 days', 1],
    ['Create database schema', 'Design tables and relationships', true, '-27 days', '-25 days', 2],
    ['Implement basic CRUD', 'Task creation and listing', true, '-24 days', '-20 days', 2],
    ['Add authentication', 'User login and JWT tokens', true, '-19 days', '-15 days', 2],
    ['Write unit tests', 'Test core functionality', true, '-14 days', '-11 days', 1],
    
    // In progress tasks (recent past to near future)
    ['Design Gantt chart UI', 'Wireframes and mockups', false, '-10 days', '-6 days', 1],
    ['Implement drag-drop', 'Task rescheduling on timeline', false, '-5 days', 'today', 2],
    ['Add resize handles', 'Adjust task start/end dates', false, '-3 days', '+2 days', 2],
    ['Fix click detection', 'Prevent accidental drags', false, '-1 day', '+3 days', 2],
    ['Update documentation', 'API and feature guides', false, 'today', '+4 days', 1],
    
    // Upcoming tasks (next 2 weeks)
    ['Add task filtering', 'By priority, status, date range', false, '+3 days', '+7 days', 1],
    ['Implement search', 'Full-text task search', false, '+6 days', '+10 days', 1],
    ['Add bulk operations', 'Multi-task edit and delete', false, '+9 days', '+13 days', 0],
    ['Create export feature', 'Export to CSV/PDF', false, '+11 days', '+15 days', 0],
    ['Add notifications', 'Due date reminders', false, '+14 days', '+18 days', 1],
    
    // Future tasks (next month)
    ['Implement webhooks', 'Task update notifications', false, '+16 days', '+20 days', 1],
    ['Add team collaboration', 'Shared tasks and comments', false, '+19 days', '+24 days', 2],
    ['Performance optimization', 'Database indexing and caching', false, '+22 days', '+26 days', 1],
    ['Mobile responsive design', 'Tablet and phone support', false, '+25 days', '+28 days', 1],
    ['Deploy to production', 'Setup CI/CD and monitoring', false, '+27 days', '+30 days', 2],
];

$created = 0;
foreach ($seeds as [$title, $desc, $completed, $startRel, $dueRel, $priority]) {
    $start = $mkDate($startRel);
    $due = $mkDate($dueRel);
    $repo->create($title, (bool)$completed, (string)$desc, $start, $due, (int)$priority);
    $created++;
}

fwrite(STDOUT, "Seeded {$created} tasks.\n");


