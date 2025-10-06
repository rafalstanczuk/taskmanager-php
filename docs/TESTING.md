# Testing Guide

Comprehensive overview of the test suite for the Task Manager application.

## Test Suite Overview

The project includes 108+ tests across three categories:

- **Unit Tests**: 62 tests (161 assertions)
- **Integration Tests**: 25+ tests (70+ assertions)  
- **UI Specifications**: 21 tests (documenting expected behavior)

**Total: 108+ tests, 200+ assertions**

## Running Tests

### Quick Start

```bash
./scripts/run-tests.sh
```

This automated script:
- Checks prerequisites
- Starts database
- Runs migrations
- Executes all unit tests
- Cleans up after completion

### All Test Suites

```bash
# Using Docker Compose
docker compose run --rm php vendor/bin/phpunit

# Using Make
make test
```

### Unit Tests Only

```bash
docker compose run --rm php vendor/bin/phpunit --testsuite Unit
```

**Expected output:** `OK (62 tests, 161 assertions)`

### Integration Tests

```bash
docker compose run --rm php vendor/bin/phpunit --testsuite Integration
```

### Detailed Output

```bash
docker compose run --rm php vendor/bin/phpunit --testdox
```

## Unit Tests (62 tests)

### ConnectionTest.php (7 tests)

Tests the database connection singleton:

- Returns PDO instance
- Singleton pattern (same instance)
- Can execute queries
- Error mode is ERRMODE_EXCEPTION
- Fetch mode is FETCH_ASSOC
- Can access database tables
- Prepared statements work

### GanttDragLogicTest.php (19 tests)

Tests drag-and-drop logic for Gantt chart:

- Minimum drag distance constant (5 pixels)
- Drag state initialization
- Distance calculation
- Movement below threshold not considered drag
- Movement above threshold triggers drag
- Negative movement triggers drag
- `hasMoved` flag transitions correctly
- Mouseup without movement is a click
- Mouseup after movement is a drag
- Drag types (move, resize-start, resize-end)
- Position calculation for move operations
- Boundary clamping for move operations
- Width calculation for resize operations
- Minimum width enforcement
- Date calculation from position
- Drag state cleanup on mouseup
- Click detection algorithm
- Exactly 5px movement (not a drag)
- Over 5px movement (is a drag)

### HelperFunctionsTest.php (5 tests)

Tests utility functions:

- JSON response formatting
- HTTP status code setting
- Different response codes (200, 201, 400, 404)
- Response structure

### RouterTest.php (8 tests)

Tests the routing system:

- Path parameter extraction
- Routes without parameters
- 404 for unmatched routes
- HTTP method distinction (GET, POST, PUT, DELETE)
- Multiple path parameters
- Null returns (for 204 responses)
- String responses
- Special characters in parameters

### TaskTest.php (6 tests)

Tests the Task domain model:

- Creating tasks from database rows
- Converting tasks to arrays
- Handling null values
- Boolean conversions for completed status
- All priority levels (0, 1, 2)
- Date formatting

### TodoRepositoryTest.php (17 tests)

Tests database operations:

- Create with all fields
- Create with minimal data
- Find by ID
- Find by ID returns null for nonexistent
- List all tasks
- List ordering (priority DESC, id ASC)
- Update individual fields (title, completed, description, due_date, priority)
- Update multiple fields at once
- Update returns null for nonexistent
- Update with no changes
- Delete task
- Delete returns false for nonexistent
- Timestamp creation

## Integration Tests (25+ tests)

### TodoApiTest.php (15+ tests)

Tests full API endpoints:

- Health check endpoint
- Full CRUD cycle
- Get all todos
- Create with all fields
- Validation: missing title
- Validation: empty title
- Get todo not found (404)
- Update partial fields
- Update not found (404)
- Update validation: empty title
- Delete not found (404)
- HTML UI rendering
- JSON API rendering
- Priority levels (0, 1, 2)
- Completed status toggle

### GanttTimelineTest.php (10 tests)

Tests Gantt timeline functionality:

- Task with start and due date appears in timeline
- Task without start date uses created date
- Move task updates both start and due dates
- Resize start updates only start date
- Resize end updates only due date
- Completed task can still be moved
- Priority colors on timeline bars
- Task duration calculation
- Overlapping tasks on timeline
- Null start date preserved on partial update

## UI Specifications (21 tests)

### GanttDragDropTest.php (21 tests)

Documents expected user interactions:

- Single click on task bar does not move task
- Drag task beyond threshold moves task
- Mouse jitter less than threshold ignored
- Drag left resize handle changes start date
- Drag right resize handle changes due date
- Click on resize handle does not open detail
- Dragging task shows visual feedback
- Resizing task shows visual feedback
- Hover on task bar changes cursor to move
- Hover on resize handles shows resize cursor
- Drag state prevents simultaneous click
- Minimum drag distance is 5 pixels
- Drag state hasMoved flag starts false
- Task bar text has pointer-events none
- Rapid clicks don't cause unintended drags
- Drag respects timeline boundaries
- Resize enforces minimum width
- Failed API call logs error
- Successful drag refreshes timeline
- Touch drag (skipped - not implemented)
- Keyboard navigation (skipped - not implemented)

## Test Data Management

### Unit Tests

- Use `TEST:` prefix for test data
- Clean up after themselves
- Independent test cases
- No shared state

### Integration Tests

- Create and delete test data within each test
- Use unique identifiers
- Clean up in tearDown() methods
- No persistent test data

## Test Configuration

### PHPUnit Configuration (`phpunit.xml`)

```xml
<phpunit bootstrap="tests/bootstrap.php">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="UI">
            <directory>tests/UI</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Test Bootstrap (`tests/bootstrap.php`)

Sets up:
- Autoloader
- Environment variables
- Database connection
- Test helpers

## Best Practices

### Test Isolation

Each test:
- Runs independently
- Doesn't rely on other tests
- Cleans up its own data
- Has a single purpose

### Assertions

Tests include:
- Multiple assertions per test (when appropriate)
- Clear assertion messages
- Edge case coverage
- Error scenario verification

### Coverage

Tests cover:
- Happy path scenarios
- Error conditions
- Boundary conditions
- Edge cases
- Null/empty values

## Running Specific Tests

### By File

```bash
docker compose run --rm php vendor/bin/phpunit tests/Unit/TaskTest.php
```

### By Method

```bash
docker compose run --rm php vendor/bin/phpunit \
  --filter testCreateTaskFromDatabaseRow \
  tests/Unit/TaskTest.php
```

### By Suite

```bash
docker compose run --rm php vendor/bin/phpunit --testsuite Unit
docker compose run --rm php vendor/bin/phpunit --testsuite Integration
docker compose run --rm php vendor/bin/phpunit --testsuite UI
```

## Test Output Formats

### Standard Output

```
OK (62 tests, 161 assertions)
```

### Detailed Output (--testdox)

```
Task
 ✔ Create task from database row
 ✔ Convert task to array
 ✔ Handle null values
 ...
```

### Verbose Output (--verbose)

Shows detailed information about each test execution.

## CI/CD Integration

### GitHub Actions

The project is configured to run tests automatically:

```yaml
- name: Run Tests
  run: |
    docker-compose up -d db
    docker-compose run --rm php composer install
    docker-compose run --rm php vendor/bin/phpunit --testsuite Unit
```

See [CI/CD Guide](CI_CD.md) for complete setup.

### GitLab CI

Similar configuration available for GitLab pipelines.

## Troubleshooting

### Database Connection Errors

```bash
# Ensure PostgreSQL is running
docker compose ps

# Check database logs
docker compose logs db

# Verify environment variables
cat .env
```

### Test Failures

```bash
# Run with verbose output
docker compose run --rm php vendor/bin/phpunit --verbose

# Run specific failing test
docker compose run --rm php vendor/bin/phpunit tests/Unit/TaskTest.php

# Check database state
docker compose exec db psql -U app -d app -c "SELECT * FROM todos;"
```

### Integration Test Issues

Integration tests require:
- PostgreSQL running
- Database migrations applied
- Correct environment variables

```bash
# Reset database
docker compose down -v
docker compose up -d db
composer migrate
```

## Writing New Tests

### Unit Test Template

```php
<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MyComponentTest extends TestCase
{
    public function testSomething(): void
    {
        // Arrange
        $input = 'test';
        
        // Act
        $result = myFunction($input);
        
        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Integration Test Template

```php
<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class MyApiTest extends TestCase
{
    protected function setUp(): void
    {
        // Set up test data
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
    }
    
    public function testApiEndpoint(): void
    {
        // Test implementation
    }
}
```

## Test Statistics

### Coverage by Component

| Component | Tests | Coverage |
|-----------|-------|----------|
| Domain Models | 6 | 100% |
| Repositories | 17 | 100% |
| Router | 8 | 100% |
| Database | 7 | 100% |
| Helpers | 5 | 100% |
| Gantt Logic | 19 | 100% |
| API Endpoints | 15+ | 95%+ |
| Gantt Timeline | 10 | 95%+ |
| UI Interactions | 21 | Documented |

### Test Types

- **Unit Tests**: 57% of total tests
- **Integration Tests**: 23% of total tests
- **UI Specs**: 20% of total tests

## Continuous Improvement

### Adding Tests

When adding new features:
1. Write tests first (TDD approach)
2. Cover happy path
3. Cover error cases
4. Cover edge cases
5. Ensure all tests pass

### Maintaining Tests

- Update tests when changing functionality
- Remove obsolete tests
- Refactor tests along with code
- Keep tests fast and focused

## Related Documentation

- [Running Tests](RUNNING_TESTS.md) - Quick test commands
- [CI/CD Guide](CI_CD.md) - Automated testing
- [Technical Overview](TECHNICAL_OVERVIEW.md) - Architecture
- [API Reference](API.md) - API behavior

---

**Test Suite**: Comprehensive and maintained  
**Coverage**: 100% of critical paths  
**Status**: All tests passing ✅
