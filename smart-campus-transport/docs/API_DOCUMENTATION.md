# API Documentation (AJAX Endpoints)

**Smart Campus Transportation System**  
Base path: `{APP_URL}/ajax/`  
Default APP_URL: `http://localhost/smart-campus-transport`  

All endpoints return JSON (`Content-Type: application/json`). Mutating POST requests require a valid CSRF token (`csrf_token` form field or `X-CSRF-TOKEN` header) unless noted. Authenticated sessions are required except where stated.

Common error shapes:

```json
{ "success": false, "message": "…" }
```

HTTP statuses used: `200`, `400`, `401`, `403`, `404`, `405`, `422`, `500`.

---

## 1. `ajax/book.php`

**Audience:** Student, Staff  
**Auth:** Logged-in student or staff  

| Action | Method | Description |
|--------|--------|-------------|
| `schedules` | GET/POST | List available schedules for a date (optional pickup/destination filters) |
| `seats` | GET/POST | Available seats for a schedule + date |
| `path` | GET/POST | Dijkstra path preview between pickup and destination |
| `create` | POST | Create a booking (CSRF required) |
| `cancel` | POST | Cancel own booking (CSRF required) |

### 1.1 `action=schedules`

**Parameters**

| Name | Type | Required | Notes |
|------|------|----------|-------|
| action | string | yes | `schedules` |
| date | string (Y-m-d) | no | Defaults to today |
| pickup_location_id | int | no | |
| destination_location_id | int | no | |

**Response:** JSON from `BookingController::getAvailableSchedules(...)`.

### 1.2 `action=seats`

**Parameters:** `schedule_id` (int, required), `date` (optional).

**Success response (example):**

```json
{
  "success": true,
  "seats": [ { "seat_number": "A1", "available": true } ],
  "message": "13 seat(s) available."
}
```

### 1.3 `action=path`

**Parameters:** `pickup_location_id`, `destination_location_id`.

**Response:**

```json
{
  "success": true,
  "path": { "success": true, "path": [1, 2, 14], "distance": 1200, "message": "…" }
}
```

### 1.4 `action=create`

**Method:** POST only  
**Parameters (typical):** schedule_id, booking_date, pickup_location_id, destination_location_id, seat_number, notes, passenger fields as accepted by `BookingController::createBooking`, plus `csrf_token`.

**Response:** `{ "success": true|false, "message": "…", … }` with `200` or `422`.

### 1.5 `action=cancel`

**Method:** POST  
**Parameters:** `booking_id`, `csrf_token`.

---

## 2. `ajax/booking-actions.php`

**Audience:** Admin only  
**Auth:** `requireRole('admin')`  
**CSRF:** Always required  
**Method:** POST  

| Action | Parameters | Effect |
|--------|------------|--------|
| `approve` | `id` (booking id) | Approves booking; notifies user |
| `cancel` | `id` | Cancels booking; notifies user |
| `complete` | `id` | Marks booking completed |

**Success example:**

```json
{
  "success": true,
  "message": "Booking approved",
  "status": "approved"
}
```

**Errors:** Invalid id → `422`; not found → `404`; unknown action → `400`.

---

## 3. `ajax/dijkstra.php`

**Audience:** Any authenticated user  
**Auth:** `requireLogin()`  

| Action | Method | Description |
|--------|--------|-------------|
| `shortest_path` (default) | GET/POST | Compute shortest path (CSRF if POST) |
| `compute` | GET/POST | Alias of shortest_path |
| `graph` | GET/POST | Return active locations and roads |

### 3.1 Shortest path

**Parameters**

| Name | Aliases | Required |
|------|---------|----------|
| start_id | start_location_id | yes |
| end_id | end_location_id | yes |

**Success response:**

```json
{
  "success": true,
  "message": "Shortest path found.",
  "path": [1, 2, 14, 4],
  "path_names": ["Main Gate", "Administrative Building", "Lecture Theatre", "School of Engineering"],
  "distance": 1420,
  "distance_formatted": "1.42 km",
  "hops": 3,
  "locations": [ /* location rows */ ],
  "segments": [ /* from/to/distance per hop */ ]
}
```

### 3.2 Graph

```json
{
  "success": true,
  "locations": [ /* … */ ],
  "roads": [ /* … */ ]
}
```

---

## 4. `ajax/seats.php`

**Audience:** Authenticated users  
**Auth:** `requireLogin()`  
**Method:** GET or POST  

**Parameters**

| Name | Required | Notes |
|------|----------|-------|
| schedule_id | yes | |
| date | no | Defaults to today (Y-m-d) |

**Success response:**

```json
{
  "success": true,
  "schedule_id": 1,
  "date": "2026-07-29",
  "capacity": 14,
  "available_count": 13,
  "taken": ["A3"],
  "seats": [
    { "seat_number": "A1", "available": true },
    { "seat_number": "A3", "available": false }
  ]
}
```

Behaviour: prefers rows from `seats` table; falls back to bookings if no seat rows exist.

---

## 5. `ajax/tracking.php`

**Audience:** Authenticated users (update restricted)  
**Auth:** `requireLogin()`  

| Action | Method | Who | Description |
|--------|--------|-----|-------------|
| `active` (default) | GET/POST | any logged-in | Active trips list |
| `update` | POST + CSRF | admin, driver | Update trip lat/lng / location |
| `by_date` | GET/POST | any logged-in | Trips for a given date |

### 5.1 `action=active`

```json
{
  "success": true,
  "trips": [ /* … */ ],
  "count": 2,
  "refreshed_at": "2026-07-29T22:00:00+01:00"
}
```

### 5.2 `action=update`

**Parameters:** `tracking_id` (required), `latitude`, `longitude`, optional `location_id`, `csrf_token`.

### 5.3 `action=by_date`

**Parameters:** `date` (optional, default today).

---

## 6. `ajax/notifications.php`

**Audience:** Authenticated users (own notifications)  
**Auth:** `requireLogin()`  

| Action | Method | CSRF | Description |
|--------|--------|------|-------------|
| `unread` (default) | GET/POST | no | Unread list + count |
| `mark_read` | POST | yes | Mark one notification read (`id`) |
| `mark_all` | POST | yes | Mark all read for current user |

**Unread response example:**

```json
{
  "success": true,
  "count": 3,
  "notifications": [
    {
      "id": 1,
      "title": "Booking Approved",
      "message": "…",
      "type": "success",
      "time_ago": "5 minutes ago"
    }
  ]
}
```

---

## 7. `ajax/dashboard-stats.php`

**Audience:** Admin only  
**Auth:** `requireRole('admin')`  
**Method:** GET (typically)  
**CSRF:** Not required for this read-only endpoint  

**Parameters**

| Name | Required | Notes |
|------|----------|-------|
| year | no | Defaults to current year for monthly chart |

**Success response (structure):**

```json
{
  "success": true,
  "summary": {
    "/* dashboardSummary fields */": "…",
    "completed_trips_today": 1,
    "todays_trips": 3
  },
  "charts": {
    "monthly_bookings": { "labels": ["Jan", "…"], "data": [0, 1, …] },
    "route_usage": { "labels": ["…"], "data": […] },
    "vehicle_usage": { "labels": ["…"], "data": […] }
  }
}
```

---

## 8. Client Integration Notes

1. Send session cookies with every AJAX call (`xhrFields: { withCredentials: true }` if cross-subdomain).  
2. For jQuery POST, include the CSRF hidden field value from the page.  
3. Prefer `dataType: 'json'` and handle `success === false` in the payload as well as HTTP errors.  
4. Refresh CSRF / page if you receive `403` invalid security token.  
5. Documented APP_URL must match so relative AJAX paths resolve correctly.  

### Example (jQuery)

```javascript
$.ajax({
  url: APP_URL + '/ajax/dijkstra.php',
  method: 'GET',
  data: { action: 'shortest_path', start_id: 1, end_id: 4 },
  dataType: 'json'
}).done(function (res) {
  if (res.success) {
    console.log(res.path_names, res.distance_formatted);
  }
});
```

---

## 9. Endpoint Index

| File | Primary consumers |
|------|-------------------|
| `book.php` | Student/Staff booking UI |
| `booking-actions.php` | Admin bookings table |
| `dijkstra.php` | Route optimisation / path preview |
| `seats.php` | Seat maps |
| `tracking.php` | Live tracking / driver updates |
| `notifications.php` | Navbar notification dropdown |
| `dashboard-stats.php` | Admin dashboard charts |
