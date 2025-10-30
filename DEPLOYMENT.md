# Red Sox Landing Page - Deployment Guide

## Quick Deployment Steps

### Option 1: Traditional PHP Hosting (Recommended)

1. **Upload files to web server:**
   ```bash
   # Upload entire 'public' directory contents to web root
   # Or configure DocumentRoot to point to /path/to/MLB-Baseball-Impact/public
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env
   # Edit .env with production database credentials
   nano .env
   ```

3. **Set file permissions:**
   ```bash
   chmod 644 .env
   chmod 755 public/
   ```

4. **Test the site:**
   - Visit: https://yourdomain.com/redsox-landing.php
   - Check API: https://yourdomain.com/api/redsox/tables.php

### Option 2: Vercel (Serverless)

1. **Install Vercel CLI:**
   ```bash
   npm i -g vercel
   ```

2. **Create vercel.json:**
   ```json
   {
     "version": 2,
     "builds": [
       {
         "src": "public/**/*.php",
         "use": "vercel-php@0.6.0"
       }
     ],
     "routes": [
       {
         "src": "/(.*)",
         "dest": "/public/$1"
       }
     ],
     "env": {
       "MLB_DB_HOST": "@mlb_db_host",
       "MLB_DB_PORT": "@mlb_db_port",
       "MLB_DB_NAME": "@mlb_db_name",
       "MLB_DB_USER": "@mlb_db_user",
       "MLB_DB_PASS": "@mlb_db_pass"
     }
   }
   ```

3. **Deploy:**
   ```bash
   vercel --prod
   
   # Add secrets via dashboard or CLI:
   vercel secrets add mlb_db_host "your-host"
   vercel secrets add mlb_db_port "3306"
   vercel secrets add mlb_db_name "mlb"
   vercel secrets add mlb_db_user "your-user"
   vercel secrets add mlb_db_pass "your-pass"
   ```

### Option 3: Docker

1. **Create Dockerfile:**
   ```dockerfile
   FROM php:8.3-apache
   
   # Install MySQL PDO extension
   RUN docker-php-ext-install pdo pdo_mysql
   
   # Copy application
   COPY . /var/www/html/
   
   # Set working directory
   WORKDIR /var/www/html
   
   # Configure Apache
   RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
   RUN a2enmod rewrite
   
   EXPOSE 80
   ```

2. **Create docker-compose.yml:**
   ```yaml
   version: '3.8'
   
   services:
     web:
       build: .
       ports:
         - "8080:80"
       environment:
         - MLB_DB_HOST=db
         - MLB_DB_PORT=3306
         - MLB_DB_NAME=mlb
         - MLB_DB_USER=mlbuser
         - MLB_DB_PASS=mlbpass
       depends_on:
         - db
     
     db:
       image: mysql:8.0
       environment:
         - MYSQL_ROOT_PASSWORD=rootpass
         - MYSQL_DATABASE=mlb
         - MYSQL_USER=mlbuser
         - MYSQL_PASSWORD=mlbpass
       volumes:
         - ./sql_mysql:/docker-entrypoint-initdb.d
         - mysql_data:/var/lib/mysql
   
   volumes:
     mysql_data:
   ```

3. **Deploy:**
   ```bash
   docker-compose up -d
   ```

### Option 4: AWS EC2/Lightsail

1. **Launch Ubuntu 22.04 instance**

2. **Install LAMP stack:**
   ```bash
   sudo apt update
   sudo apt install apache2 php8.3 php8.3-mysql mysql-server -y
   ```

3. **Configure Apache:**
   ```bash
   sudo nano /etc/apache2/sites-available/000-default.conf
   # Set DocumentRoot to /var/www/html/public
   
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

4. **Upload code and configure .env**

5. **Setup MySQL:**
   ```bash
   sudo mysql -e "CREATE DATABASE mlb;"
   sudo mysql -e "CREATE USER 'mlbuser'@'localhost' IDENTIFIED BY 'secure_password';"
   sudo mysql -e "GRANT ALL PRIVILEGES ON mlb.* TO 'mlbuser'@'localhost';"
   sudo mysql mlb < sql_mysql/01_create_schemas.sql
   sudo mysql mlb < sql_mysql/02_create_staging.sql
   sudo mysql mlb < sql_mysql/04_build_dw.sql
   ```

## Environment Variables

Required environment variables (add to `.env` file):

```bash
# Database Configuration
MLB_DB_HOST=localhost          # or remote MySQL host
MLB_DB_PORT=3306              # default MySQL port
MLB_DB_NAME=mlb               # database name
MLB_DB_USER=mlbuser           # database username
MLB_DB_PASS=secure_password   # database password
```

## Security Checklist

- [ ] Set secure database passwords
- [ ] Restrict database user to READ-ONLY access
- [ ] Use HTTPS in production (Let's Encrypt recommended)
- [ ] Set proper file permissions (644 for files, 755 for directories)
- [ ] Never commit `.env` file to version control
- [ ] Enable PHP error logging (not display_errors in production)
- [ ] Configure firewall to allow only HTTP/HTTPS traffic
- [ ] Keep PHP and dependencies updated
- [ ] Use prepared statements (already implemented)

## Post-Deployment Testing

1. **Test Landing Page:**
   ```bash
   curl https://yourdomain.com/redsox-landing.php
   ```

2. **Test API Endpoints:**
   ```bash
   curl https://yourdomain.com/api/redsox/tables.php
   curl "https://yourdomain.com/api/redsox/table-sample.php?table=dw_player_origin&limit=5"
   curl "https://yourdomain.com/api/redsox/final-outcome.php?limit=100"
   ```

3. **Test Analysis Page:**
   ```bash
   curl https://yourdomain.com/redsox-analysis.php
   ```

4. **Verify Database Connection:**
   - Visit landing page, should see table cards (not error message)
   - Visit analysis page, should see data and charts

## Troubleshooting

### Database Connection Issues

1. **Check credentials:**
   ```bash
   mysql -h $MLB_DB_HOST -P $MLB_DB_PORT -u $MLB_DB_USER -p$MLB_DB_PASS -e "USE $MLB_DB_NAME; SHOW TABLES;"
   ```

2. **Check PHP PDO extension:**
   ```bash
   php -m | grep pdo_mysql
   ```

3. **Check .env file loading:**
   ```bash
   # Add debug line to app/db.php
   var_dump($_ENV);
   ```

### API Returns 500 Error

1. **Check PHP error log:**
   ```bash
   tail -f /var/log/apache2/error.log
   # or
   tail -f /var/log/php8.3-fpm.log
   ```

2. **Enable error display temporarily:**
   ```php
   // Add to top of API file
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

### Tables Not Showing

1. **Verify database has 'dw' schema:**
   ```sql
   SHOW DATABASES;
   USE dw;
   SHOW TABLES;
   ```

2. **Load sample data:**
   ```bash
   mysql -u root -p mlb < sql_mysql/04_build_dw.sql
   ```

### CSS Not Loading

1. **Check web server configuration:**
   ```bash
   # Apache - ensure .htaccess is enabled
   # Nginx - configure static file serving
   ```

2. **Verify file paths:**
   ```bash
   ls -la public/assets/css/redsox.css
   ```

## Performance Optimization

### Enable Gzip Compression
```apache
# Add to .htaccess
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/css text/html text/javascript application/json
</IfModule>
```

### Enable Browser Caching
```apache
# Add to .htaccess
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType text/javascript "access plus 1 month"
  ExpiresByType image/svg+xml "access plus 1 month"
</IfModule>
```

### Database Query Optimization
```sql
-- Add indexes for faster queries
CREATE INDEX idx_year ON dw_roster_composition(year);
CREATE INDEX idx_origin ON dw_player_origin(origin);
```

## Monitoring

### Setup Health Check
Create `public/health.php`:
```php
<?php
require_once __DIR__ . '/../app/db.php';
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'database' => Db::isConnected() ? 'connected' : 'disconnected'
];

echo json_encode($health);
```

### Monitor with Uptime Robot
- Monitor: https://yourdomain.com/health.php
- Alert on: HTTP 500 or database disconnected

## Backup Strategy

1. **Database backups:**
   ```bash
   # Daily backup
   mysqldump -u mlbuser -p mlb > backup-$(date +%Y%m%d).sql
   ```

2. **Code backups:**
   ```bash
   # Git is already your backup
   git push origin main
   ```

## Support

For issues or questions:
- Check REDSOX_README.md for detailed documentation
- Review GitHub Issues
- Contact: CS437 MLB Project Team
