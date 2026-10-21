# Database Documentation

**Smart Campus Transportation System**  
Database name: `smart_campus_transport`  
Engine: InnoDB | Charset: `utf8mb4` / `utf8mb4_unicode_ci`  

Source of truth: `database/schema.sql`

---

## 1. Entity–Relationship Overview

The schema is organised around four themes:

1. **Identity & roles** — `users` plus role extension tables (`admins`, `students`, `staff`, `drivers`).  
2. **Campus graph** — `locations` (nodes) and `roads` (weighted edges); `routes` store named paths.  
3. **Operations** — `vehicles`, `schedules`, `bookings`, `booking_details`, `seats`, `trip_tracking`.  
4. **System services** — `notifications`, `reports`, `activity_logs`, `system_settings`.  

### Core relationships (textual ER)

```
users 1──1 admins | students | staff | drivers   (role-specific profile)
drivers 1──* vehicles (optional current_driver_id)
locations 1──* roads (as from / to)
locations 1──* routes (as start / end)
routes 1──* schedules
vehicles 1──* schedules
drivers 1──* schedules
schedules 1──* bookings
users 1──* bookings
bookings 1──1..* booking_details
schedules 1──* seats (per trip_date)
bookings 1──* seats (when occupied)
schedules / vehicles / drivers ──* trip_tracking
users 1──* notifications | reports | activity_logs
```

---

## 2. Tables Reference (18)

### 2.1 `users`

Central authentication and account table.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK AI | |
| full_name | VARCHAR(150) | |
| email | VARCHAR(191) UNIQUE | Login identity |
| phone | VARCHAR(30) | Indexed |
| password | VARCHAR(255) | bcrypt hash |
| role | ENUM(admin, student, staff, driver) | Indexed |
| status | ENUM(active, inactive, suspended) | Indexed |
| email_verified_at | DATETIME | |
| verification_token | VARCHAR(100) | |
| reset_token | VARCHAR(100) | |
| reset_token_expires | DATETIME | |
| remember_token | VARCHAR(100) | |
| avatar | VARCHAR(255) | |
| last_login | DATETIME | |
| created_at / updated_at | DATETIME | |

### 2.2 `admins`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| user_id | FK → users.id UNIQUE | CASCADE |
| employee_id | VARCHAR(50) UNIQUE | |
| department | VARCHAR(100) | |
| position | VARCHAR(100) | |
| created_at | DATETIME | |

### 2.3 `students`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| user_id | FK → users.id UNIQUE | CASCADE |
| matric_number | VARCHAR(50) UNIQUE | |
| department | VARCHAR(100) | Indexed |
| level | VARCHAR(20) | e.g. ND II, HND I |
| faculty | VARCHAR(100) | Indexed |
| created_at | DATETIME | |

### 2.4 `staff`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| user_id | FK → users.id UNIQUE | CASCADE |
| staff_id | VARCHAR(50) UNIQUE | |
| department | VARCHAR(100) | Indexed |
| position | VARCHAR(100) | |
| priority_booking | TINYINT(1) DEFAULT 1 | |
| created_at | DATETIME | |

### 2.5 `drivers`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| user_id | FK → users.id UNIQUE | CASCADE |
| license_number | VARCHAR(50) UNIQUE | |
| license_expiry | DATE | |
| experience_years | INT UNSIGNED | |
| status | ENUM(available, on_trip, off_duty) | Indexed |
| created_at | DATETIME | |

### 2.6 `vehicles`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| plate_number | VARCHAR(30) UNIQUE | |
| model / make | VARCHAR | |
| capacity | INT UNSIGNED | Seat capacity |
| year | YEAR | |
| color | VARCHAR(40) | |
| status | ENUM(available, in_use, maintenance, unavailable) | Indexed |
| current_driver_id | FK → drivers.id NULL | SET NULL on delete |
| created_at / updated_at | DATETIME | |

### 2.7 `locations`

Campus graph **nodes**.

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| name | VARCHAR(150) | Indexed |
| code | VARCHAR(30) UNIQUE | e.g. GATE, SICT |
| description | TEXT | |
| latitude / longitude | DECIMAL(10,7) | |
| location_type | ENUM(gate, academic, admin, hostel, facility, religious, other) | |
| status | ENUM(active, inactive) | |
| created_at | DATETIME | |

### 2.8 `roads`

Campus graph **edges**.

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| from_location_id | FK → locations | RESTRICT |
| to_location_id | FK → locations | RESTRICT |
| distance_metres | DECIMAL(10,2) | CHECK > 0 |
| travel_time_minutes | INT UNSIGNED | |
| is_bidirectional | TINYINT(1) DEFAULT 1 | |
| status | ENUM(active, inactive, blocked) | |
| created_at | DATETIME | |

**Constraints:** unique `(from_location_id, to_location_id)`; from ≠ to.

### 2.9 `routes`

Named shuttle routes with stored path JSON.

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| name / code | code UNIQUE | |
| start_location_id / end_location_id | FK → locations | |
| path_json | JSON | Ordered location IDs |
| total_distance | DECIMAL(10,2) | metres |
| estimated_time | INT UNSIGNED | minutes |
| description | TEXT | |
| status | ENUM(active, inactive) | |
| created_at / updated_at | DATETIME | |

### 2.10 `schedules`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| route_id | FK → routes | |
| vehicle_id | FK → vehicles | |
| driver_id | FK → drivers | |
| departure_time / arrival_time | TIME | |
| days_of_week | VARCHAR(50) | e.g. Mon,Tue,Wed,Thu,Fri |
| available_seats | INT UNSIGNED | |
| status | ENUM(active, inactive, cancelled) | |
| created_at / updated_at | DATETIME | |

### 2.11 `bookings`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| booking_number | VARCHAR(40) UNIQUE | e.g. BK-2026-0001 |
| user_id | FK → users | |
| schedule_id | FK → schedules | |
| pickup_location_id | FK → locations | |
| destination_location_id | FK → locations | |
| booking_date | DATE | Indexed |
| seat_number | VARCHAR(10) | |
| is_priority | TINYINT(1) | Staff priority |
| status | ENUM(pending, approved, completed, cancelled) | |
| notes | TEXT | |
| cancelled_at | DATETIME | |
| cancelled_by | FK → users NULL | |
| created_at / updated_at | DATETIME | |

### 2.12 `booking_details`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| booking_id | FK → bookings CASCADE | |
| passenger_name | VARCHAR(150) | |
| passenger_phone | VARCHAR(30) | |
| special_request | TEXT | |
| created_at | DATETIME | |

### 2.13 `notifications`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| user_id | FK → users CASCADE | |
| title / message | | |
| type | ENUM(info, success, warning, danger) | |
| link | VARCHAR(255) | Optional deep link |
| is_read | TINYINT(1) | Indexed |
| created_at | DATETIME | Indexed |

### 2.14 `trip_tracking`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| schedule_id / vehicle_id / driver_id | FKs | |
| current_location_id | FK → locations NULL | |
| latitude / longitude | DECIMAL(10,7) | |
| trip_date | DATE | |
| status | ENUM(scheduled, boarding, in_progress, completed, cancelled) | |
| started_at / ended_at | DATETIME | |
| notes | TEXT | |
| updated_at / created_at | DATETIME | |

### 2.15 `reports`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| report_type | VARCHAR(80) | Indexed |
| title | VARCHAR(200) | |
| parameters_json | JSON | Filters used |
| generated_by | FK → users | |
| file_path | VARCHAR(255) | Optional stored file |
| created_at | DATETIME | |

### 2.16 `activity_logs`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| user_id | FK → users NULL | SET NULL on delete |
| action | VARCHAR(100) | Indexed |
| description | TEXT | |
| module | VARCHAR(80) | Indexed |
| ip_address | VARCHAR(45) | |
| user_agent | VARCHAR(255) | |
| created_at | DATETIME | Indexed |

### 2.17 `system_settings`

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| setting_key | VARCHAR(100) UNIQUE | |
| setting_value | TEXT | |
| description | VARCHAR(255) | |
| updated_at | DATETIME | |

Sample keys: `app_name`, `booking_window_hours`, `priority_booking_enabled`, `max_seats_per_booking`, `timezone`, `maintenance_mode`, …

### 2.18 `seats`

Per schedule + trip date seat inventory.

| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| schedule_id | FK → schedules CASCADE | |
| seat_number | VARCHAR(10) | |
| is_available | TINYINT(1) | |
| booking_id | FK → bookings NULL | SET NULL on delete |
| trip_date | DATE | |
| created_at | DATETIME | |

**Unique:** `(schedule_id, seat_number, trip_date)`.

---

## 3. Indexes Summary

Beyond primary and unique keys, notable secondary indexes exist on:

- Role/status filters (`users.role`, `users.status`, `drivers.status`, `vehicles.status`)  
- Graph lookups (`roads.to_location_id`, `locations.location_type`)  
- Operational queries (`bookings.booking_date`, `bookings.status`, `schedules.departure_time`)  
- Notification feeds (`notifications.user_id`, `is_read`, `created_at`)  
- Audit searches (`activity_logs.action`, `module`, `created_at`)  

These support dashboard counts, booking lists, and Dijkstra graph loads efficiently for a campus-scale dataset.

---

## 4. Sample Data Notes

After `install.php` / `schema.sql` + `seed_passwords.php`:

| Entity | Sample content |
|--------|----------------|
| Users | 1 admin, 2 students, 1 staff, 2 drivers |
| Vehicles | 3 campus vehicles (one in maintenance) |
| Locations | 17 Federal Polytechnic Ede campus points |
| Roads | Bidirectional weighted campus network |
| Routes | 3 named routes with `path_json` |
| Schedules | 4 weekday schedules |
| Bookings | Mixed pending / approved / cancelled examples |
| Seats | Inventory for selected schedules/dates |
| Settings | Institution name, booking window, priority flag |

**Password note:** Schema placeholders use a generic bcrypt hash. Always run `database/install.php` or `database/seed_passwords.php` so passwords become `Admin@123`, `Student@123`, `Staff@123`, and `Driver@123`.

Coordinates are approximate demonstration values for mapping/path visualisation, not surveyed GIS data.

---

## 5. Installation Reminder

```bash
php database/install.php
# or
# Import schema.sql in phpMyAdmin, then:
php database/seed_passwords.php
```

See [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md).
