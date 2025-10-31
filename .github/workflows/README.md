# GitHub Actions Workflows

This directory contains GitHub Actions workflows for the KillerAPI project.

## Available Workflows

### 1. Deploy (`deploy.yml`)

Automatically deploys the application to production when a new version tag is pushed.

**Trigger:** Push of version tags (e.g., `v1.0.0`, `v2.1.0`)

**Required Secrets:**
- `SSH_PRIVATE_KEY` - SSH private key for server access
- `SSH_HOST` - Server hostname or IP
- `SSH_USER` - SSH username
- `SSH_PORT` - SSH port (defaults to 22)
- `PROD_SERVER_NAME` - Production server name
- `PROD_APP_SECRET` - Symfony app secret
- `PROD_SENTRY_DSN` - Sentry DSN for error tracking
- `PROD_EXPO_DSN` - Expo DSN for notifications
- `PROD_MERCURE_JWT_SECRET` - Mercure JWT secret
- `PROD_DATABASE_URL` - Complete database URL

**Usage:**
```bash
# Create and push a new version tag
git tag v1.0.0
git push origin v1.0.0
```

---

### 2. Manage Database (`database-manage.yml`)

Manually manage the database service on the production server.

**Trigger:** Manual workflow dispatch

**Actions Available:**
- **start** - Start the database service
- **restart** - Restart the database service (default)
- **stop** - Stop the database service
- **status** - Check database service status and show logs

**Required Secrets:**
- `SSH_PRIVATE_KEY` - SSH private key for server access
- `SSH_HOST` - Server hostname or IP
- `SSH_USER` - SSH username
- `SSH_PORT` - SSH port (defaults to 22)
- `POSTGRES_DB` - Database name
- `POSTGRES_USER` - Database user
- `POSTGRES_PASSWORD` - Database password
- `POSTGRES_VERSION` - PostgreSQL version (defaults to 16)

**Usage:**

1. Go to the **Actions** tab in your GitHub repository
2. Select **"Manage Database Service"** from the workflows list
3. Click **"Run workflow"**
4. Select the action you want to perform:
   - **start** - To start the database service
   - **restart** - To restart the database (useful after configuration changes)
   - **stop** - To stop the database (use with caution!)
   - **status** - To check if the database is running and healthy
5. Click **"Run workflow"** to execute

**Example Scenarios:**

- **First-time setup:** Run with `start` action to initialize the database
- **After configuration changes:** Run with `restart` action to apply changes
- **Troubleshooting:** Run with `status` action to check logs
- **Maintenance:** Run with `stop` action before server maintenance

---

### 3. Push (`push.yml`)

Runs tests and checks on every push to the repository.

**Trigger:** Push to any branch

---

## Setting Up Secrets

To configure the workflows, you need to add the required secrets to your GitHub repository:

1. Go to your repository on GitHub
2. Click on **Settings** → **Secrets and variables** → **Actions**
3. Click **"New repository secret"**
4. Add each required secret

### SSH Configuration Secrets

```
SSH_PRIVATE_KEY=<your-ssh-private-key>
SSH_HOST=<your-server-ip-or-hostname>
SSH_USER=<your-ssh-username>
SSH_PORT=22  # Optional, defaults to 22
```

### Application Secrets

```
PROD_SERVER_NAME=api.killerparty.app
PROD_APP_SECRET=<your-symfony-app-secret>
PROD_SENTRY_DSN=<your-sentry-dsn>
PROD_EXPO_DSN=<your-expo-dsn>
PROD_MERCURE_JWT_SECRET=<your-mercure-jwt-secret>
PROD_DATABASE_URL=postgresql://<user>:<password>@database:5432/<dbname>?serverVersion=16&charset=utf8
```

### Database Secrets

```
POSTGRES_DB=symfokiller
POSTGRES_USER=killer
POSTGRES_PASSWORD=<your-secure-password>
POSTGRES_VERSION=16  # Optional, defaults to 16
```

## Workflow Files Structure

```
.github/
└── workflows/
    ├── README.md                 # This file
    ├── deploy.yml                # Automatic deployment on tag push
    ├── database-manage.yml       # Manual database management
    └── push.yml                  # CI tests on push
```

## Best Practices

1. **Database Management:**
   - Always check the database status before stopping it
   - Use restart instead of stop/start when possible
   - Monitor logs after starting/restarting

2. **Deployment:**
   - Use semantic versioning for tags (v1.0.0, v1.1.0, etc.)
   - Test thoroughly before creating a release tag
   - Monitor deployment logs in GitHub Actions

3. **Security:**
   - Never commit secrets to the repository
   - Rotate SSH keys and passwords regularly
   - Use strong passwords for database credentials
   - Limit SSH access to specific IP addresses when possible

## Troubleshooting

### Workflow Failed - SSH Connection

If the workflow fails with SSH connection errors:

1. Verify SSH secrets are correctly set
2. Check if the server is accessible
3. Verify the SSH key has proper permissions
4. Ensure the SSH user has access to the deployment directory

### Workflow Failed - Database Issues

If the database workflow fails:

1. Run with `status` action to check current state
2. Check if the database container exists: `docker ps -a`
3. Review database logs: `docker compose -f compose.prod.yaml logs database`
4. Verify database secrets are correctly set
5. Check if the database volume has enough space

### Workflow Failed - Permission Denied

If you get permission denied errors:

1. Verify the SSH user has proper permissions
2. Check if the user is in the docker group: `groups <username>`
3. Ensure the application directory has correct ownership

## Support

For issues with the workflows:

1. Check the workflow logs in the **Actions** tab
2. Review the relevant workflow file for configuration
3. Verify all required secrets are set correctly
4. Check the server logs for more details
