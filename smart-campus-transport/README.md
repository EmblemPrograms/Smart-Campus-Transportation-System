# Smart Campus Transportation System

**Institution:** Federal Polytechnic Ede  
**Project Title:** Design and Implementation of a Web-Based Smart Campus Transportation System with Booking and Route Optimization for Federal Polytechnic Ede using the Agile Development Model and Dijkstra's Algorithm  
**Level:** Higher National Diploma (HND) Final-Year Project  
**Version:** 1.0.0  

---

## Overview

The Smart Campus Transportation System (SCTS) is a web-based application designed to digitise campus shuttle booking, fleet management, and route optimisation for Federal Polytechnic Ede. Students and staff can reserve seats on scheduled campus transport services; administrators manage vehicles, drivers, routes, schedules, and bookings; drivers manage assigned trips and update live locations. Optimal paths between campus locations are computed using **Dijkstra's shortest-path algorithm**.

The system was developed using the **Agile development model** across five iterative sprints (see [docs/AGILE_SPRINTS.md](docs/AGILE_SPRINTS.md)).

---

## Key Features

| Feature | Description |
|---------|-------------|
| Role-based access | Admin, Student, Staff, and Driver portals with distinct dashboards |
| Seat booking | Book trips by schedule, date, pickup/destination, and seat selection |
| Priority booking | Staff users receive priority booking privileges |
| Route optimisation | Dijkstra-based shortest path between campus locations |
| Live trip tracking | Drivers update GPS/location; passengers and admin can track trips |
| Fleet management | Vehicles, drivers, locations, roads, routes, and schedules CRUD |
| Notifications | In-app alerts for booking status and trip assignments |
| Reports & logs | Booking summaries, vehicle utilisation, activity audit trail |
| Security | Password hashing (bcrypt), CSRF tokens, prepared statements, XSS escaping |

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| Backend | Core PHP 8 (strict types), MySQLi prepared statements |
| Database | MySQL 8 / MariaDB (InnoDB, utf8mb4) |
| Frontend | Bootstrap 5 (CDN), jQuery, AJAX, custom CSS/JS |
| Architecture | MVC-like (config, models, controllers, views, classes) |
| Algorithm | Dijkstra's algorithm (`classes/Dijkstra.php`) |
| Local stack | XAMPP (Apache + MySQL + PHP) |
| Charts | Chart.js (admin dashboard via AJAX stats) |

---

## Quick Start

1. Install **XAMPP** and start **Apache** and **MySQL**.
2. Copy this project folder into `C:\xampp\htdocs\smart-campus-transport`.
3. Confirm credentials in `config/database.php` (default: `root` / empty password).
4. Confirm `APP_URL` in `config/app.php`:
   ```php
   define('APP_URL', 'http://localhost/smart-campus-transport');
   ```
5. Install the database (recommended):
   ```bash
   php database/install.php
   ```
   Or import `database/schema.sql` via phpMyAdmin, then run:
   ```bash
   php database/seed_passwords.php
   ```
6. Open: [http://localhost/smart-campus-transport](http://localhost/smart-campus-transport)

Full instructions: [docs/INSTALLATION_GUIDE.md](docs/INSTALLATION_GUIDE.md).

---

## Default Credentials (after install)

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@fedpolyede.edu.ng` | `Admin@123` |
| Student | `student1@fedpolyede.edu.ng` | `Student@123` |
| Staff | `staff1@fedpolyede.edu.ng` | `Staff@123` |
| Driver | `driver1@fedpolyede.edu.ng` | `Driver@123` |

> Change these passwords after first login in a production or demonstration environment.

---

## Folder Structure

```
smart-campus-transport/
├── ajax/                 # AJAX JSON API endpoints
├── assets/
│   ├── bootstrap/        # Placeholder (Bootstrap loaded via CDN)
│   ├── css/
│   └── js/
├── classes/              # Core algorithms (Dijkstra)
├── config/               # app.php, database.php
├── controllers/          # Request handling / business orchestration
├── database/             # schema.sql, install.php, seed_passwords.php
├── docs/                 # Project documentation
├── includes/             # Auth, CSRF, session, helpers
├── logs/                 # Runtime logs (.gitkeep)
├── models/               # Data access layer
├── reports/              # Generated report files (.gitkeep)
├── tests/                # CLI unit / integration tests
├── uploads/              # User uploads (.gitkeep)
├── views/                # Role-based UI templates
├── .htaccess             # Apache security & rewrite rules
├── index.php             # Landing page
└── README.md
```

---

## Agile Sprints (Summary)

| Sprint | Focus |
|--------|--------|
| Sprint 1 | Requirements, database design, authentication, base layout |
| Sprint 2 | Admin CRUD (users, vehicles, locations, roads, routes, schedules) |
| Sprint 3 | Booking workflow, seats, tickets, notifications |
| Sprint 4 | Dijkstra route optimisation, maps, path preview |
| Sprint 5 | Live tracking, reports, testing, documentation, hardening |

Details: [docs/AGILE_SPRINTS.md](docs/AGILE_SPRINTS.md).

---

## Documentation Index

| Document | Description |
|----------|-------------|
| [Installation Guide](docs/INSTALLATION_GUIDE.md) | XAMPP setup, DB install, virtual host, troubleshooting |
| [System Manual](docs/SYSTEM_MANUAL.md) | End-user guide for all roles |
| [Developer Documentation](docs/DEVELOPER_DOCUMENTATION.md) | Architecture, standards, extending the system |
| [Database Documentation](docs/DATABASE_DOCUMENTATION.md) | ER overview, 18 tables, indexes, sample data |
| [API Documentation](docs/API_DOCUMENTATION.md) | AJAX endpoints (methods, params, responses) |
| [Testing Documentation](docs/TESTING_DOCUMENTATION.md) | Unit, integration, system, and UAT cases |
| [Agile Sprints](docs/AGILE_SPRINTS.md) | Full sprint backlog and deliverables |

---

## Running Tests

```bash
php tests/DijkstraTest.php
php tests/SecurityTest.php
php tests/BookingFlowTest.php
```

---

## Screenshots

> Place project screenshots in `docs/screenshots/` and reference them below for the final bound report / soft copy.

| # | Screen | Placeholder |
|---|--------|-------------|
| 1 | Landing / Home | `![Landing](docs/screenshots/01-landing.png)` |
| 2 | Login | `![Login](docs/screenshots/02-login.png)` |
| 3 | Admin Dashboard | `![Admin Dashboard](docs/screenshots/03-admin-dashboard.png)` |
| 4 | Booking Form | `![Booking](docs/screenshots/04-booking.png)` |
| 5 | Route Optimisation (Dijkstra) | `![Dijkstra](docs/screenshots/05-dijkstra.png)` |
| 6 | Live Tracking | `![Tracking](docs/screenshots/06-tracking.png)` |
| 7 | Driver Trip View | `![Driver](docs/screenshots/07-driver.png)` |
| 8 | Ticket / Booking Detail | `![Ticket](docs/screenshots/08-ticket.png)` |

---

## License

**Academic Use License**

Copyright © 2026 — Smart Campus Transportation System, Federal Polytechnic Ede.

This software is provided solely for academic, educational, and research purposes as an HND final-year project. Redistribution, commercial use, or deployment outside the institution without explicit permission from the project author(s) and supervisor is not permitted. Sample data and credentials are for demonstration only.

---

## Author / Project Context

- **Institution:** Federal Polytechnic Ede  
- **Methodology:** Agile Development Model  
- **Core Algorithm:** Dijkstra's Shortest Path Algorithm  
- **Runtime:** PHP 8 + MySQL on XAMPP  
