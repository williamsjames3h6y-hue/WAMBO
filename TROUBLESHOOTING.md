# Troubleshooting Guide - 404 Error Fix

## Issue
Your website shows "This Page Does Not Exist" (404 error) when accessing pages.

## Root Cause
The 404 error is caused by missing URL rewriting configuration on your web server.

---

## Solution

### For Apache Servers (Most Common)

1. **Upload the .htaccess file**
   - The `.htaccess` file has been created in your project root
   - Make sure it's uploaded to your web server in the same directory as `index.php`
   - Ensure the filename is exactly `.htaccess` (starts with a dot)

2. **Enable mod_rewrite in Apache**
   - Contact your hosting provider or check cPanel if mod_rewrite is enabled
   - Most hosting providers have this enabled by default

3. **Check File Permissions**
   ```bash
   chmod 644 .htaccess
   ```

### For Nginx Servers

If your server uses Nginx instead of Apache, add this to your nginx configuration:

```nginx
server {
    listen 80;
    server_name earningsllc.online www.earningsllc.online;
    root /path/to/your/project;
    index index.php index.html;

    # Remove .php extension from URLs
    location / {
        try_files $uri $uri/ $uri.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Protect sensitive files
    location ~ /\.(env|git|htaccess) {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp)$ {
        expires 365d;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## Quick Fixes

### Fix 1: Check if .htaccess is working
Create a test file to verify .htaccess is being read:

1. Add this line to the top of .htaccess:
   ```apache
   # Test comment
   ```

2. Try accessing a non-existent file - you should get a proper 404 page

### Fix 2: Verify PHP is working
Create `test.php` in your root directory:
```php
<?php
phpinfo();
```

Access `https://earningsllc.online/test.php` - you should see PHP information.

### Fix 3: Check Document Root
Make sure your web server's document root points to the directory containing your PHP files, not a subdirectory.

---

## Files Updated

1. **`.htaccess`** (NEW) - URL rewriting rules for Apache
2. **`.env`** (FIXED) - Removed incorrect Supabase credentials, added proper MySQL config template

---

## After Fixing

Once the .htaccess is working properly, you should be able to access:

- `https://earningsllc.online/` - Home page
- `https://earningsllc.online/register` - Registration (without .php)
- `https://earningsllc.online/login` - Login (without .php)
- `https://earningsllc.online/dashboard` - Dashboard (without .php)

---

## Still Having Issues?

### Check with your hosting provider:

1. **Is mod_rewrite enabled?**
   - Most shared hosting has this enabled
   - VPS/Dedicated servers may need manual activation

2. **Are .htaccess files allowed?**
   - Check if `AllowOverride All` is set in Apache config
   - Some hosts disable .htaccess files

3. **What is the document root?**
   - Ensure it points to your project root folder
   - Common paths: `/public_html/`, `/www/`, `/htdocs/`

4. **Check Error Logs**
   - cPanel > Error Log
   - Or ask hosting support for recent error logs

---

## Contact Hosting Support

If none of these solutions work, contact your hosting provider with:

> "I need mod_rewrite enabled and .htaccess files to work for my PHP application.
> My site is showing 404 errors for all pages except direct .php file access.
> Can you verify AllowOverride is set to All in my Apache configuration?"

---

## Database Connection

Your database is already properly configured in `config/database.php`:
- Host: localhost
- Database: u800179901_70
- Username: u800179901_70
- Password: (configured)

No changes needed for database connection.
