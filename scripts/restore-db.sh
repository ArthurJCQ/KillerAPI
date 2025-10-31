#!/bin/bash

#############################################################################
# PostgreSQL Database Restore Script
#############################################################################
# This script restores a PostgreSQL database backup from a file.
#
# Usage:
#   ./scripts/restore-db.sh <backup-file>
#
# Arguments:
#   backup-file   Path to the backup file (.sql or .sql.gz)
#
# Options:
#   -h, --help    Show this help message
#
# Environment Variables (can be set in .env file):
#   POSTGRES_USER         Database user (default: app)
#   POSTGRES_PASSWORD     Database password
#   POSTGRES_DB           Database name (default: app)
#
# Example:
#   ./scripts/restore-db.sh ./backups/db/backup_symfokiller_20231025_140000.sql.gz
#############################################################################

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default configuration
COMPOSE_FILE="prod.compose.yaml"
SERVICE_NAME="database"

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Load environment variables from .env file if it exists
if [ -f "$PROJECT_DIR/.env" ]; then
    export $(grep -v '^#' "$PROJECT_DIR/.env" | grep -v '^$' | xargs)
fi

# Override with .env.local if it exists
if [ -f "$PROJECT_DIR/.env.local" ]; then
    export $(grep -v '^#' "$PROJECT_DIR/.env.local" | grep -v '^$' | xargs)
fi

# Set defaults if not provided
POSTGRES_USER=${POSTGRES_USER:-app}
POSTGRES_DB=${POSTGRES_DB:-app}
POSTGRES_PASSWORD=${POSTGRES_PASSWORD:-}

# Function to log messages
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" >&2
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1"
}

# Show help if requested
if [[ "${1:-}" == "-h" ]] || [[ "${1:-}" == "--help" ]]; then
    grep '^#' "$0" | tail -n +2 | head -n -1 | cut -c 3-
    exit 0
fi

# Check if backup file is provided
if [ $# -eq 0 ]; then
    error "No backup file provided"
    echo "Usage: $0 <backup-file>"
    exit 1
fi

BACKUP_FILE="$1"

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    error "Backup file does not exist: $BACKUP_FILE"
    exit 1
fi

# Check if Docker Compose is available
if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    error "Docker Compose is not installed or not in PATH"
    exit 1
fi

# Determine docker compose command
if docker compose version &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
else
    DOCKER_COMPOSE="docker-compose"
fi

# Navigate to project directory
cd "$PROJECT_DIR"

# Check if the database service is running
log "Checking if database service is running..."
if ! $DOCKER_COMPOSE -f "$COMPOSE_FILE" ps "$SERVICE_NAME" | grep -q "Up"; then
    error "Database service is not running. Please start it with: $DOCKER_COMPOSE -f $COMPOSE_FILE up -d"
    exit 1
fi

# Confirm restore operation
warn "This will restore the database '$POSTGRES_DB' from the backup file."
warn "ALL EXISTING DATA WILL BE REPLACED!"
echo -n "Are you sure you want to continue? (yes/no): "
read -r CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    log "Restore operation cancelled"
    exit 0
fi

# Create a pre-restore backup
PRE_RESTORE_BACKUP="/tmp/pre_restore_backup_$(date +'%Y%m%d_%H%M%S').sql"
log "Creating pre-restore backup at: $PRE_RESTORE_BACKUP"
if $DOCKER_COMPOSE -f "$COMPOSE_FILE" exec -T "$SERVICE_NAME" pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" > "$PRE_RESTORE_BACKUP"; then
    log "Pre-restore backup created successfully"
else
    warn "Failed to create pre-restore backup, but continuing..."
fi

# Restore the database
log "Starting database restore..."
log "Backup file: $BACKUP_FILE"
log "Database: $POSTGRES_DB"

# Check if backup is compressed
if [[ "$BACKUP_FILE" == *.gz ]]; then
    log "Decompressing and restoring backup..."
    if gunzip -c "$BACKUP_FILE" | $DOCKER_COMPOSE -f "$COMPOSE_FILE" exec -T "$SERVICE_NAME" psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"; then
        log "Database restore completed successfully"
    else
        error "Database restore failed"
        warn "You can restore from the pre-restore backup at: $PRE_RESTORE_BACKUP"
        exit 1
    fi
else
    log "Restoring backup..."
    if cat "$BACKUP_FILE" | $DOCKER_COMPOSE -f "$COMPOSE_FILE" exec -T "$SERVICE_NAME" psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"; then
        log "Database restore completed successfully"
    else
        error "Database restore failed"
        warn "You can restore from the pre-restore backup at: $PRE_RESTORE_BACKUP"
        exit 1
    fi
fi

# Clean up pre-restore backup if restore was successful
log "Cleaning up pre-restore backup..."
rm -f "$PRE_RESTORE_BACKUP"

log "Restore process completed successfully!"
log "Database '$POSTGRES_DB' has been restored from: $BACKUP_FILE"
