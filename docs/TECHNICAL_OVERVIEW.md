# Technical Overview

Architecture and technology stack of the Task Manager application.

## Philosophy

This project demonstrates modern PHP development without relying on frameworks, showcasing PHP 8.2's native features and clean architecture principles.

## Technology Stack

### Backend
- **PHP 8.2** - Latest stable version with modern features
- **PDO** - Native database abstraction with prepared statements
- **PostgreSQL 16** - Modern relational database
- **No Framework** - Demonstrates vanilla PHP capabilities

### Testing
- **PHPUnit 10.5** - Industry-standard testing framework
- **Docker Compose** - Isolated test environments

### Development
- **Docker** - Containerization for consistency
- **Composer** - Dependency management
- **Git** - Version control

## Architecture

### Layered Architecture

```
┌─────────────────────────────────────┐
│         HTTP Layer (Public)         │
│         index.php, Router           │
└─────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────┐
│      Controllers Layer (src/)       │
│   TodoController, GanttController   │
└─────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────┐
│     Repository Layer (src/)         │
│       TodoRepository.php            │
└─────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────┐
│      Domain Layer (src/)            │
│           Task.php                  │
└─────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────┐
│      Database (PostgreSQL)          │
│           todos table               │
└─────────────────────────────────────┘
```

### Design Patterns

**Repository Pattern**
- Abstracts data access logic
- Clean separation between business logic and data persistence
- Easy to test and mock

**Domain Model**
- Rich domain objects (`Task`) with behavior
- Immutable data structures using `readonly` properties
- Type safety with PHP 8.2 features

**Front Controller**
- Single entry point (`public/index.php`)
- Centralized routing and request handling
- Clean URL structure

**Dependency Injection**
- Constructor injection in controllers
- Testable components
- Loose coupling

## Project Structure

```
/
├── config/
│   ├── bootstrap.php        # Environment loading, helpers
│   └── .env.example          # Environment template
│
├── public/
│   └── index.php             # Front controller, routing
│
├── src/
│   ├── Controllers/
│   │   ├── TodoController.php    # REST API & List View
│   │   └── GanttController.php   # Gantt Chart View
│   │
│   ├── Database/
│   │   └── Connection.php        # PDO singleton
│   │
│   ├── Domain/
│   │   └── Task.php              # Domain model
│   │
│   └── Repositories/
│       └── TodoRepository.php     # Data access layer
│
├── scripts/
│   ├── migrate.php           # Database migrations
│   ├── seed.php              # Seed data
│   ├── start-server.sh       # Server management
│   └── run-tests.sh          # Test runner
│
├── tests/
│   ├── Unit/                 # Unit tests
│   ├── Integration/          # Integration tests
│   ├── UI/                   # UI specifications
│   └── bootstrap.php         # Test setup
│
├── vendor/                   # Composer dependencies
├── composer.json             # Dependency definitions
└── phpunit.xml               # PHPUnit configuration
```

## Key Components

### Router (`src/Router.php`)

Custom regex-based router supporting:
- Dynamic path parameters: `/todos/{id}`
- HTTP method routing (GET, POST, PUT, DELETE)
- Pattern matching with named captures
- Lightweight and fast

```php
// REST API routes
$router->add('GET', '/todos/{id}', [$todo, 'show']);
$router->add('POST', '/todos', [$todo, 'create']);

// View routes
$router->add('GET', '/gantt', [$gantt, 'index']);  // Gantt chart
$router->add('GET', '/todos', [$todo, 'index']);   // List view
```

### Database Connection (`src/Database/Connection.php`)

Singleton PDO connection with:
- Environment-based configuration
- Prepared statement support
- Exception mode for error handling
- Associative array fetch mode

```php
$pdo = Connection::getInstance();
$stmt = $pdo->prepare('SELECT * FROM todos WHERE id = :id');
$stmt->execute(['id' => $id]);
```

### Repository (`src/Repositories/TodoRepository.php`)

Data access layer providing:
- CRUD operations
- Partial update support
- Type-safe query building
- Transaction support (when needed)

### Domain Model (`src/Domain/Task.php`)

Rich domain object with:
- Readonly properties (PHP 8.2)
- Factory method for database rows
- Serialization to API format
- Type coercion and validation

```php
final class Task {
    public function __construct(
        public ?int $id,
        public string $title,
        public bool $completed,
        // ... more properties
    ) {}
}
```

### Controller (`src/Controllers/TodoController.php`)

HTTP handlers with:
- Request validation
- Business logic coordination
- Response formatting
- Error handling

## Data Flow

### Request Flow

```
HTTP Request
    ↓
Router matches pattern
    ↓
Controller method called
    ↓
Repository accessed
    ↓
Domain object returned
    ↓
JSON response sent
```

### Example: Create Task

```
1. POST /todos with JSON body
2. Router matches POST /todos
3. TodoController::create() called
4. Validate input, extract fields
5. TodoRepository::create() called
6. SQL INSERT executed
7. Task object created from result
8. Task serialized to JSON
9. 201 Created response sent
```

## Database Schema

### todos Table

```sql
CREATE TABLE todos (
    id SERIAL PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    completed BOOLEAN NOT NULL DEFAULT FALSE,
    start_date TIMESTAMPTZ NULL,
    due_date TIMESTAMPTZ NULL,
    priority INT NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

**Indexes:**
- Primary key on `id`
- No additional indexes (small dataset)

**Constraints:**
- `title` NOT NULL
- `completed` default FALSE
- `priority` default 0

## Security

### SQL Injection Prevention

All queries use prepared statements:
```php
$stmt = $pdo->prepare('SELECT * FROM todos WHERE id = :id');
$stmt->execute(['id' => $id]);
```

### Input Validation

Controllers validate all input:
- Type checking
- String trimming
- Empty value handling
- Boolean coercion

### Error Handling

- Production mode hides internal errors
- Structured error responses
- Appropriate HTTP status codes
- Exception catching at controller level

## Performance Considerations

### Database
- Connection pooling via PDO persistent connections
- Prepared statement reuse
- Minimal query complexity

### Application
- No ORM overhead
- Direct PDO access
- Lightweight routing
- No unnecessary abstractions

### Frontend
- Minimal JavaScript bundle
- No heavy frameworks
- Vanilla DOM manipulation
- CSS for animations (hardware accelerated)

## Extensibility

### Adding New Resources

1. Create domain model in `src/Domain/`
2. Create repository in `src/Repositories/`
3. Create controller in `src/Controllers/`
4. Register routes in `public/index.php`
5. Add migration in `scripts/migrate.php`

### Adding Middleware

Wrap route handlers:
```php
$authMiddleware = function($handler) {
    return function($params) use ($handler) {
        // Check auth
        if (!isAuthenticated()) {
            return json_response(['error' => 'Unauthorized'], 401);
        }
        return $handler($params);
    };
};

$router->get('/todos', $authMiddleware(fn() => $controller->index()));
```

### Adding CORS

In `public/index.php`:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
```

## Testing Strategy

### Unit Tests
- Test individual components in isolation
- Mock dependencies
- Fast execution

### Integration Tests
- Test API endpoints end-to-end
- Real database access
- Full request/response cycle

### UI Specifications
- Document expected behavior
- Serve as requirements
- Guide implementation

## Development Workflow

### Local Development

```bash
# Start services
docker compose up -d

# Run migrations
composer migrate

# Start server
composer serve

# Run tests
./scripts/run-tests.sh
```

### Database Migrations

Idempotent migrations in `scripts/migrate.php`:
- Creates tables if not exist
- Safe to run multiple times
- No down migrations (for simplicity)

### Environment Configuration

`.env` file controls:
- Database connection
- Application settings
- Feature flags (future)

## Deployment Considerations

### Production Checklist

- [ ] Set `display_errors=0` in php.ini
- [ ] Use environment variables for secrets
- [ ] Enable OPcache for performance
- [ ] Set up proper logging
- [ ] Configure reverse proxy (nginx/Apache)
- [ ] Enable HTTPS
- [ ] Set up database backups
- [ ] Configure monitoring

### Recommended Stack

- **Web Server**: Nginx or Apache with PHP-FPM
- **Database**: PostgreSQL with replication
- **Monitoring**: Sentry, New Relic, or similar
- **Logging**: Centralized logging (ELK, Splunk)

## Maintenance

### Regular Tasks

- **Database**: VACUUM ANALYZE (PostgreSQL maintenance)
- **Logs**: Rotate and archive
- **Dependencies**: Update regularly (composer update)
- **Tests**: Run before deployments

### Monitoring

Key metrics to track:
- API response times
- Error rates
- Database query performance
- Disk usage
- Memory consumption

## Scalability

### Current Limitations

- Single server deployment
- No caching layer
- Direct database access
- Session state (if added) would be server-local

### Future Enhancements

- Add Redis for caching
- Implement queue system
- Database read replicas
- Horizontal scaling with load balancer
- Stateless authentication (JWT)

## Standards and Conventions

### Code Style

- PSR-12 coding standard
- Type declarations everywhere
- Readonly properties where possible
- Strict typing (`declare(strict_types=1)`)

### Naming Conventions

- Classes: PascalCase
- Methods: camelCase
- Database tables: lowercase with underscores
- Environment variables: UPPERCASE

### Documentation

- Inline PHPDoc comments
- Markdown documentation
- Self-documenting code
- Test-as-documentation

## Related Documentation

- [README.md](../README.md) - Project overview
- [API Reference](API.md) - API endpoints
- [Testing Guide](TESTING.md) - Test structure
- [CI/CD Guide](CI_CD.md) - Automation setup

---

**Architecture**: Clean, layered, testable  
**Philosophy**: Simple, modern, maintainable  
**Status**: Production-ready foundation
