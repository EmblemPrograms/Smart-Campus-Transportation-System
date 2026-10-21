# System Manual (End-User Guide)

**Smart Campus Transportation System**  
Federal Polytechnic Ede  

This manual describes how each user role interacts with the system. Screens may vary slightly by browser; functionality remains the same.

---

## 1. Getting Started

### 1.1 Access the System

1. Open a web browser.
2. Navigate to `http://localhost/smart-campus-transport` (or your configured URL).
3. From the landing page, choose **Login** or **Register** (student self-registration where enabled).

### 1.2 Login

1. Enter your institutional email and password.
2. Optionally enable **Remember me** for trusted devices.
3. Click **Sign in**. You are redirected to the dashboard for your role.

### 1.3 Forgot Password

1. On the login page, click **Forgot password**.
2. Enter your registered email.
3. Follow the reset instructions (token-based reset pages are provided under `views/auth/`).

### 1.4 Logout

Use **Logout** from the navigation menu. Always log out on shared computers.

---

## 2. Administrator Guide

**Typical account:** `admin@fedpolyede.edu.ng`

Administrators oversee the entire transport ecosystem: users, fleet, campus graph, schedules, bookings, tracking, reports, and settings.

### 2.1 Dashboard

After login you see summary cards and charts:

- Total students, staff, drivers, vehicles  
- Today’s bookings and active trips  
- Monthly booking trends, popular routes, vehicle utilisation (loaded via AJAX)

### 2.2 Manage Students

**Path:** Admin → Students  

- View, search, and filter student accounts  
- Create or update student profiles (matric number, department, level, faculty)  
- Activate, suspend, or deactivate accounts  

### 2.3 Manage Staff

**Path:** Admin → Staff  

- Maintain staff records (staff ID, department, position)  
- Toggle **priority booking** for eligible staff  

### 2.4 Manage Drivers

**Path:** Admin → Drivers  

- Register drivers with licence number and expiry  
- Set driver status: available / on trip / off duty  
- Link drivers to vehicles via the Vehicles module  

### 2.5 Vehicles

**Path:** Admin → Vehicles  

- Add campus shuttles (plate, make, model, capacity, year, colour)  
- Set status: available, in use, maintenance, unavailable  
- Assign a current driver when appropriate  

### 2.6 Locations

**Path:** Admin → Locations  

- Maintain campus stops (Main Gate, Schools, Hostels, Library, etc.)  
- Store GPS coordinates and location type (gate, academic, hostel, …)  
- Activate or deactivate locations used by Dijkstra and bookings  

### 2.7 Roads (Campus Graph Edges)

**Path:** Admin → Roads  

- Define weighted connections between locations (distance in metres, travel time)  
- Mark roads as bidirectional when travel is allowed both ways  
- Block or deactivate roads that should not be used in path finding  

### 2.8 Routes

**Path:** Admin → Routes  

- Define named shuttle routes with start/end locations  
- Store the ordered path (`path_json`), total distance, and estimated time  
- Activate routes for schedule assignment  

### 2.9 Schedules

**Path:** Admin → Schedules  

- Assign route + vehicle + driver  
- Set departure/arrival times and days of week  
- Control available seats and schedule status  

### 2.10 Bookings

**Path:** Admin → Bookings  

- Review pending bookings  
- **Approve**, **Cancel**, or **Complete** bookings (AJAX actions)  
- Users receive notifications when status changes  

### 2.11 Live Tracking

**Path:** Admin → Tracking  

- View active trips and last known positions  
- Monitor boarding / in-progress / completed statuses  

### 2.12 Reports

**Path:** Admin → Reports  

- Generate summaries (bookings, vehicle utilisation, etc.)  
- Export or store report metadata for project demonstration  

### 2.13 Notifications, Settings, Logs, Profile

- **Notifications:** System and operational alerts  
- **Settings:** Application keys (booking window, priority booking, maintenance mode, …)  
- **Logs:** Activity audit trail (login, booking actions, …)  
- **Profile:** Update admin profile and password  

---

## 3. Student Guide

**Typical account:** `student1@fedpolyede.edu.ng` / `Student@123`

### 3.1 Student Dashboard

Shows upcoming bookings, notifications, and quick links to book a trip or view routes.

### 3.2 Book a Trip

**Path:** Student → Book  

1. Select **booking date**.  
2. Choose **pickup** and **destination** locations.  
3. Optionally preview the **shortest path** (Dijkstra) between the two points.  
4. Select an available **schedule**.  
5. Choose an available **seat**.  
6. Confirm booking. Status starts as **pending** (or as configured by admin workflow).  

### 3.3 My Bookings

**Path:** Student → Bookings  

- List all bookings with status badges (pending, approved, completed, cancelled)  
- Cancel a booking you no longer need (subject to rules)  
- Open a booking to view ticket details  

### 3.4 Ticket

**Path:** Student → Ticket (from a booking)  

Displays booking number, route/schedule info, seat, date, and passenger details for boarding.

### 3.5 Routes & Tracking

- **Routes:** Browse active campus shuttle routes.  
- **Track:** View live/active trip status for relevant schedules.  

### 3.6 Notifications & Profile

- Read booking approvals and system messages.  
- Update profile information and password.  

---

## 4. Staff Guide

**Typical account:** `staff1@fedpolyede.edu.ng` / `Staff@123`

Staff workflows mirror students with one important difference: **priority booking**.

### 4.1 Priority Booking

When priority booking is enabled in system settings, staff bookings may be flagged `is_priority = 1`, improving boarding preference and visibility to administrators and drivers.

### 4.2 Feature Walkthrough

| Feature | How to use |
|---------|------------|
| Dashboard | Overview of personal bookings and alerts |
| Book | Same booking flow as students; priority applied automatically where enabled |
| Bookings | View / cancel personal bookings |
| Ticket | Printable/viewable boarding ticket |
| Routes | View campus routes |
| Track | Follow active trips |
| Notifications | Status updates |
| Profile | Personal details and password |

---

## 5. Driver Guide

**Typical account:** `driver1@fedpolyede.edu.ng` / `Driver@123`

### 5.1 Driver Dashboard

Shows assigned trips for the day, current duty status, and notifications (e.g. trip assigned).

### 5.2 Trips

**Path:** Driver → Trips  

- View schedules assigned to you  
- Open trip actions to start boarding, begin trip, or complete trip  

### 5.3 Trip Actions

**Path:** Driver → Trip Action  

Update trip lifecycle status:

- Scheduled → Boarding → In progress → Completed (or Cancelled)

### 5.4 Passengers

**Path:** Driver → Passengers  

- View passenger list / approved bookings for the selected trip  
- Confirm seat numbers and priority passengers  

### 5.5 Navigation

**Path:** Driver → Navigation  

- View route path and location guidance for the assigned trip  
- Supports campus navigation based on stored route path / Dijkstra context  

### 5.6 Location Updates

Drivers (and admins) can push location updates via the tracking AJAX endpoint (latitude, longitude, optional location ID). Passengers and admins then see refreshed positions on the tracking views.

### 5.7 Notifications & Profile

- Receive assignment and operational alerts.  
- Keep licence-related profile data current via admin coordination; update login password from Profile.  

---

## 6. Common Status Meanings

### Booking Status

| Status | Meaning |
|--------|---------|
| pending | Awaiting admin approval |
| approved | Confirmed for travel |
| completed | Trip taken / closed |
| cancelled | Booking no longer valid |

### Trip Tracking Status

| Status | Meaning |
|--------|---------|
| scheduled | Trip planned |
| boarding | Passengers boarding |
| in_progress | Vehicle en route |
| completed | Trip finished |
| cancelled | Trip aborted |

---

## 7. Best Practices for Users

1. Book early within the configured booking window.  
2. Arrive at the pickup location a few minutes before departure.  
3. Cancel unused bookings so seats return to the pool.  
4. Keep your email and phone number accurate for notifications.  
5. Report vehicle or schedule issues to the Transport / ICT administrator.  

---

## 8. Support

- **Support email (sample setting):** `transport@fedpolyede.edu.ng`  
- For installation issues, see [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md).  
- For technical architecture, see [DEVELOPER_DOCUMENTATION.md](DEVELOPER_DOCUMENTATION.md).  
