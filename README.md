## PHP REST API (PHP 8.2, PostgreSQL)

Simple dependency-free REST API using native PHP 8.2 features and PDO for PostgreSQL.

### Quickstart

**🚀 Easiest Way (Recommended):**

```bash
# One command to start everything
./scripts/start-server.sh
```

Then open: **http://localhost:8000/todos**

See `SERVER_QUICK_START.md` for details.

---

**📋 Manual Setup:**

1) Copy environment file (optional - uses defaults if not present)

```bash
cp .env.example .env
# Edit .env to customize database credentials if needed
```

2) Start PostgreSQL (Docker)

```bash
docker compose up -d db
```

3) Install Composer autoload (optional but recommended)

```bash
composer install
```

4) Run database migrations

```bash
composer migrate
```

5) Start the development server

```bash
composer serve
# OR use the smart script:
./scripts/start-server.sh
```

Visit `http://localhost:8000/health` or `http://localhost:8000/todos`.

### API Endpoints

- GET `/health` – service health
- GET `/todos` – list todos
- GET `/todos/{id}` – get todo
- POST `/todos` – create todo `{ "title": "Task", "completed": false }`
- PUT `/todos/{id}` – update fields `{ "title": "New", "completed": true }`
- DELETE `/todos/{id}` – delete todo

See `docs/API.md` for examples.

### Configuration

The application uses environment variables for configuration. Create a `.env` file in the project root:

```bash
cp .env.example .env
```

**⚠️ All Variables are REQUIRED (no defaults):**

| Variable | Description | Example Value |
|----------|-------------|---------------|
| `DB_HOST` | Database host | `db` |
| `DB_PORT` | Database port | `5432` |
| `DB_NAME` | Database name | `app` |
| `DB_USER` | Database user | `app` |
| `DB_PASSWORD` | Database password | `app` |
| `APP_ENV` | Application environment | `local` / `testing` / `production` |
| `PORT` | Server port | `8000` |
| `DB_READY_TIMEOUT` | Database ready timeout (seconds) | `30` |
| `TEST_SERVER_PORT` | Test server port | `8001` |

**⚠️ Important:** The application will **fail** if any environment variable is missing. This ensures explicit configuration and prevents silent fallback to defaults.

### Testing

**Quick test (CI/CD mode - recommended):**

```bash
./scripts/run-tests.sh --skip-integration
```

**Using Make:**

```bash
make test          # Run unit tests
make test-coverage # With coverage report
make ci            # Full CI/CD pipeline
```

**Manual:**

```bash
# Unit tests only (62 tests, 100% pass rate)
docker compose run --rm php vendor/bin/phpunit --testsuite Unit

# Gantt drag logic tests (19 tests)
docker compose run --rm php vendor/bin/phpunit tests/Unit/GanttDragLogicTest.php --testdox

# Gantt timeline integration tests (10 tests)
docker compose run --rm php vendor/bin/phpunit tests/Integration/GanttTimelineTest.php --testdox
```

**Test script features:**
- ✅ Automatic setup and cleanup
- ✅ Database health checks
- ✅ Dependency management
- ✅ Colored output
- ✅ CI/CD ready

See `docs/TESTING.md` and `docs/CI_CD.md` for detailed documentation.

### Server Management

**Smart server script with duplicate prevention:**

```bash
./scripts/start-server.sh          # Start server (checks if already running)
./scripts/start-server.sh --status # Check status
./scripts/start-server.sh --stop   # Stop server
./scripts/start-server.sh --restart # Force restart
```

See `docs/SERVER_MANAGEMENT.md` for complete guide.

### Documentation

**📚 Complete Documentation Index**: See [DOCS_INDEX.md](DOCS_INDEX.md) for all docs

**Quick Links**:
- 🧪 [Testing Guide](docs/TESTING.md) - 108+ tests, all suites
- 🚀 [CI/CD Guide](docs/CI_CD.md) - GitHub Actions, GitLab CI
- 📊 [API Reference](docs/API.md) - All endpoints with examples
- 🎯 [Gantt Features](docs/GANTT_FEATURES.md) - Interactive timeline
- 🏗️ [Technical Overview](docs/TECHNICAL_OVERVIEW.md) - Architecture

