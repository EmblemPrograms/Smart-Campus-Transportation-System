# Installation Guide

**Smart Campus Transportation System**  
Federal Polytechnic Ede — HND Final-Year Project  

This guide explains how to install and run the application on a local Windows machine using **XAMPP**.

---

## 1. Prerequisites

| Requirement | Recommended |
|-------------|-------------|
| Operating System | Windows 10 / 11 |
| XAMPP | Latest stable (Apache + MySQL + PHP 8.x) |
| PHP | 8.0 or higher (`declare(strict_types=1)` used throughout) |
| MySQL / MariaDB | Bundled with XAMPP |
| Browser | Chrome, Edge, or Firefox (latest) |
| Disk space | ~100 MB for project + database |

Confirm PHP version after installing XAMPP:

```bash
php -v
```

---

## 2. Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/).
2. Run the installer and install to the default path (e.g. `C:\xampp`).
3. Open **XAMPP Control Panel**.
4. Start **Apache** and **MySQL**. Both should show a green *Running* status.

If Apache fails to start (port 80 in use), either stop IIS / Skype, or change Apache’s `Listen` port in `httpd.conf` and adjust `APP_URL` accordingly.

---

## 3. Deploy Project Files

1. Copy the entire project folder `smart-campus-transport` into the Apache document root:

   ```
   C:\xampp\htdocs\smart-campus-transport\
   ```

2. The landing page should then be reachable at:

   ```
   http://localhost/smart-campus-transport/
   ```

> If your project path differs (for example under `D:\software\...`), either copy it into `htdocs` or configure a virtual host (Section 7).

---

## 4. Configure Database Connection

Edit `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // default XAMPP MySQL has empty password
define('DB_NAME', 'smart_campus_transport');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 3306);
```

Update `DB_USER` / `DB_PASS` if you secured MySQL with a password.

---

## 5. Configure Application URL

Edit `config/app.php` and set `APP_URL` to match your browser path:

```php
define('APP_URL', 'http://localhost/smart-campus-transport');
```

If you use a virtual host (e.g. `http://scts.local`), set:

```php
define('APP_URL', 'http://scts.local');
```

For local development, `APP_DEBUG` may remain `true`. Set it to `false` before any public demonstration if you want to hide detailed DB errors.

---

## 6. Create and Seed the Database

You have **two supported methods**. Method A is preferred because it also hashes sample passwords correctly with PHP’s `password_hash()`.

### Method A — CLI Installer (Recommended)

From a terminal (Command Prompt, PowerShell, or XAMPP shell), change to the project directory:

```bash
cd C:\xampp\htdocs\smart-campus-transport
php database/install.php
```

Optional overrides:

```bash
php database/install.php --host=127.0.0.1 --port=3306 --user=root --pass=
```

What it does:

1. Connects to MySQL.
2. Executes `database/schema.sql` (creates database, 18 tables, sample data).
3. Updates demo user passwords to the documented credentials.

### Method B — phpMyAdmin + Password Seeder

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Go to **Import**.
3. Choose `database/schema.sql` and click **Go**.
4. Confirm the database `smart_campus_transport` exists with all tables.
5. Seed real passwords:

   ```bash
   php database/seed_passwords.php
   ```

> **Important:** The SQL file ships with a placeholder bcrypt hash for the word `password`. Always run `install.php` or `seed_passwords.php` so the accounts match the credentials below.

---

## 7. Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@fedpolyede.edu.ng` | `Admin@123` |
| Student | `student1@fedpolyede.edu.ng` | `Student@123` |
| Student (2) | `student2@fedpolyede.edu.ng` | `Student@123` |
| Staff | `staff1@fedpolyede.edu.ng` | `Staff@123` |
| Driver | `driver1@fedpolyede.edu.ng` | `Driver@123` |
| Driver (2) | `driver2@fedpolyede.edu.ng` | `Driver@123` |

---

## 8. Verify Installation

1. Browse to `http://localhost/smart-campus-transport`.
2. You should see the landing page.
3. Click **Login** and sign in as Admin.
4. Confirm the Admin Dashboard loads with statistics.
5. Optionally run automated tests:

   ```bash
   php tests/DijkstraTest.php
   php tests/SecurityTest.php
   php tests/BookingFlowTest.php
   ```

---

## 9. Optional — Apache Virtual Host

### 9.1 Create vhost in `httpd-vhosts.conf`

Typical path: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/smart-campus-transport"
    ServerName scts.local
    <Directory "C:/xampp/htdocs/smart-campus-transport">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 9.2 Map hosts file

Edit `C:\Windows\System32\drivers\etc\hosts` (as Administrator):

```
127.0.0.1   scts.local
```

### 9.3 Enable includes (if needed)

Ensure `httpd.conf` has:

```apache
Include conf/extra/httpd-vhosts.conf
```

And that `mod_rewrite` is enabled:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

### 9.4 Update APP_URL

```php
define('APP_URL', 'http://scts.local');
```

Restart Apache and open `http://scts.local`.

---

## 10. Directory Permissions

Ensure these folders are writable by Apache/PHP (for uploads, logs, generated reports):

- `uploads/`
- `logs/`
- `reports/`

On Windows with XAMPP defaults this is usually already fine. On Linux:

```bash
chmod -R 775 uploads logs reports
chown -R www-data:www-data uploads logs reports
```

---

## 11. Troubleshooting

| Problem | Likely Cause | Solution |
|---------|--------------|----------|
| Blank page / 500 error | PHP fatal error; `APP_DEBUG` false | Enable `APP_DEBUG`, check Apache `error.log` and `logs/` |
| “Unable to connect to the database” | MySQL not running or wrong credentials | Start MySQL in XAMPP; verify `config/database.php` |
| Login fails with documented passwords | Schema imported without password seeder | Run `php database/seed_passwords.php` |
| CSS/JS not loading | Wrong `APP_URL` | Align `APP_URL` with the browser address |
| AJAX returns 401 / 403 | Session expired or CSRF missing | Refresh page and log in again |
| `.htaccess` ignored | `AllowOverride None` | Set `AllowOverride All` for the project directory |
| Port 80 busy | Skype, IIS, or another web server | Stop conflicting service or change Apache port |
| Dijkstra / path fails | Empty `locations`/`roads` tables | Re-run installer; confirm sample road network exists |
| `php` not recognised | PHP not on PATH | Use `C:\xampp\php\php.exe database/install.php` |

---

## 12. Post-Install Checklist

- [ ] Apache and MySQL running  
- [ ] Project under `htdocs` (or vhost configured)  
- [ ] `config/database.php` credentials correct  
- [ ] `APP_URL` matches browser URL  
- [ ] Database created and passwords seeded  
- [ ] Admin login works  
- [ ] Student booking page loads  
- [ ] Dijkstra path computation works between two locations  
- [ ] CLI tests pass  

---

## Related Documents

- [README.md](../README.md)  
- [System Manual](SYSTEM_MANUAL.md)  
- [Database Documentation](DATABASE_DOCUMENTATION.md)  
- [Developer Documentation](DEVELOPER_DOCUMENTATION.md)  
