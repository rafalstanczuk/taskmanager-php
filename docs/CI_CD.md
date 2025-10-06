# CI/CD Integration

Continuous Integration and Deployment setup for the Task Manager application.

## Overview

The project includes production-ready CI/CD configurations for:
- **GitHub Actions** - Automated testing on push/PR
- **GitLab CI** - Multi-stage pipeline
- **Make Commands** - Local CI simulation

## Quick Start

### Local CI/CD Simulation

```bash
# Run full CI/CD pipeline locally
make ci

# Or use the test script
./scripts/run-tests.sh --skip-integration
```

### GitHub Actions

Automatically runs on:
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop`
- Manual workflow dispatch

### GitLab CI

Multi-stage pipeline with build, test, and deploy stages.

## Test Automation Script

### Features

The `./scripts/run-tests.sh` script provides:

✅ **Robust Error Handling**
- Exits on any error
- Proper cleanup on failure
- Clear error messages

✅ **Automatic Cleanup**
- Removes containers on exit
- Cleans up test data
- No resource leaks

✅ **Prerequisites Check**
- Validates Docker installation
- Checks Docker Compose
- Verifies project files

✅ **Database Health Checks**
- Waits for PostgreSQL ready
- Configurable timeout (default: 30s)
- Connection retry logic

✅ **Dependency Management**
- Auto-installs Composer dependencies
- Cached vendor directory
- Optimized for CI speed

✅ **Colored Output**
- Clear status indicators
- Professional logging
- Easy to read results

### Usage

```bash
# Standard run
./scripts/run-tests.sh

# CI/CD mode (skip integration tests)
./scripts/run-tests.sh --skip-integration

# Verbose output
./scripts/run-tests.sh --verbose

# Custom configuration
TEST_SERVER_PORT=9000 DB_READY_TIMEOUT=60 ./scripts/run-tests.sh
```

### Options

| Option | Description |
|--------|-------------|
| `--skip-integration` | Skip integration tests (recommended for CI) |
| `--verbose`, `-v` | Show verbose PHPUnit output |
| `--help`, `-h` | Display help message |

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `TEST_SERVER_PORT` | 8000 | Port for test server |
| `DB_READY_TIMEOUT` | 30 | Database ready timeout (seconds) |

## GitHub Actions

### Configuration

File: `.github/workflows/tests.yml`

### Workflow Features

✅ **Service Containers**
- PostgreSQL 16 with health checks
- Correct credentials (app/app/app)
- Persistent database for test run

✅ **Docker Optimization**
- Build caching enabled
- Layer reuse for faster builds
- Optimized image building

✅ **Test Execution**
- 62 unit tests
- 19 Gantt drag logic tests (with --testdox)
- 10 Gantt timeline tests (with --testdox)
- **Total: 72 automated tests**

✅ **Job Summary**
- Formatted test statistics
- Visual test breakdown
- Clear pass/fail indicators

✅ **Artifacts**
- Test results uploaded
- Available for debugging
- Retained for 30 days

### Trigger Events

```yaml
on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]
  workflow_dispatch:  # Manual trigger
```

### Workflow Steps

1. Checkout code
2. Setup Docker Buildx with caching
3. Build PHP Docker image
4. Install Composer dependencies (optimized)
5. Create .env with correct credentials
6. Wait for PostgreSQL (PHP PDO test)
7. Run database migrations
8. Run unit tests (62 tests)
9. Run Gantt drag logic tests (19 tests, --testdox)
10. Run Gantt timeline tests (10 tests, --testdox)
11. Generate GitHub job summary
12. Upload test artifacts

### Database Configuration

```yaml
services:
  postgres:
    image: postgres:16
    env:
      POSTGRES_USER: app
      POSTGRES_PASSWORD: app
      POSTGRES_DB: app
    options: >-
      --health-cmd "pg_isready -U app -d app"
      --health-interval 10s
      --health-timeout 5s
      --health-retries 5
```

### Environment Variables

```yaml
env:
  DB_HOST: postgres
  DB_PORT: 5432
  DB_NAME: app
  DB_USER: app
  DB_PASSWORD: app
```

### Database Wait Strategy

```bash
# PHP-based wait (works in any PHP container)
for i in {1..30}; do
  docker compose run --rm php php -r \
    "new PDO('pgsql:host=postgres;port=5432;dbname=app', 'app', 'app');" \
    && break || sleep 1
done
```

### Job Summary Example

```markdown
## 🧪 Test Results

### ✅ Unit Tests
- Total: 62 tests
- Connection: 7 tests
- **Gantt Drag Logic: 19 tests**
- Helper Functions: 5 tests
- Router: 8 tests
- Task: 6 tests
- Repository: 17 tests

### ✅ Integration Tests
- Gantt Timeline: 10 tests

**Total: 72 automated tests passing** 🎉
```

## GitLab CI

### Configuration

File: `.gitlab-ci.yml`

### Pipeline Stages

1. **Build** - Build Docker images, install dependencies
2. **Test** - Run unit tests, syntax checks
3. **Deploy** - Ready for deployment logic

### Features

- Multi-stage pipeline
- PostgreSQL service integration
- Test coverage reporting
- JUnit report artifacts
- Cached vendor dependencies

### Example Configuration

```yaml
test:
  stage: test
  services:
    - postgres:16
  variables:
    POSTGRES_DB: app
    POSTGRES_USER: app
    POSTGRES_PASSWORD: app
    DB_HOST: postgres
  script:
    - composer install
    - php scripts/migrate.php
    - vendor/bin/phpunit --testsuite Unit
  coverage: '/Lines:\s*\d+\.\d+\%/'
  artifacts:
    reports:
      junit: phpunit-report.xml
```

## Makefile Commands

### Available Commands

```bash
make help           # Show all commands
make setup          # Complete project setup
make test           # Run tests
make test-unit      # Run unit tests only
make test-coverage  # Run with coverage
make serve          # Start development server
make migrate        # Run migrations
make seed           # Seed database
make ci             # Run CI/CD pipeline locally
```

### CI Command

```bash
make ci
```

Runs:
1. Dependency installation
2. Database setup
3. Migrations
4. All tests
5. Code quality checks

## Best Practices

### For CI/CD Pipelines

✅ **Use Automated Script**
```bash
./scripts/run-tests.sh --skip-integration
```

✅ **Environment Isolation**
- Use Docker containers
- Fresh database for each run
- No shared state

✅ **Fail Fast**
```bash
set -euo pipefail  # Exit on error
```

✅ **Proper Cleanup**
```bash
trap cleanup EXIT INT TERM
```

✅ **Health Checks**
- Database readiness verification
- Configurable timeouts
- Clear error messages

### Database Management

✅ **Non-Interactive Mode**
```bash
composer install --no-interaction --prefer-dist --optimize-autoloader
```

✅ **Health Checks**
```bash
docker compose exec -T db pg_isready -U app -d app
```

✅ **Idempotent Migrations**
- Safe to run multiple times
- Creates tables if not exist
- No destructive operations

## Performance Optimization

### Docker Layer Caching

```yaml
- uses: actions/cache@v3
  with:
    path: /tmp/.buildx-cache
    key: ${{ runner.os }}-buildx-${{ github.sha }}
    restore-keys: |
      ${{ runner.os }}-buildx-
```

### Composer Optimization

```bash
composer install \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader \
  --classmap-authoritative
```

### Parallel Execution

- Tests run in isolated containers
- No shared state between runs
- Concurrent job execution where possible

## Test Coverage

### Unit Tests (62 tests)

Fully containerized and CI-ready:
- Connection tests (7)
- Gantt drag logic (19)
- Helper functions (5)
- Router (8)
- Task domain (6)
- Repository (17)

### Integration Tests (10 tests)

Gantt timeline functionality (included in CI)

### UI Specifications (21 tests)

Document expected behavior (not executed in CI)

## Security Considerations

### Secrets Management

```yaml
env:
  DB_PASSWORD: ${{ secrets.DB_PASSWORD }}
  API_KEY: ${{ secrets.API_KEY }}
```

### Least Privilege

- Run containers as non-root when possible
- Use read-only volumes where appropriate
- Limit network access

### Dependency Scanning

```bash
# Can be added to CI/CD
composer audit
```

## Example CI/CD Workflows

### GitHub Actions

```yaml
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_DB: app
          POSTGRES_USER: app
          POSTGRES_PASSWORD: app
    
    steps:
      - uses: actions/checkout@v4
      - name: Run Tests
        run: ./scripts/run-tests.sh --skip-integration
```

### GitLab CI

```yaml
test:
  stage: test
  script:
    - ./scripts/run-tests.sh --skip-integration --verbose
  coverage: '/Lines:\s*\d+\.\d+\%/'
  artifacts:
    reports:
      junit: phpunit-report.xml
```

### Jenkins Pipeline

```groovy
pipeline {
    agent any
    stages {
        stage('Test') {
            steps {
                sh './scripts/run-tests.sh --skip-integration'
            }
        }
    }
    post {
        always {
            junit 'phpunit-report.xml'
        }
    }
}
```

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | All tests passed |
| 1 | Test failure or script error |
| 130 | Interrupted by user (Ctrl+C) |

## Troubleshooting

### Database Connection Issues

```bash
# Check database health
docker compose exec db pg_isready -U app -d app

# Increase timeout
DB_READY_TIMEOUT=60 ./scripts/run-tests.sh

# Check logs
docker compose logs db
```

### Port Conflicts

```bash
# Use different port
TEST_SERVER_PORT=9000 ./scripts/run-tests.sh

# Find what's using port
lsof -i :8000
```

### Cleanup Issues

```bash
# Manual cleanup
docker compose down -v
docker system prune -f

# Reset everything
make clean  # If available
```

## Continuous Deployment

### Deployment Targets

- **Staging**: Auto-deploy on `develop` branch
- **Production**: Manual approval on `main` branch

### Example Deployment

```yaml
deploy:
  stage: deploy
  only:
    - main
  when: manual
  script:
    - ./scripts/deploy.sh production
```

## Related Documentation

- [Testing Guide](TESTING.md) - Complete test documentation
- [Running Tests](RUNNING_TESTS.md) - Quick test commands
- [Technical Overview](TECHNICAL_OVERVIEW.md) - Architecture
- [API Reference](API.md) - API endpoints

---

**CI/CD Status**: Production-ready ✅  
**Automation**: GitHub Actions, GitLab CI, Make  
**Test Coverage**: 72 automated tests
