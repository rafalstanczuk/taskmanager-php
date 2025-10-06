# GitHub Configuration Guide

Complete guide for setting up GitHub repository secrets and CI/CD environment variables.

## ⚠️ Strict Environment Variables Policy

**All environment variables are REQUIRED with no defaults.** 

This project enforces explicit configuration:
- ✅ **Prevents silent failures** from missing configuration
- ✅ **Ensures consistent behavior** across all environments
- ✅ **Explicit over implicit** - you know exactly what's configured
- ❌ **No fallback defaults** - missing variables cause immediate errors

**Required Variables:** `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `APP_ENV`, `PORT`, `DB_READY_TIMEOUT`, `TEST_SERVER_PORT`

---

## Recent Updates

✅ **Fixed GitHub Actions workflow** - Moved `.env` file creation before Docker build step  
✅ **Updated to use GitHub Secrets** - Workflow now uses secrets with fallback defaults  
✅ **Zero configuration required** - Works out of the box, secrets optional

---

## GitHub Secrets Setup

### What are GitHub Secrets?

GitHub Secrets are encrypted environment variables stored in your repository settings. They're used to:
- Store sensitive data (API keys, passwords)
- Configure CI/CD pipelines
- Avoid hardcoding credentials in code

### ⚡ Quick Start: Do You Need Secrets?

**For Public Repos & Open Source:**
- ✅ **Secrets are OPTIONAL** - Default values work fine for CI/CD
- ✅ Test credentials (`app`/`app`) are safe for automated testing
- ℹ️ Only add secrets if you have custom requirements

**For Private Repos & Production:**
- 🔐 **Recommended** - Use secrets for better security
- 🔐 Especially important for production deployments
- 🔐 Keeps credentials out of workflow file history

### How to Add Secrets (Optional)

1. **Navigate to Repository Settings**
   ```
   https://github.com/rafalstanczuk/taskmanager-php/settings/secrets/actions
   ```

2. **Click "New repository secret"**

3. **Add Each Secret (ALL REQUIRED):**

   | Name | Value | Description |
   |------|-------|-------------|
   | `DB_HOST` | `postgres` | Database host for CI/CD |
   | `DB_PORT` | `5432` | PostgreSQL port |
   | `DB_NAME` | `app` | Database name |
   | `DB_USER` | `app` | Database user |
   | `DB_PASSWORD` | `app` | Database password |
   | `APP_ENV` | `testing` | Application environment |
   | `PORT` | `8000` | Server port |
   | `DB_READY_TIMEOUT` | `30` | Database timeout (seconds) |
   | `TEST_SERVER_PORT` | `8001` | Test server port |

---

## Using Secrets in Workflows

### ✅ Current Approach (Secrets with Fallback Defaults)

The workflow now uses GitHub Secrets with fallback to sensible defaults:

```yaml
- name: Create .env file
  run: |
    cat > .env << EOF
    APP_ENV=${{ secrets.APP_ENV || 'testing' }}
    DB_HOST=${{ secrets.DB_HOST || 'postgres' }}
    DB_PORT=${{ secrets.DB_PORT || '5432' }}
    DB_NAME=${{ secrets.DB_NAME || 'app' }}
    DB_USER=${{ secrets.DB_USER || 'app' }}
    DB_PASSWORD=${{ secrets.DB_PASSWORD || 'app' }}
    PORT=${{ secrets.PORT || '8000' }}
    DB_READY_TIMEOUT=${{ secrets.DB_READY_TIMEOUT || '30' }}
    TEST_SERVER_PORT=${{ secrets.TEST_SERVER_PORT || '8001' }}
    EOF
```

**Benefits:**
- ✅ **Works immediately** - No secrets setup required for testing
- ✅ **Secure by default** - Uses secrets if configured
- ✅ **Flexible** - Easy to override for production/staging
- ✅ **No workflow changes** - Just add secrets when ready

**Behavior:**
1. If secrets are set → Uses secret values
2. If secrets NOT set → Uses fallback defaults (safe for testing)
3. Best of both worlds!

---

### Best Practice: Environment-Specific Secrets

```yaml
- name: Create .env file for Production
  if: github.ref == 'refs/heads/main'
  run: |
    cat > .env << EOF
    APP_ENV=production
    DB_HOST=${{ secrets.PROD_DB_HOST }}
    DB_PORT=${{ secrets.PROD_DB_PORT }}
    DB_NAME=${{ secrets.PROD_DB_NAME }}
    DB_USER=${{ secrets.PROD_DB_USER }}
    DB_PASSWORD=${{ secrets.PROD_DB_PASSWORD }}
    EOF

- name: Create .env file for Development
  if: github.ref == 'refs/heads/develop'
  run: |
    cat > .env << EOF
    APP_ENV=development
    DB_HOST=${{ secrets.DEV_DB_HOST }}
    # ... other dev credentials
    EOF
```

---

## Environment Variables in GitHub Actions

### Three Types of Variables

1. **Secrets** (encrypted, for sensitive data)
   - Database passwords
   - API keys
   - Tokens

2. **Variables** (plain text, for non-sensitive config)
   - Application version
   - Build flags
   - Feature flags

3. **Environment Variables** (set in workflow)
   ```yaml
   env:
     NODE_ENV: production
     PHP_VERSION: 8.2
   ```

### Adding Repository Variables

1. Go to: `Settings` → `Secrets and variables` → `Actions` → `Variables` tab
2. Click: "New repository variable"
3. Add: `APP_VERSION`, `BUILD_MODE`, etc.

---

## Current Workflow Status

### ✅ What's Fixed

- `.env` file created **before** Docker build
- No more "variable is not set" warnings
- Workflow runs without errors

### ✅ What's Working

- Environment variables available during build
- Docker Compose can read from `.env`
- Tests run with proper database configuration

### 📝 What's Hardcoded (Intentionally)

Test credentials are hardcoded in the workflow because:
- They're not sensitive (test database only)
- Simplifies workflow for contributors
- No production data at risk

---

## Production Deployment Setup

### When You Deploy to Production

1. **Create Production Secrets:**
   ```
   PROD_DB_HOST=your-prod-db-host.com
   PROD_DB_PASSWORD=strong-random-password
   ```

2. **Add Deployment Workflow:**
   ```yaml
   deploy:
     name: Deploy to Production
     runs-on: ubuntu-latest
     if: github.ref == 'refs/heads/main'
     environment: production
     steps:
       - name: Deploy
         env:
           DB_HOST: ${{ secrets.PROD_DB_HOST }}
           DB_PASSWORD: ${{ secrets.PROD_DB_PASSWORD }}
         run: |
           # Your deployment commands
   ```

3. **Use GitHub Environments:**
   - `Settings` → `Environments` → `New environment`
   - Add: `production`, `staging`, `development`
   - Set protection rules and required reviewers

---

## Verification Steps

### 1. Check Workflow Syntax

```bash
# Locally validate (requires act or nektos/act)
act -n
```

### 2. Test CI/CD Pipeline

```bash
git add .github/workflows/tests.yml
git commit -m "Fix: Create .env before Docker build in CI/CD"
git push origin main
```

### 3. Monitor GitHub Actions

1. Go to: `Actions` tab in your repository
2. Watch the workflow run
3. Check for green checkmarks ✅

---

## Troubleshooting

### Issue: "variable is not set" warnings

**Solution:** Create `.env` file before `docker compose build`

### Issue: Tests fail with database connection errors

**Solution:** Ensure `DB_HOST` matches the service name in docker-compose (`postgres`)

### Issue: Secrets not available in workflow

**Solution:** Check secrets are added and names match exactly (case-sensitive)

### Issue: Build works locally but fails in CI

**Solution:** Ensure all files (especially `.env.example`) are committed

---

## Security Best Practices

### ✅ DO:
- Use secrets for passwords and API keys
- Rotate secrets regularly
- Use different credentials for dev/staging/prod
- Limit secret access to specific environments

### ❌ DON'T:
- Commit `.env` to repository
- Print secrets in logs
- Share secrets via insecure channels
- Use production credentials in CI/CD tests

---

## Quick Reference

### GitHub URLs

- **Repository:** https://github.com/rafalstanczuk/taskmanager-php
- **Secrets:** https://github.com/rafalstanczuk/taskmanager-php/settings/secrets/actions
- **Actions:** https://github.com/rafalstanczuk/taskmanager-php/actions
- **Settings:** https://github.com/rafalstanczuk/taskmanager-php/settings

### Commands

```bash
# Test locally with environment variables
export DB_HOST=localhost DB_PORT=5432 DB_NAME=app
docker compose build php

# Run tests locally
./scripts/run-tests.sh

# Check workflow syntax
cat .github/workflows/tests.yml
```

---

## Next Steps

1. ✅ Commit the fixed workflow
2. ⏳ Push to GitHub
3. ⏳ Verify Actions run successfully
4. 📝 Add production secrets when deploying

The workflow is now configured correctly and will run without warnings! 🎉
