<?php
/**
 * Vehicle Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * CRUD and assignment helpers for campus vehicles.
 */

declare(strict_types=1);

class Vehicle
{
    /**
     * Find a vehicle by primary key with optional driver details.
     *
     * @param int $id Vehicle ID
     * @return array|null Vehicle row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT v.*, u.full_name AS driver_name, u.phone AS driver_phone
                 FROM vehicles v
                 LEFT JOIN drivers d ON d.id = v.current_driver_id
                 LEFT JOIN users u ON u.id = d.user_id
                 WHERE v.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Vehicle::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a vehicle by plate number.
     *
     * @param string $plate Plate number
     * @return array|null Vehicle row or null
     */
    public static function findByPlate(string $plate): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM vehicles WHERE plate_number = ? LIMIT 1');
            $stmt->bind_param('s', $plate);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Vehicle::findByPlate failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new vehicle record.
     *
     * @param array $data Vehicle fields
     * @return int|false New vehicle ID or false
     */
    public static function create(array $data)
    {
        try {
            $db = getDB();
            $plate = (string) ($data['plate_number'] ?? '');
            $model = (string) ($data['model'] ?? '');
            $make = (string) ($data['make'] ?? '');
            $capacity = (int) ($data['capacity'] ?? 0);
            $year = (int) ($data['year'] ?? date('Y'));
            $color = (string) ($data['color'] ?? '');
            $status = (string) ($data['status'] ?? 'available');
            $driverId = isset($data['current_driver_id']) && $data['current_driver_id'] !== ''
                ? (int) $data['current_driver_id']
                : null;

            $stmt = $db->prepare(
                'INSERT INTO vehicles
                    (plate_number, model, make, capacity, year, color, status, current_driver_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->bind_param('sssiissi', $plate, $model, $make, $capacity, $year, $color, $status, $driverId);
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('Vehicle::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a vehicle record.
     *
     * @param int $id Vehicle ID
     * @param array $data Fields to update
     * @return bool Success
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = getDB();
            $plate = (string) ($data['plate_number'] ?? '');
            $model = (string) ($data['model'] ?? '');
            $make = (string) ($data['make'] ?? '');
            $capacity = (int) ($data['capacity'] ?? 0);
            $year = (int) ($data['year'] ?? date('Y'));
            $color = (string) ($data['color'] ?? '');
            $status = (string) ($data['status'] ?? 'available');
            $driverId = isset($data['current_driver_id']) && $data['current_driver_id'] !== '' && $data['current_driver_id'] !== null
                ? (int) $data['current_driver_id']
                : null;

            $stmt = $db->prepare(
                'UPDATE vehicles
                 SET plate_number = ?, model = ?, make = ?, capacity = ?, year = ?,
                     color = ?, status = ?, current_driver_id = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->bind_param('sssiissii', $plate, $model, $make, $capacity, $year, $color, $status, $driverId, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Vehicle::update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign a driver to a vehicle.
     *
     * @param int $vehicleId Vehicle ID
     * @param int|null $driverId Driver ID or null to unassign
     * @return bool Success
     */
    public static function assignDriver(int $vehicleId, ?int $driverId): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'UPDATE vehicles SET current_driver_id = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->bind_param('ii', $driverId, $vehicleId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Vehicle::assignDriver failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a vehicle's status.
     *
     * @param int $id Vehicle ID
     * @param string $status New status (available/in_use/maintenance/unavailable)
     * @return bool Success
     */
    public static function updateStatus(int $id, string $status): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('UPDATE vehicles SET status = ?, updated_at = NOW() WHERE id = ?');
            $stmt->bind_param('si', $status, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Vehicle::updateStatus failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a vehicle.
     *
     * @param int $id Vehicle ID
     * @return bool Success
     */
    public static function delete(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM vehicles WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Vehicle::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all vehicles with driver names.
     *
     * @param string|null $status Optional status filter
     * @return array List of vehicles
     */
    public static function all(?string $status = null): array
    {
        try {
            $db = getDB();
            if ($status !== null && $status !== '') {
                $stmt = $db->prepare(
                    'SELECT v.*, u.full_name AS driver_name
                     FROM vehicles v
                     LEFT JOIN drivers d ON d.id = v.current_driver_id
                     LEFT JOIN users u ON u.id = d.user_id
                     WHERE v.status = ?
                     ORDER BY v.plate_number ASC'
                );
                $stmt->bind_param('s', $status);
            } else {
                $stmt = $db->prepare(
                    'SELECT v.*, u.full_name AS driver_name
                     FROM vehicles v
                     LEFT JOIN drivers d ON d.id = v.current_driver_id
                     LEFT JOIN users u ON u.id = d.user_id
                     ORDER BY v.plate_number ASC'
                );
            }
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Vehicle::all failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available vehicles (not in use or maintenance).
     *
     * @return array List of available vehicles
     */
    public static function getAvailable(): array
    {
        return self::all('available');
    }
}
