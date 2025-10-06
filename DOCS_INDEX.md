# Documentation Index

Complete guide to the Task Manager PHP project documentation.

## Quick Start

**New to the project?**

1. [README.md](README.md) - Project overview and setup
2. [Server Quick Start](SERVER_QUICK_START.md) - Start the server in one command
3. [API Reference](docs/API.md) - Available endpoints

## Documentation Structure

### Core Documentation

| Document | Description |
|----------|-------------|
| [README.md](README.md) | Project overview, features, quick start guide |
| [API Reference](docs/API.md) | Complete REST API documentation with examples |
| [Technical Overview](docs/TECHNICAL_OVERVIEW.md) | Architecture, technology stack, design patterns |

### Feature Documentation

| Document | Description |
|----------|-------------|
| [Gantt Features](docs/GANTT_FEATURES.md) | 2-month timeline with Excel-style columns, weekend highlighting, drag-and-drop, and pixel-perfect alignment |
| [Gantt Drag & Drop](docs/GANTT_DRAG_DROP.md) | Drag-and-drop implementation details and task resize functionality |

### Testing Documentation

| Document | Description |
|----------|-------------|
| [Testing Guide](docs/TESTING.md) | Complete test suite overview (108+ tests) |
| [Running Tests](docs/RUNNING_TESTS.md) | Quick commands for running tests |

### Operations

| Document | Description |
|----------|-------------|
| [Server Management](docs/SERVER_MANAGEMENT.md) | Server control script documentation |
| [Server Quick Start](SERVER_QUICK_START.md) | One-page server reference |
| [CI/CD Guide](docs/CI_CD.md) | Continuous integration setup |

## Find Documentation by Task

### I want to...

#### Get Started
→ [README.md](README.md) - Complete setup guide

#### Run the Server
→ [Server Quick Start](SERVER_QUICK_START.md) - One command start  
→ [Server Management](docs/SERVER_MANAGEMENT.md) - Advanced features

#### Run Tests
→ [Running Tests](docs/RUNNING_TESTS.md) - Quick test commands  
→ [Testing Guide](docs/TESTING.md) - Comprehensive test documentation

#### Use the API
→ [API Reference](docs/API.md) - All endpoints with examples  
→ [README.md#api-endpoints](README.md#api-endpoints) - Quick endpoint list

#### Understand the Gantt Chart
→ [Gantt Features](docs/GANTT_FEATURES.md) - 2-month timeline, Excel-style columns, weekend highlighting, UTC date handling  
→ [Gantt Drag & Drop](docs/GANTT_DRAG_DROP.md) - Drag-and-drop, task resize, pixel-perfect positioning

#### Set Up CI/CD
→ [CI/CD Guide](docs/CI_CD.md) - GitHub Actions, GitLab CI setup  
→ [Testing Guide](docs/TESTING.md) - CI/CD test integration

#### Understand the Architecture
→ [Technical Overview](docs/TECHNICAL_OVERVIEW.md) - System design  
→ [README.md#project-structure](README.md#project-structure) - File organization

## Project Statistics

### Features
- ✅ REST API with full CRUD operations
- ✅ PostgreSQL database with migrations and UTC date handling
- ✅ Interactive Gantt chart with 2-month timeline
- ✅ Excel-style column backgrounds with weekend highlighting
- ✅ Drag-and-drop task scheduling with pixel-perfect alignment
- ✅ Task prioritization with color coding (High/Medium/Low)
- ✅ Real-time UI updates
- ✅ Responsive design

### Test Coverage
- **Unit Tests**: 62 tests
  - Connection: 7 tests
  - Gantt Drag Logic: 19 tests
  - Helper Functions: 5 tests
  - Router: 8 tests
  - Task Domain: 6 tests
  - Repository: 17 tests

- **Integration Tests**: 25+ tests
  - Todo API: 15+ tests
  - Gantt Timeline: 10 tests

- **UI Specifications**: 21 tests
  - Gantt Drag & Drop: 21 tests

**Total: 108+ tests, 200+ assertions**

### Technology Stack
- PHP 8.2
- PostgreSQL 16
- PHPUnit 10.5
- Docker & Docker Compose

## Common Tasks

### Starting the Server

```bash
# Quick start
./scripts/start-server.sh

# Check if running
./scripts/start-server.sh --status

# Stop server
./scripts/start-server.sh --stop
```

### Running Tests

```bash
# All tests with automated setup
./scripts/run-tests.sh

# Unit tests only
docker compose run --rm php vendor/bin/phpunit --testsuite Unit

# Specific test file
docker compose run --rm php vendor/bin/phpunit tests/Unit/GanttDragLogicTest.php
```

### Database Operations

```bash
# Clear database (initialize empty)
./scripts/seed-database.sh

# Append 20 sample tasks (2-month timeline for testing)
./scripts/seed-database.sh --appendtestdata

# Run migrations manually
docker compose exec php php scripts/migrate.php

# Or seed manually
docker compose exec php php scripts/seed.php

# Reset database completely
docker compose down -v
docker compose up -d db
./scripts/seed-database.sh
```

### Making API Requests

```bash
# Health check
curl http://localhost:8000/health

# List tasks
curl http://localhost:8000/todos

# Create task
curl -X POST http://localhost:8000/todos \
  -H "Content-Type: application/json" \
  -d '{"title": "New Task", "priority": 1}'
```

## Troubleshooting

### Server Issues
- **Won't start?** → See [Server Management](docs/SERVER_MANAGEMENT.md#troubleshooting)
- **Port conflict?** → Use `./scripts/start-server.sh --port 8080`

### Test Failures
- **Database errors?** → Check [Testing Guide](docs/TESTING.md#troubleshooting)
- **Connection issues?** → Verify PostgreSQL is running

### API Problems
- **404 errors?** → Check [API Reference](docs/API.md) for correct endpoints
- **Validation errors?** → Review request body format

## Documentation Standards

All documentation in this project follows these principles:

### Clarity
- Clear, concise language
- Step-by-step instructions
- Practical examples

### Completeness
- All features documented
- Edge cases covered
- Error scenarios explained

### Currency
- Reflects current codebase
- Accurate test counts
- Working code examples

## Quick Reference

### File Locations
```
docs/
├── API.md                    # API documentation
├── CI_CD.md                  # CI/CD setup
├── GANTT_DRAG_DROP.md        # Gantt implementation
├── GANTT_FEATURES.md         # Gantt user guide
├── RUNNING_TESTS.md          # Test commands
├── SERVER_MANAGEMENT.md      # Server operations
├── TECHNICAL_OVERVIEW.md     # Architecture
└── TESTING.md                # Test suite guide

Project Root:
├── README.md                 # Main documentation
├── DOCS_INDEX.md             # This file
└── SERVER_QUICK_START.md     # Quick server guide
```

### External Resources
- [PHPUnit Documentation](https://phpunit.de/)
- [PostgreSQL 16 Docs](https://www.postgresql.org/docs/16/)
- [PHP 8.2 Manual](https://www.php.net/manual/en/)
- [Docker Compose Docs](https://docs.docker.com/compose/)

## Next Steps

1. **New Users**: Read [README.md](README.md)
2. **Developers**: Check [Technical Overview](docs/TECHNICAL_OVERVIEW.md)
3. **DevOps**: Review [CI/CD Guide](docs/CI_CD.md)
4. **Testers**: See [Testing Guide](docs/TESTING.md)

---

**Documentation Health**: ✅ Complete and current

For questions or issues, refer to the specific documentation section above or check the troubleshooting guides.
