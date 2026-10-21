# Agile Sprint Documentation

**Project:** Design and Implementation of a Web-Based Smart Campus Transportation System with Booking and Route Optimization for Federal Polytechnic Ede using the Agile Development Model and Dijkstra's Algorithm  

**Methodology:** Agile (iterative sprints with incremental deliverables)  
**Number of sprints:** 5  
**Institution:** Federal Polytechnic Ede  

This document records sprint objectives, user stories, tasks, and deliverables as required for the HND project write-up.

---

## Agile Approach Summary

Development proceeded in short iterations. Each sprint produced a demonstrable increment, reviewed against acceptance criteria before proceeding. Continuous refactoring of models, security helpers, and UI layouts was applied between sprints. Dijkstra optimisation was introduced once the campus graph (locations/roads) existed, and tracking/reporting closed the operational loop.

---

## Sprint 1 — Foundations, Authentication & Core Setup

### Objectives

- Elicit and refine functional/non-functional requirements.  
- Establish XAMPP project structure (MVC-like folders).  
- Design and implement the MySQL schema foundation.  
- Deliver secure authentication and role-based session handling.  
- Provide a branded landing page and shared layouts.  

### User Stories

| ID | As a… | I want to… | So that… |
|----|-------|------------|----------|
| US-1.1 | Stakeholder | See a clear project landing page | Visitors understand the system purpose |
| US-1.2 | User | Register / log in with email and password | I can access my role portal securely |
| US-1.3 | User | Reset a forgotten password | I can recover access |
| US-1.4 | Admin | Have a dedicated admin account seeded | System setup can be demonstrated |
| US-1.5 | Developer | Use a consistent config and autoload | Modules can be added quickly |

### Tasks

1. Create folder structure: `config`, `includes`, `models`, `controllers`, `views`, `ajax`, `assets`, `database`.  
2. Implement `config/app.php` and `config/database.php`.  
3. Implement helpers: `e()`, `clean()`, CSRF, session, auth guards.  
4. Draft initial ERD and begin `schema.sql` (users + role tables).  
5. Build auth views: login, register, forgot/reset password, logout.  
6. Implement `AuthController` and `User` model with password hashing.  
7. Design Bootstrap 5 layouts (header, navbar, sidebar, footer).  
8. Seed demo users for all four roles (passwords finalised via installer).  

### Deliverables

- Running project skeleton under XAMPP.  
- Working login/logout with role redirect.  
- Base database tables for identity.  
- Landing page (`index.php`).  
- Sprint 1 review notes / backlog update.  

---

## Sprint 2 — Administration & Campus Resources

### Objectives

- Complete CRUD for organisational and fleet entities.  
- Model the campus as locations and roads (graph data).  
- Define routes and schedules linking vehicles and drivers.  
- Provide admin dashboards with navigation to all modules.  

### User Stories

| ID | As a… | I want to… | So that… |
|----|-------|------------|----------|
| US-2.1 | Admin | Manage students, staff, and drivers | User records stay accurate |
| US-2.2 | Admin | Manage vehicles and assignments | Fleet capacity is known |
| US-2.3 | Admin | Maintain campus locations and roads | The transport network is digitised |
| US-2.4 | Admin | Define routes and schedules | Shuttles run on planned times |
| US-2.5 | Admin | View a management dashboard | I can monitor system health |

### Tasks

1. Expand schema: vehicles, locations, roads, routes, schedules, settings, activity logs.  
2. Implement models: `Student`, `Staff`, `Driver`, `Vehicle`, `Location`, `Road`, `Route`, `Schedule`, `Setting`, `ActivityLog`.  
3. Build admin views for each CRUD module.  
4. Wire `AdminController` actions and sidebar links.  
5. Insert Federal Polytechnic Ede sample locations and road network.  
6. Implement activity logging for admin mutations.  
7. Apply role middleware so only admins reach management pages.  

### Deliverables

- Admin modules for users, fleet, graph, routes, schedules.  
- Seeded campus map data.  
- Admin dashboard shell.  
- Updated schema with foreign keys and indexes.  

---

## Sprint 3 — Booking, Seats, Tickets & Notifications

### Objectives

- Enable students and staff to book seats on schedules.  
- Support priority booking for staff.  
- Allow admin approval workflow.  
- Issue ticket views and in-app notifications.  

### User Stories

| ID | As a… | I want to… | So that… |
|----|-------|------------|----------|
| US-3.1 | Student | Book a seat on a schedule | I can reserve campus transport |
| US-3.2 | Staff | Book with priority | I receive preferential boarding treatment |
| US-3.3 | Student/Staff | View and cancel my bookings | I can manage my trips |
| US-3.4 | Student/Staff | View a ticket | I can board with proof of booking |
| US-3.5 | Admin | Approve / cancel / complete bookings | Capacity and compliance are controlled |
| US-3.6 | User | Receive notifications | I know when booking status changes |

### Tasks

1. Add tables: `bookings`, `booking_details`, `seats`, `notifications`.  
2. Implement `Booking` and `Notification` models.  
3. Implement `BookingController` (schedules, seats, create, cancel).  
4. Build student/staff booking UI with AJAX (`ajax/book.php`, `ajax/seats.php`).  
5. Build admin booking actions (`ajax/booking-actions.php`).  
6. Implement ticket generation view.  
7. Enforce CSRF on booking POSTs; validate seat availability.  
8. Hook notifications on create/approve/cancel.  

### Deliverables

- End-to-end booking workflow.  
- Seat availability API.  
- Ticket page.  
- Notification centre / AJAX unread feed.  

---

## Sprint 4 — Dijkstra Route Optimisation & Path UX

### Objectives

- Implement Dijkstra’s algorithm for campus shortest paths.  
- Expose path computation via AJAX and booking path preview.  
- Visualise path results for academic demonstration.  

### User Stories

| ID | As a… | I want to… | So that… |
|----|-------|------------|----------|
| US-4.1 | Passenger | See the shortest path between pickup and destination | I understand the optimal campus route |
| US-4.2 | Admin | Rely on road distances as graph weights | Paths reflect real campus connectivity |
| US-4.3 | Developer | Unit-test Dijkstra without a database | Algorithm correctness is proven |
| US-4.4 | User | Retrieve the campus graph via API | Maps/UI can render nodes and edges |

### Tasks

1. Implement `classes/Dijkstra.php` (`buildGraph`, `addEdge`, `shortestPath`, `getPathDetails`).  
2. Load only `active` locations/roads; honour bidirectional flags.  
3. Create `ajax/dijkstra.php` (`shortest_path`, `graph`).  
4. Integrate path preview into booking forms.  
5. Add frontend script `assets/js/dijkstra-map.js`.  
6. Write `tests/DijkstraTest.php` with hardcoded edges.  
7. Document algorithm usage in developer docs.  

### Deliverables

- Working Dijkstra class and AJAX API.  
- Path preview in booking flow.  
- Passing unit tests for shortest-path cases.  
- Academic explanation artefacts (path, hops, distance).  

---

## Sprint 5 — Tracking, Reports, Hardening, Testing & Documentation

### Objectives

- Deliver live trip tracking for drivers and viewers.  
- Provide admin reports and dashboard statistics.  
- Harden security (`.htaccess`, CSRF coverage, folder protection).  
- Complete testing suite and full academic documentation pack.  
- Prepare deployment support files for XAMPP demonstration.  

### User Stories

| ID | As a… | I want to… | So that… |
|----|-------|------------|----------|
| US-5.1 | Driver | Update my trip status and location | Passengers know where the shuttle is |
| US-5.2 | Passenger | Track active trips | I can plan boarding |
| US-5.3 | Admin | View charts and generate reports | Management decisions are data-driven |
| US-5.4 | Admin | Inspect activity logs | Auditing and accountability are possible |
| US-5.5 | Examiner | Read complete documentation | The project meets HND submission standards |
| US-5.6 | Developer | Run CLI security and booking smoke tests | Regressions are caught quickly |

### Tasks

1. Implement `trip_tracking` model/UI and `ajax/tracking.php`.  
2. Build driver trips, passengers, navigation, trip-action views.  
3. Implement `Report` model, `ReportController`, `ajax/dashboard-stats.php`.  
4. Finalise settings, logs, and profile pages.  
5. Add `.htaccess` protections and placeholder dirs (`uploads`, `logs`, `reports`).  
6. Write Security and BookingFlow CLI tests.  
7. Author README and full `docs/` set (install, manual, developer, DB, API, testing, sprints).  
8. Perform system/UAT matrix; fix critical defects.  
9. Package installer (`database/install.php`, `seed_passwords.php`) for one-command demo setup.  

### Deliverables

- Tracking and reporting modules.  
- Security hardening artefacts.  
- Complete documentation and tests.  
- Stable v1.0.0 demonstration build.  

---

## Sprint Retrospective (Project-Level)

| Theme | Outcome |
|-------|---------|
| What worked well | Incremental MVC-like structure; early auth; seeded FPE campus graph |
| Challenges | Coordinating seat inventory with bookings; CSRF on all AJAX POSTs |
| Improvements applied | Central helpers, autoload, installer for reproducible demos |
| Final increment | Bookable, optimised, trackable campus transport system |

---

## Traceability: Stories → Modules

| Sprint | Primary modules delivered |
|--------|---------------------------|
| 1 | `config/*`, `includes/*`, `views/auth/*`, `User`, `AuthController`, `index.php` |
| 2 | Admin CRUD views/models, campus graph seed data |
| 3 | Booking stack, seats, notifications, tickets |
| 4 | `Dijkstra`, `ajax/dijkstra.php`, path UI/tests |
| 5 | Tracking, reports, `.htaccess`, docs, remaining tests |

---

## Related Documents

- [README.md](../README.md)  
- [Testing Documentation](TESTING_DOCUMENTATION.md)  
- [Developer Documentation](DEVELOPER_DOCUMENTATION.md)  
