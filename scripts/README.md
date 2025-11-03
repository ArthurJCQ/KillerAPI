# Database Backup and Restore Scripts

This directory contains scripts for backing up and restoring the PostgreSQL database used by KillerAPI.

## Prerequisites

- Docker and Docker Compose installed
- The database service must be running (see `compose.prod.yaml`)
- Proper environment variables configured (in `.env` or `.env.local`)

## Backup Script

### Usage

```bash
./scripts/backup-db.sh [options]
```

### Options

- `-r, --retention DAYS` - Number of days to keep backups (default: 7)
- `-c, --compress` - Compress backups with gzip (default: true)
- `-h, --help` - Show help message

### Examples

```bash
# Create a backup with default settings (7 days retention, compressed)
./scripts/backup-db.sh

# Create a backup with 30 days retention
./scripts/backup-db.sh --retention 30

# Create an uncompressed backup
./scripts/backup-db.sh --compress false
```

### Automated Backups

To schedule automatic backups, add a cron job:

```bash
# Edit crontab
crontab -e

# Add this line for daily backups at 2 AM
0 2 * * * cd /path/to/KillerAPI && ./scripts/backup-db.sh >> /var/log/db-backup.log 2>&1

# Add this line for backups every 6 hours
0 */6 * * * cd /path/to/KillerAPI && ./scripts/backup-db.sh >> /var/log/db-backup.log 2>&1
```

### Backup Location

Backups are stored in `./backups/db/` with the naming format:
- `backup_<database-name>_<timestamp>.sql.gz` (compressed)
- `backup_<database-name>_<timestamp>.sql` (uncompressed)

Example: `backup_symfokiller_20250131_140000.sql.gz`

## Restore Script

### Usage

```bash
./scripts/restore-db.sh <backup-file>
```

### Examples

```bash
# Restore from a compressed backup
./scripts/restore-db.sh ./backups/db/backup_symfokiller_20250131_140000.sql.gz

# Restore from an uncompressed backup
./scripts/restore-db.sh ./backups/db/backup_symfokiller_20250131_140000.sql
```

### Safety Features

- **Confirmation prompt**: You must type "yes" to confirm the restore operation
- **Pre-restore backup**: A backup of the current database is created before restoration
- If the restore fails, you can use the pre-restore backup to recover

### Warning

⚠️ **IMPORTANT**: Restoring a backup will **replace all existing data** in the database. Make sure you have a recent backup before performing a restore operation.

## Environment Variables

The scripts use the following environment variables from your `.env` file:

- `POSTGRES_USER` - Database username (default: app)
- `POSTGRES_PASSWORD` - Database password
- `POSTGRES_DB` - Database name (default: app)

## Troubleshooting

### Database service not running

If you get an error about the database service not running:

```bash
# Start the database service
docker compose -f compose.prod.yaml up -d database
```

### Permission denied

If you get a permission denied error:

```bash
# Make the scripts executable
chmod +x scripts/backup-db.sh scripts/restore-db.sh
```

### Out of disk space

If backups are consuming too much disk space:

1. Reduce the retention period:
   ```bash
   ./scripts/backup-db.sh --retention 3
   ```

2. Manually clean up old backups:
   ```bash
   rm backups/db/backup_*_older_than_needed.sql.gz
   ```

3. Check disk usage:
   ```bash
   du -sh backups/db/
   ls -lh backups/db/
   ```

## Production Considerations

### Backup Strategy

For production environments, consider the following backup strategy:

1. **Frequency**:
   - Daily full backups during off-peak hours
   - Optional: Hourly incremental backups if needed

2. **Retention**:
   - Keep daily backups for 7-30 days
   - Keep weekly backups for 3-6 months
   - Keep monthly backups for 1-2 years

3. **Off-site Storage**:
   - Copy backups to remote storage (S3, Azure Blob, etc.)
   - Use encryption for sensitive data
   - Test restores regularly

### Example Production Cron Jobs

```bash
# Daily backup at 2 AM with 30 days retention
0 2 * * * cd /path/to/KillerAPI && ./scripts/backup-db.sh --retention 10 >> /var/log/db-backup.log 2>&1

# Weekly backup on Sunday at 3 AM (copy to long-term storage)
0 3 * * 0 cd /path/to/KillerAPI && ./scripts/backup-db.sh && cp backups/db/backup_*.sql.gz /mnt/long-term-storage/weekly/ >> /var/log/db-backup.log 2>&1

# Monthly backup on the 1st at 4 AM (copy to archive storage)
0 4 1 * * cd /path/to/KillerAPI && ./scripts/backup-db.sh && cp backups/db/backup_*.sql.gz /mnt/archive-storage/monthly/ >> /var/log/db-backup.log 2>&1
```

### Monitoring

Monitor backup logs regularly:

```bash
# View recent backup logs
tail -f /var/log/db-backup.log

# Check for failed backups
grep -i error /var/log/db-backup.log
```

## Docker Volume Persistence

The production compose file (`compose.prod.yaml`) uses named volumes for database persistence:

- **Volume**: `database_data` → `/var/lib/postgresql/data`
- **Backup mount**: `./backups/db` → `/backups` (inside container)

This ensures:
1. Data persists even if the container is stopped or removed
2. Backups are accessible from both the host and container
3. No data loss when updating the database container

### Volume Management

```bash
# List all volumes
docker volume ls

# Inspect the database volume
docker volume inspect killerapi_database_data

# Backup the entire volume (alternative method)
docker run --rm -v killerapi_database_data:/data -v $(pwd)/backups:/backup alpine tar czf /backup/volume-backup.tar.gz /data
```
