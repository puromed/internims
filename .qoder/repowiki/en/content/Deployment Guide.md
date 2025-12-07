# Deployment Guide

<cite>
**Referenced Files in This Document**   
- [composer.json](file://composer.json)
- [.env.example](file://.env.example)
- [artisan](file://artisan)
- [public/index.php](file://public/index.php)
- [config/app.php](file://config/app.php)
- [config/database.php](file://config/database.php)
- [config/queue.php](file://config/queue.php)
- [config/cache.php](file://config/cache.php)
- [config/services.php](file://config/services.php)
- [vite.config.js](file://vite.config.js)
- [package.json](file://package.json)
- [vendor/laravel/sail/runtimes/8.2/supervisord.conf](file://vendor/laravel/sail/runtimes/8.2/supervisord.conf)
- [internship_management_system_implementation_plan.md](file://internship_management_system_implementation_plan.md)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [Server Requirements](#server-requirements)
3. [Environment Configuration](#environment-configuration)
4. [Deployment Process](#deployment-process)
5. [Queue Worker Configuration](#queue-worker-configuration)
6. [Web Server Configuration](#web-server-configuration)
7. [CI/CD Pipeline Examples](#cicd-pipeline-examples)
8. [Common Deployment Issues](#common-deployment-issues)
9. [Troubleshooting Guide](#troubleshooting-guide)
10. [Performance Optimization](#performance-optimization)

## Introduction
This deployment guide provides comprehensive instructions for deploying the Internship Management System to production environments. The system is built on Laravel 12 with Livewire, utilizing modern PHP features and a robust queue system for AI analysis jobs. This document covers all aspects of production deployment including server requirements, environment configuration, deployment workflows, queue management, web server setup, and performance optimization strategies.

**Section sources**
- [composer.json](file://composer.json)
- [internship_management_system_implementation_plan.md](file://internship_management_system_implementation_plan.md)

## Server Requirements
The Internship Management System requires specific server components to function properly in production. The minimum requirements are:

- **PHP**: Version 8.2 or higher (as specified in composer.json)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Database**: MySQL 8.0+, PostgreSQL 12+, or SQLite 3.8.8+
- **Node.js**: Version 18+ for asset compilation
- **Redis**: Version 6.0+ (recommended for queue and cache)
- **Composer**: Version 2.5+
- **Vite**: For frontend asset compilation

The application also requires several PHP extensions including OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, and Fileinfo. These are standard Laravel requirements that ensure proper functionality of the framework and its components.

**Section sources**
- [composer.json](file://composer.json#L12)
- [internship_management_system_implementation_plan.md](file://internship_management_system_implementation_plan.md#L9)

## Environment Configuration
Proper environment configuration is critical for secure and efficient operation of the Internship Management System. The system uses a .env file for configuration, with .env.example providing the template.

### Core Environment Variables
The following environment variables must be configured for production:

```env
APP_NAME=Internship Management System
APP_ENV=production
APP_KEY=your-generated-app-key
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internship_system
DB_USERNAME=your-db-username
DB_PASSWORD=your-db-password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

GEMINI_API_KEY=your-gemini-api-key
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
```

### AI Service Configuration
For AI analysis features, configure the appropriate service providers:

```env
# Gemini API configuration
GEMINI_API_KEY=your-gemini-api-key
GEMINI_MODEL=gemini-2.5-pro

# Alternative Z.AI configuration
ZAI_API_KEY=your-zai-api-key
ZAI_ENDPOINT=https://api.z.ai/api/paas/v4/chat/completions
```

### Cache and Queue Configuration
Optimize cache and queue settings for production performance:

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
DB_QUEUE_RETRY_AFTER=180
REDIS_QUEUE_RETRY_AFTER=180
```

**Section sources**
- [.env.example](file://.env.example)
- [config/database.php](file://config/database.php)
- [config/cache.php](file://config/cache.php)
- [config/queue.php](file://config/queue.php)
- [config/services.php](file://config/services.php)

## Deployment Process
The deployment process for the Internship Management System follows a standardized sequence to ensure reliability and consistency across environments.

### Step-by-Step Deployment Instructions
1. **Code Transfer**: Deploy the application code to the production server using your preferred method (Git, SFTP, CI/CD pipeline, etc.)

2. **Dependency Installation**: Install PHP and JavaScript dependencies:
   ```bash
   # Install PHP dependencies with optimization
   composer install --optimize-autoloader --no-dev
   
   # Install JavaScript dependencies
   npm install
   ```

3. **Environment Setup**: Configure the environment file and generate application key:
   ```bash
   # Copy environment template
   cp .env.example .env
   
   # Generate application encryption key
   php artisan key:generate
   ```

4. **Database Migration**: Run database migrations to set up the schema:
   ```bash
   php artisan migrate --force
   ```

5. **Asset Compilation**: Compile frontend assets for production:
   ```bash
   npm run build
   ```

6. **Cache Configuration**: Clear and configure application caches:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### Automated Deployment Script
The composer.json file includes a setup script that automates many deployment steps:

```json
"scripts": {
    "setup": [
        "composer install",
        "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
        "@php artisan key:generate",
        "@php artisan migrate --force",
        "npm install",
        "npm run build"
    ]
}
```

This script can be executed with `composer run setup` to perform the complete deployment process.

**Section sources**
- [composer.json](file://composer.json#L42-L48)
- [artisan](file://artisan)
- [vite.config.js](file://vite.config.js)

## Queue Worker Configuration
The Internship Management System relies on queue workers to process AI analysis jobs asynchronously. Proper configuration ensures these jobs are processed reliably.

### Supervisor Configuration
Supervisor is recommended for managing queue workers. The following configuration should be placed in `/etc/supervisor/conf.d/internims-worker.conf`:

```ini
[program:internims-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-app/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --max-jobs=250
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path-to-your-app/storage/logs/worker.log
stopwaitsecs=3600
```

### Worker Configuration Options
Key configuration options for optimal performance:

- **numprocs**: Number of worker processes (typically 1-2 per CPU core)
- **max-time**: Maximum execution time before worker restart (3600 seconds recommended)
- **max-jobs**: Maximum jobs per worker before restart (250 recommended to prevent memory leaks)
- **sleep**: Time to wait between job checks (3 seconds)
- **tries**: Number of retry attempts for failed jobs (3 recommended)

### Alternative Queue Management
For environments where Supervisor is not available:

- **Systemd**: Create systemd service units for queue workers
- **Docker**: Use Docker containers with restart policies
- **Cloud Services**: Utilize managed services like AWS Elastic Beanstalk or Laravel Forge

**Section sources**
- [config/queue.php](file://config/queue.php)
- [vendor/laravel/sail/runtimes/8.2/supervisord.conf](file://vendor/laravel/sail/runtimes/8.2/supervisord.conf)
- [internship_management_system_implementation_plan.md](file://internship_management_system_implementation_plan.md#L128)

## Web Server Configuration
Proper web server configuration ensures requests are routed correctly to the Laravel application.

### Apache Configuration
Create a virtual host configuration with the following settings:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/your/app/public

    <Directory /path/to/your/app/public>
        AllowOverride All
        Require all granted
        Options -MultiViews
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/internims_error.log
    CustomLog ${APACHE_LOG_DIR}/internims_access.log combined
</VirtualHost>
```

Ensure the .htaccess file in the public directory is properly configured for URL rewriting.

### Nginx Configuration
Create an Nginx server block with the following configuration:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/app/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### SSL Configuration
For production environments, implement SSL using Let's Encrypt:

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # Additional SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
}
```

**Section sources**
- [public/index.php](file://public/index.php)
- [config/app.php](file://config/app.php)

## CI/CD Pipeline Examples
Implementing automated CI/CD pipelines ensures consistent and reliable deployments.

### GitHub Actions Example
```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Install dependencies
      run: |
        composer install --optimize-autoloader --no-dev
        npm install
        
    - name: Build assets
      run: npm run build
      
    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.SSH_KEY }}
        script: |
          cd /path/to/app
          git pull origin main
          composer install --optimize-autoloader --no-dev
          npm install
          npm run build
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          sudo supervisorctl reload
```

### Deployment Script
Create a deployment script (deploy.sh) for manual deployments:

```bash
#!/bin/bash
# Production deployment script

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install JavaScript dependencies
npm install

# Build assets
npm run build

# Run migrations
php artisan migrate --force

# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart

echo "Deployment completed successfully!"
```

**Section sources**
- [composer.json](file://composer.json)
- [package.json](file://package.json)

## Common Deployment Issues
This section addresses common issues encountered during deployment and their solutions.

### File Permissions
Incorrect file permissions are a frequent cause of deployment failures:

```bash
# Set proper ownership
sudo chown -R www-data:www-data /path/to/your/app

# Set proper permissions
sudo find /path/to/your/app -type f -exec chmod 644 {} \;
sudo find /path/to/your/app -type d -exec chmod 755 {} \;

# Storage and cache directories need write permissions
sudo chmod -R 775 storage bootstrap/cache
```

### Queue Worker Crashes
Common causes and solutions:

- **Memory exhaustion**: Reduce max-jobs parameter and restart workers periodically
- **Database connection timeouts**: Increase DB_WAIT_TIMEOUT and implement proper error handling
- **Long-running jobs**: Implement job timeouts and chunk large tasks
- **Worker not starting**: Check logs at storage/logs/worker.log and verify PHP version compatibility

### Asset Versioning
To prevent browser caching issues:

```bash
# Clear old assets
php artisan view:clear
php artisan cache:clear

# Rebuild with new version
npm run build

# Alternatively, use versioning
php artisan view:cache
```

### Environment-Specific Issues
- **Debug mode enabled**: Ensure APP_DEBUG=false in production
- **Missing APP_KEY**: Always generate a new APP_KEY for production
- **Incorrect database credentials**: Verify DB connection details in .env

**Section sources**
- [config/app.php](file://config/app.php)
- [config/database.php](file://config/database.php)
- [storage/logs](file://storage/logs)

## Troubleshooting Guide
This section provides guidance for diagnosing and resolving common deployment failures.

### Deployment Failure Diagnosis
When deployment fails, follow this diagnostic process:

1. **Check application logs**: Review storage/logs/laravel.log for errors
2. **Verify environment variables**: Ensure all required .env variables are set
3. **Test database connection**: Use php artisan tinker to test DB::connection()
4. **Check queue worker status**: Use php artisan queue:work --daemon to test
5. **Verify file permissions**: Ensure proper ownership of storage and bootstrap/cache

### Performance Issues
For slow application performance:

- **Enable OPcache**: Configure PHP OPcache for improved performance
- **Database indexing**: Ensure proper indexes on frequently queried columns
- **Query optimization**: Use Laravel Debugbar to identify slow queries
- **Redis caching**: Implement Redis for frequent data access

### AI Service Integration Issues
For problems with AI analysis jobs:

- **Verify API keys**: Ensure GEMINI_API_KEY or ZAI_API_KEY is correctly set
- **Check network connectivity**: Ensure outbound connections to AI services
- **Monitor job failures**: Review failed_jobs table for processing errors
- **Adjust timeouts**: Increase DB_QUEUE_RETRY_AFTER for slow AI responses

**Section sources**
- [storage/logs](file://storage/logs)
- [config/queue.php](file://config/queue.php)
- [internship_management_system_implementation_plan.md](file://internship_management_system_implementation_plan.md#L126)

## Performance Optimization
Optimize the Internship Management System for production performance with these recommendations.

### Caching Strategies
Implement multiple caching layers:

```php
// Configuration cache
php artisan config:cache

// Route cache
php artisan route:cache

// View cache
php artisan view:cache

// Database query caching
Cache::remember('key', $seconds, function () {
    return DB::table('users')->get();
});
```

### Database Optimization
- **Indexing**: Add indexes to frequently queried columns
- **Connection pooling**: Use persistent database connections
- **Query optimization**: Avoid N+1 queries with eager loading
- **Database maintenance**: Regularly optimize tables and update statistics

### Frontend Optimization
- **Asset minification**: Ensure npm run build produces optimized assets
- **CDN usage**: Serve static assets through a CDN
- **Browser caching**: Configure proper cache headers for static files
- **Image optimization**: Compress and resize images appropriately

### Server-Level Optimization
- **OPcache**: Enable and configure PHP OPcache
- **Redis**: Use Redis for session storage and caching
- **Load balancing**: Distribute traffic across multiple application servers
- **Monitoring**: Implement application performance monitoring (APM)

**Section sources**
- [config/cache.php](file://config/cache.php)
- [vite.config.js](file://vite.config.js)
- [internship_management_system_implementation_plan.md](file://internship_management_system_implementation_plan.md#L127)