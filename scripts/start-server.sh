#!/usr/bin/env bash

# Exit on error, undefined variables, and pipe failures
set -euo pipefail

# Color codes for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly NC='\033[0m' # No Color

# Configuration
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
readonly CONTAINER_NAME="taskmanager_php_server"
readonly DEFAULT_PORT="${PORT:-8000}"
readonly DB_READY_TIMEOUT="${DB_READY_TIMEOUT:-30}"

# Logging functions
log_info() {
    echo -e "${BLUE}ℹ${NC} $*"
}

log_success() {
    echo -e "${GREEN}✓${NC} $*"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $*"
}

log_error() {
    echo -e "${RED}✗${NC} $*" >&2
}

log_section() {
    echo ""
    echo -e "${BLUE}▶${NC} $*"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
}

# Check if server is already running
check_existing_server() {
    # Check by container name
    if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        return 0  # Server is running
    fi
    return 1  # Server is not running
}

# Check if port is in use
check_port() {
    local port=$1
    if lsof -Pi :${port} -sTCP:LISTEN -t >/dev/null 2>&1 || \
       ss -tlnp 2>/dev/null | grep -q ":${port} " || \
       netstat -tlnp 2>/dev/null | grep -q ":${port} "; then
        return 0  # Port is in use
    fi
    return 1  # Port is free
}

# Wait for database to be ready
wait_for_database() {
    log_info "Waiting for database to be ready (timeout: ${DB_READY_TIMEOUT}s)..."
    
    local counter=0
    while [ $counter -lt $DB_READY_TIMEOUT ]; do
        if docker compose -f "${PROJECT_ROOT}/docker-compose.yml" exec -T db pg_isready -U postgres >/dev/null 2>&1; then
            log_success "Database is ready"
            return 0
        fi
        sleep 1
        counter=$((counter + 1))
    done
    
    log_error "Database failed to become ready within ${DB_READY_TIMEOUT} seconds"
    log_info "Hint: Try 'docker compose down' to reset the database"
    return 1
}

# Start database
start_database() {
    log_section "Starting Database"
    
    cd "${PROJECT_ROOT}"
    
    # Check if database container exists (running or stopped)
    if docker compose ps -a db 2>/dev/null | grep -q "db"; then
        # Container exists - check if it's running
        if docker compose ps db 2>/dev/null | grep -q "Up"; then
            log_info "Database already running"
        else
            # Container exists but is stopped - try to start it
            log_info "Database container exists but is stopped, starting..."
            if docker compose start db 2>/dev/null; then
                log_success "Database container started"
            else
                # If start fails, remove and recreate
                log_warning "Failed to start existing container, recreating..."
                docker compose rm -f db >/dev/null 2>&1 || true
                log_info "Starting PostgreSQL database..."
                docker compose up -d db
            fi
        fi
    else
        # Container doesn't exist - create it
        log_info "Starting PostgreSQL database..."
        docker compose up -d db
    fi
    
    wait_for_database
}

# Stop existing server
stop_existing_server() {
    log_info "Stopping existing server..."
    docker stop "${CONTAINER_NAME}" >/dev/null 2>&1 || true
    docker rm "${CONTAINER_NAME}" >/dev/null 2>&1 || true
    log_success "Existing server stopped"
}

# Cleanup all containers
cleanup_all() {
    log_section "Cleanup"
    
    log_info "Stopping all containers..."
    docker compose -f "${PROJECT_ROOT}/docker-compose.yml" down 2>/dev/null || true
    
    log_info "Removing server container..."
    docker stop "${CONTAINER_NAME}" >/dev/null 2>&1 || true
    docker rm "${CONTAINER_NAME}" >/dev/null 2>&1 || true
    
    log_success "Cleanup complete"
    log_info "You can now run './scripts/start-server.sh' to start fresh"
}

# Start the PHP server
start_server() {
    local port=$1
    
    log_section "Starting PHP Development Server"
    
    cd "${PROJECT_ROOT}"
    
    log_info "Starting server on port ${port}..."
    docker compose run --rm -d \
        -p "${port}:8000" \
        --name "${CONTAINER_NAME}" \
        php php -S 0.0.0.0:8000 -t public
    
    # Wait for server to be ready
    sleep 2
    
    # Check if server is responding
    if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        log_success "Server started successfully!"
        echo ""
        log_info "Access the application:"
        echo -e "  ${GREEN}http://localhost:${port}/todos${NC}"
        echo ""
        log_info "View server logs:"
        echo -e "  ${BLUE}docker logs -f ${CONTAINER_NAME}${NC}"
        echo ""
        log_info "Stop the server:"
        echo -e "  ${BLUE}docker stop ${CONTAINER_NAME}${NC}"
        echo ""
        return 0
    else
        log_error "Failed to start server"
        return 1
    fi
}

# Show server status
show_status() {
    log_section "Server Status"
    
    if check_existing_server; then
        local port=$(docker port "${CONTAINER_NAME}" 8000 2>/dev/null | cut -d: -f2)
        echo -e "${GREEN}✓${NC} Server is ${GREEN}RUNNING${NC}"
        echo ""
        echo "  Container: ${CONTAINER_NAME}"
        echo "  Port: ${port:-unknown}"
        echo "  URL: http://localhost:${port}/todos"
        echo ""
        log_info "View logs: docker logs -f ${CONTAINER_NAME}"
        log_info "Stop server: docker stop ${CONTAINER_NAME}"
    else
        echo -e "${YELLOW}⚠${NC} Server is ${YELLOW}NOT RUNNING${NC}"
        echo ""
        log_info "Start server: ./scripts/start-server.sh"
    fi
}

# Main execution
main() {
    local port="${DEFAULT_PORT}"
    local force_restart=false
    local show_status_only=false
    
    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --port|-p)
                port="$2"
                shift 2
                ;;
            --restart|-r)
                force_restart=true
                shift
                ;;
            --status|-s)
                show_status_only=true
                shift
                ;;
            --stop)
                if check_existing_server; then
                    stop_existing_server
                    log_success "Server stopped"
                else
                    log_info "No server is running"
                fi
                exit 0
                ;;
            --cleanup)
                cleanup_all
                exit 0
                ;;
            --help|-h)
                cat << EOF
Usage: $(basename "$0") [OPTIONS]

Start the PHP development server with automatic checks.

OPTIONS:
    --port, -p PORT       Port to run server on (default: 8000)
    --restart, -r         Force restart if already running
    --status, -s          Show server status and exit
    --stop                Stop the running server
    --cleanup             Stop and remove all containers (fixes conflicts)
    --help, -h            Show this help message

ENVIRONMENT VARIABLES:
    PORT                  Default port (default: 8000)
    DB_READY_TIMEOUT      Database ready timeout in seconds (default: 30)

EXAMPLES:
    $(basename "$0")                    # Start server on default port 8000
    $(basename "$0") --port 8080        # Start server on port 8080
    $(basename "$0") --restart          # Force restart server
    $(basename "$0") --status           # Check server status
    $(basename "$0") --stop             # Stop the server
    $(basename "$0") --cleanup          # Clean up all containers and start fresh

TROUBLESHOOTING:
    If you get container conflicts, run: $(basename "$0") --cleanup

EOF
                exit 0
                ;;
            *)
                log_error "Unknown option: $1"
                echo "Use --help for usage information"
                exit 1
                ;;
        esac
    done
    
    # Header
    echo ""
    echo "╔════════════════════════════════════════════╗"
    echo "║     PHP Development Server Manager        ║"
    echo "╚════════════════════════════════════════════╝"
    echo ""
    
    # Show status only
    if [ "$show_status_only" = true ]; then
        show_status
        exit 0
    fi
    
    # Check if server is already running
    if check_existing_server; then
        if [ "$force_restart" = true ]; then
            log_warning "Server is already running on port $(docker port ${CONTAINER_NAME} 8000 2>/dev/null | cut -d: -f2 || echo 'unknown')"
            stop_existing_server
        else
            local existing_port=$(docker port "${CONTAINER_NAME}" 8000 2>/dev/null | cut -d: -f2 || echo "unknown")
            log_warning "Server is already running!"
            echo ""
            echo "  Container: ${CONTAINER_NAME}"
            echo "  Port: ${existing_port}"
            echo "  URL: http://localhost:${existing_port}/todos"
            echo ""
            log_info "To restart the server, use: $0 --restart"
            log_info "To stop the server, use: $0 --stop"
            log_info "To check status, use: $0 --status"
            exit 0
        fi
    fi
    
    # Check if port is in use
    if check_port "$port"; then
        log_error "Port ${port} is already in use by another process"
        log_info "Try a different port: $0 --port 8080"
        log_info "Or check what's using the port: lsof -i :${port}"
        exit 1
    fi
    
    # Start database
    start_database || exit 1
    
    # Start server
    start_server "$port" || exit 1
}

# Run main function
main "$@"

