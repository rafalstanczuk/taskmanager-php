# Server Management

Complete guide to the server management script for local development.

## Overview

The `start-server.sh` script provides intelligent server management with:
- Duplicate server prevention
- Port conflict detection  
- Automatic database startup
- Health checks and monitoring
- Easy start/stop/restart operations

## Quick Commands

```bash
# Start server
./scripts/start-server.sh

# Check status
./scripts/start-server.sh --status

# Stop server
./scripts/start-server.sh --stop

# Restart server
./scripts/start-server.sh --restart

# Use different port
./scripts/start-server.sh --port 8080

# Show help
./scripts/start-server.sh --help
```

## Command Options

| Option | Description |
|--------|-------------|
| `--port, -p PORT` | Specify port (default: 8000) |
| `--restart, -r` | Force restart if already running |
| `--status, -s` | Show server status and exit |
| `--stop` | Stop the running server |
| `--help, -h` | Display help message |

## Features

### 1. Duplicate Prevention

The script detects if a server is already running and prevents starting duplicate instances.

**Example output when already running:**
```
⚠ Server is already running!

  Container: phpproject_server
  Port: 8000
  URL: http://localhost:8000/todos

ℹ To restart: ./scripts/start-server.sh --restart
ℹ To stop: ./scripts/start-server.sh --stop
```

### 2. Port Conflict Detection

Automatically checks if the requested port is available before starting.

**Example output for port conflict:**
```
✗ Port 8000 is already in use by another process

ℹ Try a different port: ./scripts/start-server.sh --port 8080
ℹ Or check what's using the port: lsof -i :8000
```

### 3. Database Management

- Automatically starts PostgreSQL if not running
- Waits for database to be ready (30-second timeout)
- Performs health checks before starting server

**Database startup sequence:**
```
▶ Starting Database
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ℹ Starting PostgreSQL database...
✓ Database is ready
```

### 4. Smart Status Checking

Shows comprehensive server information.

**Status output:**
```
▶ Server Status
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Server is RUNNING

  Container: phpproject_server
  Port: 8000
  URL: http://localhost:8000/todos

ℹ View logs: docker logs -f phpproject_server
ℹ Stop server: docker stop phpproject_server
```

## Usage Scenarios

### Scenario 1: First Time Startup

```bash
$ ./scripts/start-server.sh

╔════════════════════════════════════════════╗
║     PHP Development Server Manager        ║
╚════════════════════════════════════════════╝

▶ Starting Database
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ℹ Starting PostgreSQL database...
✓ Database is ready

▶ Starting PHP Development Server
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Server started successfully!

ℹ Access the application:
  http://localhost:8000/todos

ℹ View server logs:
  docker logs -f phpproject_server

ℹ Stop the server:
  docker stop phpproject_server
```

### Scenario 2: Server Already Running

Attempting to start when already running:

```bash
$ ./scripts/start-server.sh

⚠ Server is already running!

  Container: phpproject_server
  Port: 8000
  URL: http://localhost:8000/todos

ℹ To restart the server, use: ./scripts/start-server.sh --restart
ℹ To stop the server, use: ./scripts/start-server.sh --stop
```

### Scenario 3: Force Restart

```bash
$ ./scripts/start-server.sh --restart

⚠ Server is already running on port 8000
ℹ Stopping existing server...
✓ Existing server stopped

▶ Starting PHP Development Server
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Server started successfully!
```

### Scenario 4: Check Status

```bash
$ ./scripts/start-server.sh --status

▶ Server Status
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ Server is RUNNING

  Container: phpproject_server
  Port: 8000
  URL: http://localhost:8000/todos
```

### Scenario 5: Stop Server

```bash
$ ./scripts/start-server.sh --stop

ℹ Stopping existing server...
✓ Server stopped
```

### Scenario 6: Use Different Port

```bash
$ ./scripts/start-server.sh --port 8080

▶ Starting PHP Development Server
✓ Server started successfully!

ℹ Access the application:
  http://localhost:8080/todos
```

## Technical Details

### Container Management

**Container Name:** `phpproject_server`

The script uses a consistent container name to:
- Track running instances
- Prevent duplicates
- Enable easy management

### Health Checks

**Database Health Check:**
```bash
docker compose exec -T db pg_isready -U postgres
```

Waits up to 30 seconds for PostgreSQL to accept connections.

**Port Availability Check:**
```bash
lsof -Pi :PORT -sTCP:LISTEN
```

Verifies the port is not in use before starting.

### Server Lifecycle

**1. Start Sequence:**
1. Check if server already running
2. Verify port availability
3. Start database if needed
4. Wait for database ready (30s timeout)
5. Start PHP server container
6. Verify server responding

**2. Status Check:**
1. Check container existence
2. Get port mapping
3. Display connection information

**3. Stop Sequence:**
1. Stop container gracefully
2. Remove container
3. Clean up resources

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `PORT` | 8000 | Default server port |
| `DB_READY_TIMEOUT` | 30 | Database ready timeout (seconds) |

**Usage:**
```bash
PORT=8080 ./scripts/start-server.sh
DB_READY_TIMEOUT=60 ./scripts/start-server.sh
```

## Troubleshooting

### Server Won't Start

**Check Docker status:**
```bash
docker ps -a
```

**Check database logs:**
```bash
docker compose logs db
```

**Force clean restart:**
```bash
./scripts/start-server.sh --restart
```

### Port Already in Use

**Find what's using the port:**
```bash
lsof -i :8000
```

**Kill the process:**
```bash
kill -9 <PID>
```

**Or use a different port:**
```bash
./scripts/start-server.sh --port 8080
```

### Database Won't Start

**Restart Docker:**
```bash
sudo systemctl restart docker
```

**Reset database:**
```bash
docker compose down -v
docker compose up -d db
```

### Server Not Responding

**Check server logs:**
```bash
docker logs phpproject_server
```

**Restart server:**
```bash
./scripts/start-server.sh --restart
```

**Verify container is running:**
```bash
docker ps | grep phpproject_server
```

### Permission Denied

**Make script executable:**
```bash
chmod +x scripts/start-server.sh
```

## Development Workflows

### Daily Development

```bash
# Morning: Start server
./scripts/start-server.sh

# Work on code...

# Evening: Stop server
./scripts/start-server.sh --stop
```

### Testing Workflow

```bash
# Start server for manual testing
./scripts/start-server.sh

# Run automated tests (separate terminal)
./scripts/run-tests.sh

# Stop when done
./scripts/start-server.sh --stop
```

### CI/CD Pipeline

```bash
# Use --restart to ensure clean state
./scripts/start-server.sh --restart

# Run tests
./scripts/run-tests.sh --skip-integration

# Cleanup
./scripts/start-server.sh --stop
```

## Advanced Usage

### Custom Port via Environment

```bash
export PORT=8080
./scripts/start-server.sh
```

### Extended Database Timeout

```bash
export DB_READY_TIMEOUT=60
./scripts/start-server.sh
```

### Background Execution

```bash
# Start and detach (not recommended for development)
./scripts/start-server.sh > /dev/null 2>&1 &

# Check status later
./scripts/start-server.sh --status
```

### Multiple Environments

```bash
# Development (port 8000)
./scripts/start-server.sh

# Staging (port 8001)
PORT=8001 ./scripts/start-server.sh
```

Note: Running multiple servers requires different container names (not currently supported).

## Output Colors

The script uses colored output for better readability:

- 🔵 **Blue** - Info messages
- 🟢 **Green** - Success messages
- 🟡 **Yellow** - Warning messages
- 🔴 **Red** - Error messages

## Best Practices

1. **Always use the script** instead of manual Docker commands
2. **Check status** before starting: `./scripts/start-server.sh --status`
3. **Use --restart** when you need a clean state
4. **Stop server** when not in use to free resources
5. **Check logs** if something goes wrong: `docker logs phpproject_server`

## Comparison: Manual vs Script

### Manual Method

```bash
# Multiple commands required
docker compose up -d db
# Wait... (manual check)
docker compose run --rm -p 8000:8000 php php -S 0.0.0.0:8000 -t public

# Issues:
# - No duplicate checking
# - No port conflict detection
# - Manual database wait
# - Hard to track container
```

### Automated Script

```bash
# One command does everything
./scripts/start-server.sh

# Benefits:
# ✓ Duplicate prevention
# ✓ Port conflict detection
# ✓ Automatic database start
# ✓ Health checks
# ✓ Status tracking
# ✓ Easy management
```

## Related Documentation

- [Server Quick Start](../SERVER_QUICK_START.md) - One-page reference
- [README.md](../README.md) - Project setup
- [Testing Guide](TESTING.md) - Running tests
- [CI/CD Guide](CI_CD.md) - Automation setup

---

**Script**: Professional server management  
**Features**: Smart, safe, reliable  
**Status**: Production-ready ✅
