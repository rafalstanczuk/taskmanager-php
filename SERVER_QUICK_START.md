# Server Quick Start

One-page guide to starting and managing the development server.

## Start Server (One Command)

```bash
./scripts/start-server.sh
```

Then open: **http://localhost:8000/todos**

## Common Commands

| Command | Description |
|---------|-------------|
| `./scripts/start-server.sh` | Start server (checks for duplicates) |
| `./scripts/start-server.sh --status` | Check if server is running |
| `./scripts/start-server.sh --stop` | Stop the server |
| `./scripts/start-server.sh --restart` | Restart server |
| `./scripts/start-server.sh --port 8080` | Start on custom port |

## What the Script Does

When you run `./scripts/start-server.sh`:

1. ✓ Checks if server is already running
2. ✓ Verifies port availability
3. ✓ Starts PostgreSQL database
4. ✓ Waits for database to be ready
5. ✓ Starts PHP development server
6. ✓ Shows access URL and management commands

## Features

### Duplicate Prevention
The script detects if a server is already running and prevents starting duplicate instances.

```bash
# If already running, you'll see:
⚠ Server is already running!
  Port: 8000
  URL: http://localhost:8000/todos
```

### Port Conflict Detection
Automatically checks if the requested port is available.

```bash
# If port is in use:
✗ Port 8000 is already in use
ℹ Try: ./scripts/start-server.sh --port 8080
```

### Database Management
Automatically starts PostgreSQL and waits for it to be ready (30-second timeout with health checks).

## Output Examples

### Successful Start

```
╔════════════════════════════════════════════╗
║     PHP Development Server Manager        ║
╚════════════════════════════════════════════╝

▶ Starting Database
✓ Database is ready

▶ Starting PHP Development Server
✓ Server started successfully!

ℹ Access: http://localhost:8000/todos
ℹ Logs: docker logs -f phpproject_server
```

### Already Running

```
⚠ Server is already running!

  Container: phpproject_server
  Port: 8000
  URL: http://localhost:8000/todos

ℹ Restart: ./scripts/start-server.sh --restart
ℹ Stop: ./scripts/start-server.sh --stop
```

## Quick Fixes

### Server Won't Start

```bash
# Check status
docker ps -a

# Check database logs
docker compose logs db

# Force restart
./scripts/start-server.sh --restart
```

### Port Already in Use

```bash
# Use different port
./scripts/start-server.sh --port 8080

# Or find what's using port 8000
lsof -i :8000
```

### View Server Logs

```bash
docker logs -f phpproject_server
```

## Manual Server Start (Alternative)

If you prefer manual control:

```bash
# 1. Start database
docker compose up -d db

# 2. Wait for database
sleep 5

# 3. Start PHP server
docker compose run --rm -p 8000:8000 php php -S 0.0.0.0:8000 -t public
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `PORT` | 8000 | Server port |
| `DB_READY_TIMEOUT` | 30 | Database ready timeout (seconds) |

Example:
```bash
PORT=8080 ./scripts/start-server.sh
```

## Server Management Workflow

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
# Start server
./scripts/start-server.sh

# Run tests (separate terminal)
./scripts/run-tests.sh

# Stop when done
./scripts/start-server.sh --stop
```

## Tips

- **Always check status** before starting: `./scripts/start-server.sh --status`
- **Use restart** when you need a clean state: `./scripts/start-server.sh --restart`
- **Check logs** if something goes wrong: `docker logs phpproject_server`
- **Different port** for multiple environments: `--port 8080`

## Related Documentation

- [Server Management Guide](docs/SERVER_MANAGEMENT.md) - Complete documentation
- [README.md](README.md) - Project setup
- [Testing Guide](docs/RUNNING_TESTS.md) - Running tests

---

**That's it!** Start with `./scripts/start-server.sh` and you're ready to develop. 🚀
