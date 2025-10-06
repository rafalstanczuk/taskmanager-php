# Running Tests

Quick guide to running the test suite.

## Quick Start

```bash
./scripts/run-tests.sh
```

This automated script handles all setup and runs the complete test suite.

## Test Suite Summary

- **Unit Tests**: 62 tests ✓
- **Integration Tests**: 25+ tests ✓
- **UI Specifications**: 21 tests ✓
- **Total**: 108+ tests, 200+ assertions

## Running All Tests

### Using Test Script (Recommended)

```bash
# Run all unit tests with automated setup
./scripts/run-tests.sh

# Skip integration tests (CI/CD mode)
./scripts/run-tests.sh --skip-integration

# Verbose output
./scripts/run-tests.sh --verbose

# Show help
./scripts/run-tests.sh --help
```

### Using Docker Compose

```bash
# All test suites
docker compose run --rm php vendor/bin/phpunit

# Unit tests only
docker compose run --rm php vendor/bin/phpunit --testsuite Unit

# Integration tests only
docker compose run --rm php vendor/bin/phpunit --testsuite Integration
```

### Using Make

```bash
make test              # Run unit tests
make test-coverage     # Run with coverage (requires xdebug)
make ci                # Full CI/CD pipeline
```

## Running Specific Tests

### Unit Tests

```bash
# All unit tests (62 tests)
docker compose run --rm php vendor/bin/phpunit --testsuite Unit
```

**Expected output:**
```
OK (62 tests, 161 assertions)
```

### Gantt Drag Logic Tests

```bash
# 19 tests for drag-and-drop logic
docker compose run --rm php vendor/bin/phpunit tests/Unit/GanttDragLogicTest.php --testdox
```

### Gantt Timeline Integration

```bash
# 10 tests for timeline functionality
docker compose run --rm php vendor/bin/phpunit tests/Integration/GanttTimelineTest.php --testdox
```

### UI Specifications

```bash
# 21 tests documenting user interactions
docker compose run --rm php vendor/bin/phpunit tests/UI/GanttDragDropTest.php --testdox
```

### Specific Test File

```bash
docker compose run --rm php vendor/bin/phpunit tests/Unit/TaskTest.php
docker compose run --rm php vendor/bin/phpunit tests/Unit/RouterTest.php
docker compose run --rm php vendor/bin/phpunit tests/Unit/TodoRepositoryTest.php
```

### Specific Test Method

```bash
docker compose run --rm php vendor/bin/phpunit \
  --filter testCreateTaskFromDatabaseRow \
  tests/Unit/TaskTest.php
```

## Output Formats

### Standard Output

```bash
docker compose run --rm php vendor/bin/phpunit --testsuite Unit
```

Output:
```
PHPUnit 10.5.0 by Sebastian Bergmann and contributors.

..............................................................  62 / 62 (100%)

Time: 00:01.234, Memory: 10.00 MB

OK (62 tests, 161 assertions)
```

### Detailed Output (--testdox)

```bash
docker compose run --rm php vendor/bin/phpunit --testdox
```

Output:
```
Task (Tests\Unit\TaskTest)
 ✔ Create task from database row
 ✔ Convert task to array
 ✔ Handle null values
 ...

Router (Tests\Unit\RouterTest)
 ✔ Extract path parameters
 ✔ Route without parameters
 ...
```

### Verbose Output

```bash
docker compose run --rm php vendor/bin/phpunit --verbose
```

Shows detailed information about each test execution.

## Test Script Options

The `./scripts/run-tests.sh` script supports:

| Option | Description |
|--------|-------------|
| `--skip-integration` | Skip integration tests (CI/CD mode) |
| `--verbose`, `-v` | Show verbose PHPUnit output |
| `--help`, `-h` | Display help message |

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `TEST_SERVER_PORT` | 8000 | Port for test server |
| `DB_READY_TIMEOUT` | 30 | Database ready timeout (seconds) |

Example:
```bash
TEST_SERVER_PORT=9000 ./scripts/run-tests.sh
```

## Manual Test Setup

If you prefer manual control:

```bash
# 1. Start database
docker compose up -d db

# 2. Wait for database (optional - tests will retry)
sleep 3

# 3. Run migrations (optional - tests handle this)
docker compose run --rm php php scripts/migrate.php

# 4. Run tests
docker compose run --rm php vendor/bin/phpunit
```

## CI/CD Mode

For continuous integration pipelines:

```bash
# Recommended for CI/CD
./scripts/run-tests.sh --skip-integration
```

This runs:
- All 62 unit tests
- Gantt drag logic tests
- No integration tests (avoid Docker networking issues)

## Test Coverage

### Unit Tests (62 tests)

- ✅ Connection: 7 tests
- ✅ Gantt Drag Logic: 19 tests
- ✅ Helper Functions: 5 tests
- ✅ Router: 8 tests
- ✅ Task Domain: 6 tests
- ✅ Todo Repository: 17 tests

### Integration Tests (25+ tests)

- ✅ Todo API: 15+ tests
- ✅ Gantt Timeline: 10 tests

### UI Specifications (21 tests)

- ✅ Gantt Drag & Drop: 21 tests

## Troubleshooting

### Database Connection Issues

```bash
# Check if database is running
docker compose ps

# View database logs
docker compose logs db

# Restart database
docker compose restart db

# Reset database
docker compose down -v
docker compose up -d db
```

### Test Failures

```bash
# Run specific failing test with verbose output
docker compose run --rm php vendor/bin/phpunit \
  --verbose \
  tests/Unit/TaskTest.php

# Check test database state
docker compose exec db psql -U app -d app -c "SELECT * FROM todos WHERE title LIKE 'TEST:%';"
```

### Port Conflicts

```bash
# Use different port
TEST_SERVER_PORT=9000 ./scripts/run-tests.sh

# Or check what's using port 8000
lsof -i :8000
```

### Composer Issues

```bash
# Reinstall dependencies
docker compose run --rm php composer install

# Update dependencies
docker compose run --rm php composer update
```

## Expected Test Results

All tests should pass with these results:

### Unit Tests
```
OK (62 tests, 161 assertions)
```

### Integration Tests
```
OK (10 tests, 40+ assertions)
```

### UI Specifications
```
OK (21 tests, 21 assertions)
```

## Quick Test Verification

### Minimal Verification (30 seconds)

```bash
# Just unit tests
docker compose run --rm php vendor/bin/phpunit --testsuite Unit
```

### Full Verification (1-2 minutes)

```bash
# All tests
./scripts/run-tests.sh
```

## Test Data

Tests use the `TEST:` prefix for all test data:
- Automatic cleanup
- No pollution of main data
- Independent test runs

## Performance

### Test Execution Times

- **Unit Tests**: ~1-2 seconds
- **Integration Tests**: ~5-10 seconds
- **All Tests**: ~10-15 seconds

### Optimizations

- Tests run in memory where possible
- Database transactions for isolation
- Minimal test data creation
- Parallel test execution (when configured)

## Development Workflow

### Before Committing

```bash
# Ensure all tests pass
./scripts/run-tests.sh
```

### During Development

```bash
# Run specific test file while developing
docker compose run --rm php vendor/bin/phpunit tests/Unit/MyTest.php
```

### Before Deployment

```bash
# Full test suite
make ci
```

## Related Documentation

- [Testing Guide](TESTING.md) - Complete test documentation
- [CI/CD Guide](CI_CD.md) - Automated testing setup
- [API Reference](API.md) - API endpoints for integration tests

---

**Quick Command**: `./scripts/run-tests.sh`  
**Expected Result**: All tests passing ✅  
**Time**: ~10-15 seconds
