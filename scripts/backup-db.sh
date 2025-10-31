#!/bin/bash

#############################################################################
# PostgreSQL Database Backup Script
#############################################################################
# This script creates backups of the PostgreSQL database running in Docker
# and manages backup retention.
#
# Usage:
#   ./scripts/backup-db.sh [options]
#
# Options:
#   -r, --retention DAYS   Number of days to keep backups (default: 7)
#   -c, --compress         Compress backups with gzip (default: true)
#   -h, --help            Show this help message
#
# Environment Variables (can be set in .env file):
#   POSTGRES_USER         Database user (default: app)
#   POSTGRES_PASSWORD     Database password
#   POSTGRES_DB           Database name (default: app)
#   BACKUP_DIR            Backup directory (default: ./backups/db)
#
# Cron Example (daily at 2 AM):
#   0 2 * * * /path/to/KillerAPI/scripts/backup-db.sh >> /var/log/db-backup.log 2>&1
#############################################################################

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default configuration
RETENTION_DAYS=7
COMPRESS=true
BACKUP_DIR="./backups/db"
COMPOSE_FILE="compose.prod.yaml"
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

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -r|--retention)
            RETENTION_DAYS="$2"
            shift 2
            ;;
        -c|--compress)
            COMPRESS="$2"
            shift 2
            ;;
        -h|--help)
            grep '^#' "$0" | tail -n +2 | head -n -1 | cut -c 3-
            exit 0
            ;;
        *)
            echo -e "${RED}Error: Unknown option $1${NC}"
            exit 1
            ;;
    esac
done

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

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Generate backup filename with timestamp
TIMESTAMP=$(date +'%Y%m%d_%H%M%S')
BACKUP_FILE="$BACKUP_DIR/backup_${POSTGRES_DB}_${TIMESTAMP}.sql"

# Create the backup
log "Starting database backup..."
log "Database: $POSTGRES_DB"
log "Backup file: $BACKUP_FILE"

if $DOCKER_COMPOSE -f "$COMPOSE_FILE" exec -T "$SERVICE_NAME" pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists > "$BACKUP_FILE"; then
    log "Database backup completed successfully"

    # Get backup file size
    BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    log "Backup size: $BACKUP_SIZE"

    # Compress the backup if enabled
    if [ "$COMPRESS" = true ]; then
        log "Compressing backup..."
        gzip -f "$BACKUP_FILE"
        BACKUP_FILE="${BACKUP_FILE}.gz"
        COMPRESSED_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        log "Compressed size: $COMPRESSED_SIZE"
    fi

    # Verify backup file exists and is not empty
    if [ ! -s "$BACKUP_FILE" ]; then
        error "Backup file is empty or does not exist"
        exit 1
    fi

    log "Backup saved to: $BACKUP_FILE"
else
    error "Database backup failed"
    exit 1
fi

# Clean up old backups
log "Cleaning up old backups (retention: $RETENTION_DAYS days)..."
if [ "$COMPRESS" = true ]; then
    PATTERN="backup_${POSTGRES_DB}_*.sql.gz"
else
    PATTERN="backup_${POSTGRES_DB}_*.sql"
fi

# Find and delete backups older than retention period
DELETED_COUNT=0
while IFS= read -r old_backup; do
    if [ -f "$old_backup" ]; then
        rm -f "$old_backup"
        log "Deleted old backup: $(basename "$old_backup")"
        ((DELETED_COUNT++))
    fi
done < <(find "$BACKUP_DIR" -name "$PATTERN" -type f -mtime +"$RETENTION_DAYS")

if [ $DELETED_COUNT -eq 0 ]; then
    log "No old backups to delete"
else
    log "Deleted $DELETED_COUNT old backup(s)"
fi

# Display backup summary
log "Backup summary:"
log "  Total backups: $(find "$BACKUP_DIR" -name "$PATTERN" -type f | wc -l)"
log "  Total size: $(du -sh "$BACKUP_DIR" | cut -f1)"

log "Backup process completed successfully!"
