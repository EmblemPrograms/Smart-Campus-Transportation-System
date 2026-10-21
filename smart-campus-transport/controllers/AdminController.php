<?php
/**
 * Admin Controller
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Processes POST CRUD actions for admin modules. Views may call
 * AdminController::handle() at the top of the page, or include specific
 * static action methods.
 */

declare(strict_types=1);

class AdminController
{
    /**
     * Dispatch the current POST action for an admin module.
     *
     * @param string $module Module key (students, staff, drivers, ...)
     * @return void
     */
    public static function handle(string $module): void
    {
        requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        requireCsrf();

        $action = clean($_POST['action'] ?? '');

        match ($module) {
            'students' => self::handleStudents($action),
            'staff' => self::handleStaff($action),
            'drivers' => self::handleDrivers($action),
            'vehicles' => self::handleVehicles($action),
            'locations' => self::handleLocations($action),
            'roads' => self::handleRoads($action),
            'routes' => self::handleRoutes($action),
            'schedules' => self::handleSchedules($action),
            'bookings' => self::handleBookings($action),
            'notifications' => self::handleNotifications($action),
            'settings' => self::handleSettings($action),
            'profile' => self::handleProfile($action),
            default => flash('error', 'Unknown admin module.'),
        };
    }

    /**
     * Students CRUD: create, update, delete, toggle status.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleStudents(string $action): void
    {
        $uid = currentUserId();

        if ($action === 'create') {
            $email = clean($_POST['email'] ?? '');
            if (User::findByEmail($email)) {
                flash('error', 'Email already exists.');
                return;
            }
            $password = (string) ($_POST['password'] ?? 'Password1');
            $userId = User::create([
                'full_name' => clean($_POST['full_name'] ?? ''),
                'email' => $email,
                'phone' => clean($_POST['phone'] ?? ''),
                'password' => $password,
                'role' => ROLE_STUDENT,
                'status' => clean($_POST['status'] ?? 'active'),
            ]);
            if (!$userId) {
                flash('error', 'Failed to create user account.');
                return;
            }
            $sid = Student::create([
                'user_id' => $userId,
                'matric_number' => clean($_POST['matric_number'] ?? ''),
                'department' => clean($_POST['department'] ?? ''),
                'level' => clean($_POST['level'] ?? ''),
                'faculty' => clean($_POST['faculty'] ?? ''),
            ]);
            if ($sid) {
                logActivity($uid, 'create', "Created student #{$sid}", 'students');
                flash('success', 'Student created successfully.');
            } else {
                User::delete((int) $userId);
                flash('error', 'Failed to create student profile.');
            }
            return;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $student = Student::findById($id);
            if (!$student) {
                flash('error', 'Student not found.');
                return;
            }
            User::update((int) $student['user_id'], [
                'full_name' => clean($_POST['full_name'] ?? ''),
                'email' => clean($_POST['email'] ?? ''),
                'phone' => clean($_POST['phone'] ?? ''),
                'role' => ROLE_STUDENT,
                'status' => clean($_POST['status'] ?? 'active'),
                'avatar' => $student['avatar'] ?? null,
            ]);
            Student::update($id, [
                'matric_number' => clean($_POST['matric_number'] ?? ''),
                'department' => clean($_POST['department'] ?? ''),
                'level' => clean($_POST['level'] ?? ''),
                'faculty' => clean($_POST['faculty'] ?? ''),
            ]);
            logActivity($uid, 'update', "Updated student #{$id}", 'students');
            flash('success', 'Student updated successfully.');
            return;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $student = Student::findById($id);
            if ($student) {
                Student::delete($id);
                User::delete((int) $student['user_id']);
                logActivity($uid, 'delete', "Deleted student #{$id}", 'students');
                flash('success', 'Student deleted.');
            } else {
                flash('error', 'Student not found.');
            }
            return;
        }

        if ($action === 'toggle_status') {
            $id = (int) ($_POST['id'] ?? 0);
            $student = Student::findById($id);
            if ($student) {
                $newStatus = ($student['user_status'] ?? '') === 'active' ? 'inactive' : 'active';
                User::updateStatus((int) $student['user_id'], $newStatus);
                logActivity($uid, 'status', "Set student #{$id} to {$newStatus}", 'students');
                flash('success', 'Student status updated.');
            }
            return;
        }
    }

    /**
     * Staff CRUD handlers.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleStaff(string $action): void
    {
        $uid = currentUserId();

        if ($action === 'create') {
            $email = clean($_POST['email'] ?? '');
            if (User::findByEmail($email)) {
                flash('error', 'Email already exists.');
                return;
            }
            $userId = User::create([
                'full_name' => clean($_POST['full_name'] ?? ''),
                'email' => $email,
                'phone' => clean($_POST['phone'] ?? ''),
                'password' => (string) ($_POST['password'] ?? 'Password1'),
                'role' => ROLE_STAFF,
                'status' => clean($_POST['status'] ?? 'active'),
            ]);
            if (!$userId) {
                flash('error', 'Failed to create user.');
                return;
            }
            $sid = Staff::create([
                'user_id' => $userId,
                'staff_id' => clean($_POST['staff_id'] ?? ''),
                'department' => clean($_POST['department'] ?? ''),
                'position' => clean($_POST['position'] ?? ''),
                'priority_booking' => (int) ($_POST['priority_booking'] ?? 1),
            ]);
            if ($sid) {
                logActivity($uid, 'create', "Created staff #{$sid}", 'staff');
                flash('success', 'Staff member created.');
            } else {
                User::delete((int) $userId);
                flash('error', 'Failed to create staff profile.');
            }
            return;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $staff = Staff::findById($id);
            if (!$staff) {
                flash('error', 'Staff not found.');
                return;
            }
            User::update((int) $staff['user_id'], [
                'full_name' => clean($_POST['full_name'] ?? ''),
                'email' => clean($_POST['email'] ?? ''),
                'phone' => clean($_POST['phone'] ?? ''),
                'role' => ROLE_STAFF,
                'status' => clean($_POST['status'] ?? 'active'),
                'avatar' => $staff['avatar'] ?? null,
            ]);
            Staff::update($id, [
                'staff_id' => clean($_POST['staff_id'] ?? ''),
                'department' => clean($_POST['department'] ?? ''),
                'position' => clean($_POST['position'] ?? ''),
                'priority_booking' => (int) ($_POST['priority_booking'] ?? 1),
            ]);
            logActivity($uid, 'update', "Updated staff #{$id}", 'staff');
            flash('success', 'Staff updated.');
            return;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $staff = Staff::findById($id);
            if ($staff) {
                Staff::delete($id);
                User::delete((int) $staff['user_id']);
                logActivity($uid, 'delete', "Deleted staff #{$id}", 'staff');
                flash('success', 'Staff deleted.');
            }
            return;
        }

        if ($action === 'toggle_status') {
            $id = (int) ($_POST['id'] ?? 0);
            $staff = Staff::findById($id);
            if ($staff) {
                $newStatus = ($staff['user_status'] ?? '') === 'active' ? 'inactive' : 'active';
                User::updateStatus((int) $staff['user_id'], $newStatus);
                flash('success', 'Staff status updated.');
            }
            return;
        }
    }

    /**
     * Drivers CRUD and status assignment.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleDrivers(string $action): void
    {
        $uid = currentUserId();

        if ($action === 'create') {
            $email = clean($_POST['email'] ?? '');
            if (User::findByEmail($email)) {
                flash('error', 'Email already exists.');
                return;
            }
            $userId = User::create([
                'full_name' => clean($_POST['full_name'] ?? ''),
                'email' => $email,
                'phone' => clean($_POST['phone'] ?? ''),
                'password' => (string) ($_POST['password'] ?? 'Password1'),
                'role' => ROLE_DRIVER,
                'status' => 'active',
            ]);
            if (!$userId) {
                flash('error', 'Failed to create user.');
                return;
            }
            $did = Driver::create([
                'user_id' => $userId,
                'license_number' => clean($_POST['license_number'] ?? ''),
                'license_expiry' => clean($_POST['license_expiry'] ?? ''),
                'experience_years' => (int) ($_POST['experience_years'] ?? 0),
                'status' => clean($_POST['status'] ?? 'available'),
            ]);
            if ($did) {
                logActivity($uid, 'create', "Created driver #{$did}", 'drivers');
                flash('success', 'Driver created.');
            } else {
                User::delete((int) $userId);
                flash('error', 'Failed to create driver.');
            }
            return;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $driver = Driver::findById($id);
            if (!$driver) {
                flash('error', 'Driver not found.');
                return;
            }
            User::update((int) $driver['user_id'], [
                'full_name' => clean($_POST['full_name'] ?? ''),
                'email' => clean($_POST['email'] ?? ''),
                'phone' => clean($_POST['phone'] ?? ''),
                'role' => ROLE_DRIVER,
                'status' => clean($_POST['user_status'] ?? 'active'),
                'avatar' => $driver['avatar'] ?? null,
            ]);
            Driver::update($id, [
                'license_number' => clean($_POST['license_number'] ?? ''),
                'license_expiry' => clean($_POST['license_expiry'] ?? ''),
                'experience_years' => (int) ($_POST['experience_years'] ?? 0),
                'status' => clean($_POST['status'] ?? 'available'),
            ]);
            logActivity($uid, 'update', "Updated driver #{$id}", 'drivers');
            flash('success', 'Driver updated.');
            return;
        }

        if ($action === 'set_status') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = clean($_POST['status'] ?? 'available');
            if (Driver::updateDriverStatus($id, $status)) {
                logActivity($uid, 'status', "Driver #{$id} → {$status}", 'drivers');
                flash('success', 'Driver status updated.');
            }
            return;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $driver = Driver::findById($id);
            if ($driver) {
                Driver::delete($id);
                User::delete((int) $driver['user_id']);
                logActivity($uid, 'delete', "Deleted driver #{$id}", 'drivers');
                flash('success', 'Driver deleted.');
            }
            return;
        }
    }

    /**
     * Vehicles CRUD and driver assignment.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleVehicles(string $action): void
    {
        $uid = currentUserId();

        if ($action === 'create') {
            $id = Vehicle::create([
                'plate_number' => clean($_POST['plate_number'] ?? ''),
                'model' => clean($_POST['model'] ?? ''),
                'make' => clean($_POST['make'] ?? ''),
                'capacity' => (int) ($_POST['capacity'] ?? 0),
                'year' => (int) ($_POST['year'] ?? date('Y')),
                'color' => clean($_POST['color'] ?? ''),
                'status' => clean($_POST['status'] ?? 'available'),
                'current_driver_id' => $_POST['current_driver_id'] ?? null,
            ]);
            if ($id) {
                logActivity($uid, 'create', "Created vehicle #{$id}", 'vehicles');
                flash('success', 'Vehicle created.');
            } else {
                flash('error', 'Failed to create vehicle.');
            }
            return;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            if (Vehicle::update($id, [
                'plate_number' => clean($_POST['plate_number'] ?? ''),
                'model' => clean($_POST['model'] ?? ''),
                'make' => clean($_POST['make'] ?? ''),
                'capacity' => (int) ($_POST['capacity'] ?? 0),
                'year' => (int) ($_POST['year'] ?? date('Y')),
                'color' => clean($_POST['color'] ?? ''),
                'status' => clean($_POST['status'] ?? 'available'),
                'current_driver_id' => $_POST['current_driver_id'] ?? null,
            ])) {
                logActivity($uid, 'update', "Updated vehicle #{$id}", 'vehicles');
                flash('success', 'Vehicle updated.');
            } else {
                flash('error', 'Failed to update vehicle.');
            }
            return;
        }

        if ($action === 'assign_driver') {
            $id = (int) ($_POST['id'] ?? 0);
            $driverId = $_POST['current_driver_id'] !== '' ? (int) $_POST['current_driver_id'] : null;
            if (Vehicle::assignDriver($id, $driverId)) {
                flash('success', 'Driver assigned to vehicle.');
                logActivity($uid, 'assign', "Assigned driver to vehicle #{$id}", 'vehicles');
            }
            return;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if (Vehicle::delete($id)) {
                logActivity($uid, 'delete', "Deleted vehicle #{$id}", 'vehicles');
                flash('success', 'Vehicle deleted.');
            }
            return;
        }
    }

    /**
     * Locations CRUD.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleLocations(string $action): void
    {
        $uid = currentUserId();

        if ($action === 'create') {
            $id = Location::create([
                'name' => clean($_POST['name'] ?? ''),
                'code' => clean($_POST['code'] ?? ''),
                'description' => clean($_POST['description'] ?? ''),
                'latitude' => (float) ($_POST['latitude'] ?? 0),
                'longitude' => (float) ($_POST['longitude'] ?? 0),
                'location_type' => clean($_POST['location_type'] ?? 'other'),
                'status' => clean($_POST['status'] ?? 'active'),
            ]);
            if ($id) {
                logActivity($uid, 'create', "Created location #{$id}", 'locations');
                flash('success', 'Location created.');
            } else {
                flash('error', 'Failed to create location.');
            }
            return;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            if (Location::update($id, [
                'name' => clean($_POST['name'] ?? ''),
                'code' => clean($_POST['code'] ?? ''),
                'description' => clean($_POST['description'] ?? ''),
                'latitude' => (float) ($_POST['latitude'] ?? 0),
                'longitude' => (float) ($_POST['longitude'] ?? 0),
                'location_type' => clean($_POST['location_type'] ?? 'other'),
                'status' => clean($_POST['status'] ?? 'active'),
            ])) {
                logActivity($uid, 'update', "Updated location #{$id}", 'locations');
                flash('success', 'Location updated.');
            }
            return;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if (Location::delete($id)) {
                logActivity($uid, 'delete', "Deleted location #{$id}", 'locations');
                flash('success', 'Location deleted.');
            }
            return;
        }
    }

    /**
     * Roads CRUD.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleRoads(string $action): void
    {
        $uid = currentUserId();

        if ($action === 'create') {
            $id = Road::create([
                'from_location_id' => (int) ($_POST['from_location_id'] ?? 0),
                'to_location_id' => (int) ($_POST['to_location_id'] ?? 0),
                'distance_metres' => (float) ($_POST['distance_metres'] ?? 0),
                'travel_time_minutes' => (int) ($_POST['travel_time_minutes'] ?? 0),
                'is_bidirectional' => isset($_POST['is_bidirectional']) ? 1 : 0,
                'status' => clean($_POST['status'] ?? 'active'),
            ]);
            if ($id) {
                logActivity($uid, 'create', "Created road #{$id}", 'roads');
                flash('success', 'Road created.');
            } else {
                flash('error', 'Failed to create road.');
            }
            return;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            if (Road::update($id, [
                'from_location_id' => (int) ($_POST['from_location_id'] ?? 0),
                'to_location_id' => (int) ($_POST['to_location_id'] ?? 0),
                'distance_metres' => (float) ($_POST['distance_metres'] ?? 0),
                'travel_time_minutes' => (int) ($_POST['travel_time_minutes'] ?? 0),
                'is_bidirectional' => isset($_POST['is_bidirectional']) ? 1 : 0,
                'status' => clean($_POST['status'] ?? 'active'),
            ])) {
                logActivity($uid, 'update', "Updated road #{$id}", 'roads');
                flash('success', 'Road updated.');
            }
            return;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if (Road::delete($id)) {
                logActivity($uid, 'delete', "Deleted road #{$id}", 'roads');
                flash('success', 'Road deleted.');
            }
            return;
        }
    }

    /**
     * Routes CRUD and Dijkstra-optimised save.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleRoutes(string $action): void
    {
        $uid = currentUserId();

        if ($action === 'create' || $action === 'optimize_save') {
            $data = [
                'name' => clean($_POST['name'] ?? ''),
                'code' => clean($_POST['code'] ?? ''),
                'start_location_id' => (int) ($_POST['start_location_id'] ?? 0),
                'end_location_id' => (int) ($_POST['end_location_id'] ?? 0),
                'description' => clean($_POST['description'] ?? ''),
                'status' => clean($_POST['status'] ?? 'active'),
                'estimated_time' => (int) ($_POST['estimated_time'] ?? 0),
            ];

            if ($action === 'optimize_save' || !empty($_POST['use_dijkstra'])) {
                $id = Route::saveOptimizedPath($data);
            } else {
                $pathJson = $_POST['path_json'] ?? '[]';
                if (is_string($pathJson)) {
                    $decoded = json_decode($pathJson, true);
                    $pathJson = is_array($decoded) ? $decoded : [];
                }
                $data['path_json'] = $pathJson;
                $data['total_distance'] = (float) ($_POST['total_distance'] ?? 0);
                $id = Route::create($data);
            }

            if ($id) {
                logActivity($uid, 'create', "Created route #{$id}", 'routes');
                flash('success', 'Route saved successfully.');
            } else {
                flash('error', 'Failed to save route. Check start/end connectivity.');
            }
            return;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $pathJson = $_POST['path_json'] ?? '[]';
            if (is_string($pathJson)) {
                $decoded = json_decode($pathJson, true);
                $pathJson = is_array($decoded) ? $decoded : [];
            }
            if (Route::update($id, [
                'name' => clean($_POST['name'] ?? ''),
                'code' => clean($_POST['code'] ?? ''),
                'start_location_id' => (int) ($_POST['start_location_id'] ?? 0),
                'end_location_id' => (int) ($_POST['end_location_id'] ?? 0),
                'path_json' => $pathJson,
                'total_distance' => (float) ($_POST['total_distance'] ?? 0),
                'estimated_time' => (int) ($_POST['estimated_time'] ?? 0),
                'description' => clean($_POST['description'] ?? ''),
                'status' => clean($_POST['status'] ?? 'active'),
            ])) {
                logActivity($uid, 'update', "Updated route #{$id}", 'routes');
                flash('success', 'Route updated.');
            }
            return;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if (Route::delete($id)) {
                logActivity($uid, 'delete', "Deleted route #{$id}", 'routes');
                flash('success', 'Route deleted.');
            }
            return;
        }
    }

    /**
     * Schedules CRUD and assignments.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleSchedules(string $action): void
    {
        $uid = currentUserId();

        $days = $_POST['days_of_week'] ?? [];
        if (is_array($days)) {
            $days = implode(',', $days);
        }

        if ($action === 'create') {
            $id = Schedule::create([
                'route_id' => (int) ($_POST['route_id'] ?? 0),
                'vehicle_id' => $_POST['vehicle_id'] ?? null,
                'driver_id' => $_POST['driver_id'] ?? null,
                'departure_time' => clean($_POST['departure_time'] ?? '00:00'),
                'arrival_time' => clean($_POST['arrival_time'] ?? '00:00'),
                'days_of_week' => $days,
                'available_seats' => (int) ($_POST['available_seats'] ?? 0),
                'status' => clean($_POST['status'] ?? 'active'),
            ]);
            if ($id) {
                logActivity($uid, 'create', "Created schedule #{$id}", 'schedules');
                flash('success', 'Schedule created.');
            } else {
                flash('error', 'Failed to create schedule.');
            }
            return;
        }

        if ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            if (Schedule::update($id, [
                'route_id' => (int) ($_POST['route_id'] ?? 0),
                'vehicle_id' => $_POST['vehicle_id'] ?? null,
                'driver_id' => $_POST['driver_id'] ?? null,
                'departure_time' => clean($_POST['departure_time'] ?? '00:00'),
                'arrival_time' => clean($_POST['arrival_time'] ?? '00:00'),
                'days_of_week' => $days,
                'available_seats' => (int) ($_POST['available_seats'] ?? 0),
                'status' => clean($_POST['status'] ?? 'active'),
            ])) {
                logActivity($uid, 'update', "Updated schedule #{$id}", 'schedules');
                flash('success', 'Schedule updated.');
            }
            return;
        }

        if ($action === 'assign') {
            $id = (int) ($_POST['id'] ?? 0);
            if (!empty($_POST['vehicle_id'])) {
                Schedule::assignVehicle($id, (int) $_POST['vehicle_id']);
            }
            if (!empty($_POST['driver_id'])) {
                Schedule::assignDriver($id, (int) $_POST['driver_id']);
            }
            flash('success', 'Assignment saved.');
            return;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if (Schedule::delete($id)) {
                logActivity($uid, 'delete', "Deleted schedule #{$id}", 'schedules');
                flash('success', 'Schedule deleted.');
            }
            return;
        }
    }

    /**
     * Booking approve / cancel / complete.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleBookings(string $action): void
    {
        $uid = currentUserId();
        $id = (int) ($_POST['id'] ?? 0);

        if ($action === 'approve') {
            if (Booking::approve($id)) {
                $booking = Booking::findById($id);
                if ($booking) {
                    createNotification(
                        (int) $booking['user_id'],
                        'Booking Approved',
                        'Your booking ' . $booking['booking_number'] . ' has been approved.',
                        'success',
                        url('views/student/bookings.php')
                    );
                }
                logActivity($uid, 'approve', "Approved booking #{$id}", 'bookings');
                flash('success', 'Booking approved.');
            } else {
                flash('error', 'Could not approve booking.');
            }
            return;
        }

        if ($action === 'cancel') {
            if (Booking::cancel($id, $uid)) {
                $booking = Booking::findById($id);
                if ($booking) {
                    createNotification(
                        (int) $booking['user_id'],
                        'Booking Cancelled',
                        'Your booking ' . $booking['booking_number'] . ' was cancelled by admin.',
                        'warning'
                    );
                }
                logActivity($uid, 'cancel', "Cancelled booking #{$id}", 'bookings');
                flash('success', 'Booking cancelled.');
            } else {
                flash('error', 'Could not cancel booking.');
            }
            return;
        }

        if ($action === 'complete') {
            if (Booking::complete($id)) {
                logActivity($uid, 'complete', "Completed booking #{$id}", 'bookings');
                flash('success', 'Booking marked completed.');
            }
            return;
        }
    }

    /**
     * Send announcements / notifications.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleNotifications(string $action): void
    {
        $uid = currentUserId();

        if ($action === 'announce') {
            $title = clean($_POST['title'] ?? '');
            $message = clean($_POST['message'] ?? '');
            $type = clean($_POST['type'] ?? 'info');
            $audience = clean($_POST['audience'] ?? 'all');

            $users = [];
            if ($audience === 'all') {
                $users = User::all();
            } else {
                $users = User::all($audience);
            }

            $count = 0;
            foreach ($users as $user) {
                if (Notification::create((int) $user['id'], $title, $message, $type)) {
                    $count++;
                }
            }
            logActivity($uid, 'announce', "Sent announcement to {$count} users", 'notifications');
            flash('success', "Announcement sent to {$count} user(s).");
            return;
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            Notification::delete($id);
            flash('success', 'Notification deleted.');
            return;
        }
    }

    /**
     * System settings save.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleSettings(string $action): void
    {
        if ($action !== 'save') {
            return;
        }

        $keys = [
            'app_name', 'institution_name', 'support_email', 'support_phone',
            'booking_window_hours', 'max_seats_per_booking', 'auto_approve_staff',
            'maintenance_mode', 'tracking_enabled', 'notification_email_enabled',
        ];

        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                Setting::set($key, clean((string) $_POST[$key]));
            }
        }

        // Checkboxes that may be absent
        Setting::set('auto_approve_staff', isset($_POST['auto_approve_staff']) ? '1' : '0');
        Setting::set('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
        Setting::set('tracking_enabled', isset($_POST['tracking_enabled']) ? '1' : '0');
        Setting::set('notification_email_enabled', isset($_POST['notification_email_enabled']) ? '1' : '0');

        logActivity(currentUserId(), 'update', 'Updated system settings', 'settings');
        flash('success', 'Settings saved.');
    }

    /**
     * Admin profile update and password change.
     *
     * @param string $action Action name
     * @return void
     */
    public static function handleProfile(string $action): void
    {
        $uid = (int) currentUserId();
        $user = User::findById($uid);
        if (!$user) {
            flash('error', 'User not found.');
            return;
        }

        if ($action === 'update_profile') {
            User::update($uid, [
                'full_name' => clean($_POST['full_name'] ?? ''),
                'email' => clean($_POST['email'] ?? ''),
                'phone' => clean($_POST['phone'] ?? ''),
                'role' => $user['role'],
                'status' => $user['status'],
                'avatar' => $user['avatar'] ?? null,
            ]);
            $_SESSION['user_name'] = clean($_POST['full_name'] ?? '');
            $_SESSION['user_email'] = clean($_POST['email'] ?? '');
            logActivity($uid, 'update', 'Updated admin profile', 'profile');
            flash('success', 'Profile updated.');
            return;
        }

        if ($action === 'change_password') {
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');

            if (!verifyPassword($current, (string) $user['password'])) {
                flash('error', 'Current password is incorrect.');
                return;
            }
            if ($new !== $confirm) {
                flash('error', 'New passwords do not match.');
                return;
            }
            $check = validatePassword($new);
            if (!$check['valid']) {
                flash('error', $check['message']);
                return;
            }
            User::changePassword($uid, $new);
            logActivity($uid, 'password', 'Changed admin password', 'profile');
            flash('success', 'Password changed successfully.');
            return;
        }
    }
}
