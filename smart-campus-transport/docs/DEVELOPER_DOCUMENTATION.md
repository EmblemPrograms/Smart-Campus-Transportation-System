# Developer Documentation

**Smart Campus Transportation System**  
Federal Polytechnic Ede — HND Final-Year Project  

This document describes the software architecture, coding standards, Dijkstra usage, security practices, and how to extend the system.

---

## 1. Architecture Overview

The application follows an **MVC-like** structure implemented in Core PHP 8 without a heavy framework:

| Layer | Location | Responsibility |
|-------|----------|----------------|
| Configuration | `config/` | App constants, DB connection, autoload |
| Includes | `includes/` | Cross-cutting helpers (auth, CSRF, session) |
| Models | `models/` | Data access via MySQLi prepared statements |
| Controllers | `controllers/` | Orchestrate business rules for pages / AJAX |
| Views | `views/` | Role-based PHP templates + layouts |
| Classes | `classes/` | Domain algorithms (Dijkstra) |
| AJAX API | `ajax/` | JSON endpoints consumed by jQuery |
| Assets | `assets/` | CSS/JS (Bootstrap via CDN) |
| Database | `database/` | Schema, installer, password seeder |

**Request flow (typical page):**

1. Entry script / view loads `config/app.php`.  
2. Session starts; remember-me and auth helpers run.  
3. Controller / model methods fetch or mutate data.  
4. View renders HTML with Bootstrap 5 layout (`views/layouts/`).  

**Request flow (AJAX):**

1. Browser calls `ajax/*.php` with action + parameters.  
2. Endpoint authenticates, validates CSRF on mutating POSTs.  
3. Model / controller logic executes.  
4. `jsonResponse()` returns JSON and exits.  

---

## 2. Bootstrap and Autoloading

`config/app.php` defines constants (`APP_URL`, roles, booking statuses, upload limits, etc.), requires core includes, and registers an `spl_autoload_register` that loads from:

- `classes/{Class}.php`  
- `models/{Class}.php`  
- `controllers/{Class}.php`  

`getDB()` in `config/database.php` returns a **singleton MySQLi** connection with `utf8mb4` and Africa/Lagos-aligned SQL timezone (`+01:00`).

---

## 3. Coding Standards

Follow these conventions for academic consistency and maintainability:

1. **`declare(strict_types=1);`** at the top of every PHP file.  
2. Use **prepared statements** (`bind_param`) for all SQL with user input.  
3. Prefer **static model methods** (`Booking::findById`, `User::create`) for data access.  
4. Escape output with **`e()`** (HTMLspecialchars) in views.  
5. Sanitize string input with **`clean()`** (trim + strip_tags).  
6. Protect state-changing requests with **`requireCsrf()`** / `csrfField()`.  
7. Guard pages with **`requireLogin()`** / **`requireRole()`**.  
8. Use named constants for roles and statuses (`ROLE_ADMIN`, `BOOKING_PENDING`, …).  
9. Log significant actions with **`logActivity()`**.  
10. Keep controllers thin; put SQL in models; keep Dijkstra logic in `classes/Dijkstra.php`.  

### Naming

| Item | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `BookingController`, `Dijkstra` |
| Methods / functions | camelCase | `shortestPath`, `generateToken` |
| DB tables / columns | snake_case | `booking_number`, `trip_tracking` |
| AJAX files | kebab-case | `booking-actions.php` |

---

## 4. Directory Responsibilities (Detail)

### Models (`models/`)

Examples: `User`, `Student`, `Staff`, `Driver`, `Vehicle`, `Location`, `Road`, `Route`, `Schedule`, `Booking`, `Notification`, `Tracking`, `Report`, `ActivityLog`, `Setting`.

Each model encapsulates CRUD and queries for one domain aggregate.

### Controllers (`controllers/`)

- `AuthController` — login, register, password flows  
- `AdminController` — admin module orchestration  
- `BookingController` — schedules, seats, create/cancel, path preview  
- `DriverController` — trips and driver actions  
- `ReportController` — reporting endpoints / pages  

### Views (`views/`)

Organised by role: `admin/`, `student/`, `staff/`, `driver/`, `auth/`, plus shared `layouts/` (header, navbar, sidebar, footer, spinner).

---

## 5. Adding a New Module (Step-by-Step)

Example: adding a **Maintenance Tickets** module.

1. **Database**  
   - Design table(s) in `schema.sql` (and a migration note for existing installs).  
   - Add foreign keys to `users` / `vehicles` as needed.

2. **Model**  
   - Create `models/MaintenanceTicket.php` with `create`, `findById`, `all`, `updateStatus`.

3. **Controller**  
   - Create `controllers/MaintenanceController.php` with methods used by views/AJAX.

4. **Views**  
   - Add `views/admin/maintenance.php` (list/form).  
   - Link from `views/layouts/sidebar.php` for the admin role.

5. **AJAX (optional)**  
   - Add `ajax/maintenance.php` with actions (`list`, `create`, `close`).  
   - Document in `docs/API_DOCUMENTATION.md`.

6. **Security**  
   - Call `requireRole('admin')` and `requireCsrf()` on POST.  
   - Use prepared statements only.

7. **Activity / Notifications**  
   - Call `logActivity()` and optionally `createNotification()`.

8. **Tests**  
   - Extend CLI tests or add checklist cases in `docs/TESTING_DOCUMENTATION.md`.

---

## 6. Dijkstra Class Usage

File: `classes/Dijkstra.php`

Campus locations are **nodes**; roads are **weighted edges** (distance in metres). Bidirectional roads are inserted in both directions when loading from the database.

### 6.1 Database-backed usage (production)

```php
$dijkstra = new Dijkstra(true); // auto-builds from locations + roads
$result = $dijkstra->shortestPath($startId, $endId);

if ($result['success']) {
    $details = $dijkstra->getPathDetails();
    // $details['path'], path_names, distance, hops, segments, ...
}
```

### 6.2 Manual graph (unit tests / offline)

```php
$dijkstra = new Dijkstra(false); // do not hit DB
$dijkstra->addEdge(1, 2, 450.0);
$dijkstra->addEdge(2, 3, 300.0);
$dijkstra->addEdge(1, 3, 900.0);

$result = $dijkstra->shortestPath(1, 3);
// Expect path [1, 2, 3], distance 750
```

### 6.3 Key methods

| Method | Purpose |
|--------|---------|
| `buildGraph()` | Load active locations/roads from MySQL |
| `addEdge($from, $to, $weight)` | Add directed edge |
| `shortestPath($startId, $endId)` | Compute path |
| `getDistance()` / `getPath()` / `getPathNames()` | Accessors |
| `getPathDetails()` | Rich payload for UI/API |
| `getGraph()` | Inspect adjacency list |

AJAX entry point: `ajax/dijkstra.php` (`action=shortest_path|compute|graph`).

---

## 7. Security Practices

| Control | Implementation |
|---------|----------------|
| Password storage | `password_hash()` / `password_verify()` (bcrypt) |
| SQL injection | MySQLi prepared statements everywhere |
| XSS | `e()` on output; `clean()` on input |
| CSRF | Token in session; `csrfField()` / `requireCsrf()` |
| AuthZ | Role checks (`requireRole`, `in_array` on AJAX) |
| Session | Named session, idle timeout constants |
| Direct script access | `.htaccess` denies `config/`, `includes/`, `classes/`, `models/`, `controllers/`, `logs/` |
| Sensitive files | `.sql` blocked from web download |
| Directory listing | `Options -Indexes` |
| Login abuse | `MAX_LOGIN_ATTEMPTS` / lockout constants in `app.php` |
| Uploads | Size and MIME allow-list constants |

**Developer checklist for every new form:**

1. Include `<?= csrfField() ?>`.  
2. On POST handler, call `requireCsrf()`.  
3. Validate and type-cast IDs to `int`.  
4. Never interpolate raw input into SQL.  
5. Escape every dynamic value printed in HTML.  

---

## 8. Frontend Integration Notes

- Bootstrap 5 and Bootstrap Icons are loaded from **CDN** (see layout header). Local `assets/bootstrap/` is a placeholder only.  
- jQuery AJAX calls should send `X-Requested-With: XMLHttpRequest` and include CSRF token for POST (`csrf_token` field or `X-CSRF-TOKEN` header).  
- Tracking refresh interval is controlled by `TRACKING_REFRESH_INTERVAL` (seconds).  
- Map / path UI scripts: `assets/js/dijkstra-map.js`, `assets/js/tracking.js`, `assets/js/app.js`.  

---

## 9. Extending Route Optimisation

Possible academic extensions:

1. Weight edges by **travel time** instead of (or in addition to) distance.  
2. Temporarily remove `blocked` roads and recompute paths.  
3. Multi-criteria cost (distance + congestion factor).  
4. Visualise the full graph from `ajax/dijkstra.php?action=graph`.  
5. Cache the adjacency list in memory/APCu for large campuses.  

Keep the algorithm class pure; feed it graphs from models so unit tests can stay database-free (see `tests/DijkstraTest.php`).

---

## 10. Environment & Debugging

| Setting | File | Notes |
|---------|------|-------|
| `APP_DEBUG` | `config/app.php` | Shows detailed DB errors when true |
| `APP_URL` | `config/app.php` | Must match deployment URL |
| `MAIL_ENABLED` | `config/app.php` | Email currently off by default |
| Error log | Apache / PHP log | Also use `error_log()` messages |

---

## 11. Related Documents

- [API Documentation](API_DOCUMENTATION.md)  
- [Database Documentation](DATABASE_DOCUMENTATION.md)  
- [Testing Documentation](TESTING_DOCUMENTATION.md)  
- [Installation Guide](INSTALLATION_GUIDE.md)  
