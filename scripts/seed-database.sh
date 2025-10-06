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

# Check if database is running
check_database() {
    if docker compose -f "${PROJECT_ROOT}/docker-compose.yml" ps db 2>/dev/null | grep -q "Up"; then
        return 0  # Database is running
    fi
    return 1  # Database is not running
}

# Wait for database to be ready
wait_for_database() {
    log_info "Waiting for database to be ready (timeout: ${DB_READY_TIMEOUT}s)..."
    
    local counter=0
    while [ $counter -lt $DB_READY_TIMEOUT ]; do
        if docker compose -f "${PROJECT_ROOT}/docker-compose.yml" exec -T db pg_isready -U app -d app >/dev/null 2>&1; then
            log_success "Database is ready"
            return 0
        fi
        sleep 1
        counter=$((counter + 1))
    done
    
    log_error "Database failed to become ready within ${DB_READY_TIMEOUT} seconds"
    log_info "Hint: Try './scripts/start-server.sh' to start the database"
    return 1
}

# Start database if not running
start_database() {
    log_section "Starting Database"
    
    cd "${PROJECT_ROOT}"
    
    if check_database; then
        log_info "Database already running"
    else
        log_info "Starting PostgreSQL database..."
        docker compose up -d db
    fi
    
    wait_for_database
}

# Run migrations
run_migrations() {
    log_section "Running Migrations"
    
    cd "${PROJECT_ROOT}"
    
    log_info "Executing database migrations..."
    if docker compose run --rm php php scripts/migrate.php; then
        log_success "Migrations completed successfully"
        return 0
    else
        log_error "Migrations failed"
        return 1
    fi
}

# Seed database
seed_database() {
    log_section "Seeding Database"
    
    cd "${PROJECT_ROOT}"
    
    log_info "Inserting seed data..."
    if docker compose exec php php scripts/seed.php; then
        log_success "Database seeded successfully"
        return 0
    else
        log_error "Seeding failed"
        return 1
    fi
}

# Clear existing data (optional)
clear_data() {
    log_section "Clearing Existing Data"
    
    cd "${PROJECT_ROOT}"
    
    log_warning "This will delete all tasks from the database!"
    log_info "Clearing todos table..."
    
    # Run TRUNCATE command (TRUNCATE doesn't support IF EXISTS, but will fail gracefully if table doesn't exist)
    if docker compose exec -T db psql -U app -d app -c "TRUNCATE TABLE todos RESTART IDENTITY CASCADE;" >/dev/null 2>&1; then
        log_success "Data cleared successfully"
        return 0
    else
        # Table might not exist yet - that's OK
        log_info "Table doesn't exist yet or is already empty"
        return 0
    fi
}

# Show help
show_help() {
    cat << EOF
Usage: $(basename "$0") [OPTIONS]

Database initialization and seeding utility.

OPTIONS:
    (no options)          Clear all entries (default behavior)
    --init, -i            Clear all entries (same as default)
    --clear, -c           Clear all entries (same as default)
    --appendtestdata      Append 20 sample tasks (60 days: -30 to +30)
    --migrate, -m         Run migrations before operation
    --start-db            Start database if not running (default)
    --skip-db-start       Skip database startup check
    --help, -h            Show this help message

ENVIRONMENT VARIABLES:
    DB_READY_TIMEOUT      Database ready timeout in seconds (default: 30)

EXAMPLES:
    $(basename "$0")                    # Clear all entries
    $(basename "$0") --clear            # Clear all entries (explicit)
    $(basename "$0") --init             # Clear all entries (explicit)
    $(basename "$0") --appendtestdata   # Append 20 sample tasks
    $(basename "$0") --migrate --clear  # Migrate, then clear
    $(basename "$0") --migrate --appendtestdata  # Migrate, then seed

WORKFLOW:
    Default (no options):
    1. Check/start database
    2. Clear all entries from todos table
    
    With --appendtestdata:
    1. Check/start database
    2. Run migrations (if --migrate)
    3. Append 20 sample tasks across exactly 2 months (60 days)

EOF
}

# Main execution
main() {
    local action="clear"  # Default action is clear
    local run_migrations_first=false
    local start_db=true
    
    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --init|-i)
                action="clear"
                shift
                ;;
            --clear|-c)
                action="clear"
                shift
                ;;
            --appendtestdata)
                action="append"
                shift
                ;;
            --migrate|-m)
                run_migrations_first=true
                shift
                ;;
            --start-db)
                start_db=true
                shift
                ;;
            --skip-db-start)
                start_db=false
                shift
                ;;
            --help|-h)
                show_help
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
    echo "║     Database Seeding Utility              ║"
    echo "╚════════════════════════════════════════════╝"
    
    # Start database
    if [ "$start_db" = true ]; then
        start_database || exit 1
    else
        log_info "Skipping database startup check"
    fi
    
    # Run migrations if requested
    if [ "$run_migrations_first" = true ]; then
        run_migrations || exit 1
    fi
    
    # Execute based on action
    if [ "$action" = "clear" ]; then
        # Clear all entries
        clear_data || exit 1
        
        echo ""
        log_success "Database cleared - all entries removed!"
        echo ""
        log_info "Database is ready for use"
        log_info "To add sample data: $(basename "$0") --appendtestdata"
        echo ""
        log_info "Access the application:"
        echo -e "  ${GREEN}http://localhost:8000/todos${NC}"
        echo ""
        
    elif [ "$action" = "append" ]; then
        # Append test data
        seed_database || exit 1
        
        echo ""
        log_success "Sample data appended successfully!"
        echo ""
        log_info "20 tasks added across exactly 2 months (60 days: -30 to +30 from today)"
        echo ""
        log_info "View the data:"
        echo -e "  ${GREEN}http://localhost:8000/todos${NC}"
        echo ""
        log_info "Or query directly:"
        echo -e "  ${BLUE}docker compose exec db psql -U app -d app -c 'SELECT COUNT(*) FROM todos;'${NC}"
        echo ""
    fi
}

# Run main function
main "$@"

