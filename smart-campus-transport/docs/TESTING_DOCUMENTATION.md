# Testing Documentation

**Smart Campus Transportation System**  
Federal Polytechnic Ede — HND Final-Year Project  

This document defines unit, integration, system, and user-acceptance (UAT) test cases with expected results, plus a role-based test matrix. Automated CLI helpers live under `tests/`.

---

## 1. Testing Objectives

1. Verify Dijkstra shortest-path correctness on controlled graphs.  
2. Verify security primitives (password hashing, input cleaning, CSRF token format).  
3. Confirm core models/controllers are present for booking flows.  
4. Validate end-to-end behaviour for Admin, Student, Staff, and Driver roles.  
5. Provide auditable evidence suitable for project viva / documentation chapter.  

---

## 2. Test Environment

| Item | Value |
|------|-------|
| OS | Windows 10/11 |
| Stack | XAMPP (Apache, MySQL, PHP 8+) |
| APP_URL | `http://localhost/smart-campus-transport` |
| Database | `smart_campus_transport` (seeded) |
| Browsers | Chrome / Edge |

---

## 3. Automated CLI Tests

```bash
php tests/DijkstraTest.php
php tests/SecurityTest.php
php tests/BookingFlowTest.php
```

| Script | Type | DB required? | Purpose |
|--------|------|--------------|---------|
| `DijkstraTest.php` | Unit | No | Hardcoded graph + `addEdge` assertions |
| `SecurityTest.php` | Unit | No | Hashing, cleaning, CSRF-like token helpers |
| `BookingFlowTest.php` | Integration (light) | Optional | Method existence on Booking/User if bootstrap works |

---

## 4. Unit Test Cases

### UT-01 — Dijkstra same start/end

| Field | Detail |
|-------|--------|
| Steps | Build graph; call `shortestPath(1, 1)` |
| Expected | `success=true`, path `[1]`, distance `0` |

### UT-02 — Dijkstra shortest of two alternatives

| Field | Detail |
|-------|--------|
| Setup | Edges 1→2 (450), 2→3 (300), 1→3 (900) |
| Steps | `shortestPath(1, 3)` |
| Expected | Path `[1,2,3]`, distance `750` (not direct 900) |

### UT-03 — Dijkstra unreachable node

| Field | Detail |
|-------|--------|
| Setup | Disconnected node |
| Expected | `success=false`, empty path |

### UT-04 — Password hashing

| Field | Detail |
|-------|--------|
| Steps | `password_hash('Student@123')` then `password_verify` |
| Expected | Verify true; hash ≠ plain text |

### UT-05 — Input cleaning

| Field | Detail |
|-------|--------|
| Input | `" <b>Hello</b> "` via `clean()` (or equivalent strip_tags/trim) |
| Expected | `"Hello"` |

### UT-06 — CSRF token format

| Field | Detail |
|-------|--------|
| Steps | Generate 32-byte hex token (64 hex chars) |
| Expected | Matches `/^[a-f0-9]{64}$/i` and differs across calls |

---

## 5. Integration Test Cases

### IT-01 — Database connection

| Steps | Expected |
|-------|----------|
| Load `config/app.php` / `getDB()` | mysqli connection succeeds |

### IT-02 — Booking model API surface

| Steps | Expected |
|-------|----------|
| Reflect `Booking` class methods | `create`, `approve`, `cancel`, `complete`, `findById`, `getByUser` exist |

### IT-03 — User model API surface

| Steps | Expected |
|-------|----------|
| Reflect `User` class methods | `findByEmail`, `create`, `changePassword`, `updateStatus` exist |

### IT-04 — Create booking then approve

| Steps | Expected |
|-------|----------|
| Student creates booking via UI/AJAX; admin approves | Status pending → approved; notification created |

### IT-05 — Seat conflict

| Steps | Expected |
|-------|----------|
| Attempt to book an already taken seat | Operation rejected / seat unavailable |

### IT-06 — Dijkstra AJAX with DB graph

| Steps | Expected |
|-------|----------|
| Call `ajax/dijkstra.php` start=Main Gate, end=Engineering | Path returned with positive distance |

### IT-07 — CSRF rejection

| Steps | Expected |
|-------|----------|
| POST booking-actions without token | HTTP 403 / invalid security token |

---

## 6. System Test Cases

### ST-01 — Fresh install

| Steps | Expected |
|-------|----------|
| Run `php database/install.php`; open APP_URL | Landing page loads; login works with seeded credentials |

### ST-02 — Role dashboards

| Steps | Expected |
|-------|----------|
| Login as each role | Redirected to correct dashboard; unauthorised modules blocked |

### ST-03 — Full booking lifecycle

| Steps | Expected |
|-------|----------|
| Book → approve → driver trip → complete booking | Statuses update; seats/tracking consistent |

### ST-04 — Priority staff booking

| Steps | Expected |
|-------|----------|
| Staff books with priority enabled | `is_priority` flagged; visible to admin/driver |

### ST-05 — Road block impact

| Steps | Expected |
|-------|----------|
| Mark a road blocked; recompute path | Alternate route or failure if disconnected |

### ST-06 — Reports & logs

| Steps | Expected |
|-------|----------|
| Generate report; perform login/booking | Report row / activity_log entries present |

### ST-07 — Security headers / folder protection

| Steps | Expected |
|-------|----------|
| Request `/config/database.php` directly | Denied by `.htaccess` (403/404) |

---

## 7. UAT Test Cases (End Users)

| ID | Role | Scenario | Expected Result | Pass? |
|----|------|----------|-----------------|-------|
| UAT-01 | Student | Register/login and book a weekday schedule | Booking created with number | ☐ |
| UAT-02 | Student | View ticket for approved booking | Ticket shows seat, date, route | ☐ |
| UAT-03 | Student | Cancel pending booking | Status cancelled; seat freed | ☐ |
| UAT-04 | Staff | Create priority booking | Booking marked priority | ☐ |
| UAT-05 | Admin | Approve pending booking | Student notified; status approved | ☐ |
| UAT-06 | Admin | Add vehicle and assign driver | Vehicle list updated | ☐ |
| UAT-07 | Admin | Create location and road | Appears in Dijkstra graph | ☐ |
| UAT-08 | Admin | View dashboard charts | Charts render with data | ☐ |
| UAT-09 | Driver | Open assigned trip and start boarding | Trip status boarding | ☐ |
| UAT-10 | Driver | Update location while in progress | Tracking page shows new coords | ☐ |
| UAT-11 | Driver | View passenger list | Approved passengers listed | ☐ |
| UAT-12 | All | Logout | Session cleared; login required | ☐ |

---

## 8. Role Test Matrix

| Feature | Admin | Student | Staff | Driver |
|---------|:-----:|:-------:|:-----:|:------:|
| Login / logout | ✓ | ✓ | ✓ | ✓ |
| Dashboard | ✓ | ✓ | ✓ | ✓ |
| Manage students/staff/drivers | ✓ | — | — | — |
| Manage vehicles | ✓ | — | — | — |
| Manage locations/roads/routes | ✓ | — | — | — |
| Manage schedules | ✓ | — | — | — |
| Approve/complete bookings | ✓ | — | — | — |
| Create booking | — | ✓ | ✓ | — |
| Priority booking | — | — | ✓ | — |
| View own bookings / ticket | — | ✓ | ✓ | — |
| Cancel own booking | — | ✓ | ✓ | — |
| View routes | ✓ | ✓ | ✓ | ✓* |
| Dijkstra path preview | ✓ | ✓ | ✓ | ✓ |
| Live tracking view | ✓ | ✓ | ✓ | ✓ |
| Update trip location | ✓ | — | — | ✓ |
| Trip actions / passengers | — | — | — | ✓ |
| Reports | ✓ | — | — | — |
| System settings / logs | ✓ | — | — | — |
| Notifications | ✓ | ✓ | ✓ | ✓ |
| Profile | ✓ | ✓ | ✓ | ✓ |

\* Driver typically uses Navigation / trip route rather than general browsing.

---

## 9. Defect Severity Guide

| Severity | Meaning | Example |
|----------|---------|---------|
| Critical | Blocks core use | Cannot login; DB install fails |
| Major | Feature broken | Booking create fails; Dijkstra wrong path |
| Minor | Workaround exists | UI alignment; non-critical validation message |
| Trivial | Cosmetic | Typo on landing page |

---

## 10. Test Evidence Checklist (Project Submission)

- [ ] Screenshot of CLI DijkstraTest PASS  
- [ ] Screenshot of SecurityTest PASS  
- [ ] Screenshot of successful admin login  
- [ ] Screenshot of student booking + ticket  
- [ ] Screenshot of Dijkstra path result  
- [ ] Screenshot of driver tracking update  
- [ ] Completed UAT checklist signed by tester/supervisor (optional)  

---

## Related Documents

- [API Documentation](API_DOCUMENTATION.md)  
- [System Manual](SYSTEM_MANUAL.md)  
- [Agile Sprints](AGILE_SPRINTS.md)  
