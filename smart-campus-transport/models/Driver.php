<?php
/**
 * Driver Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * CRUD for driver profiles linked to users.
 */

declare(strict_types=1);

class Driver
{
    /**
     * Find a driver by primary key with user details.
     *
     * @param int $id Driver ID
     * @return array|null Driver row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT d.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM drivers d
                 INNER JOIN users u ON u.id = d.user_id
                 WHERE d.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Driver::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a driver profile by linked user ID.
     *
     * @param int $userId User ID
     * @return array|null Driver row or null
     */
    public static function findByUserId(int $userId): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT d.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM drivers d
                 INNER JOIN users u ON u.id = d.user_id
                 WHERE d.user_id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Driver::findByUserId failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a driver profile linked to a user.
     *
     * @param array $data Driver fields
     * @return int|false New driver ID or false
     */
    public static function create(array $data)
    {
        try {
            $db = getDB();
            $userId = (int) ($data['user_id'] ?? 0);
            $license = (string) ($data['license_number'] ?? '');
            $expiry = (string) ($data['license_expiry'] ?? '');
            $experience = (int) ($data['experience_years'] ?? 0);
            $status = (string) ($data['status'] ?? 'available');

            $stmt = $db->prepare(
                'INSERT INTO drivers (user_id, license_number, license_expiry, experience_years, status, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->bind_param('issis', $userId, $license, $expiry, $experience, $status);
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('Driver::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a driver profile.
     *
     * @param int $id Driver ID
     * @param array $data Fields to update
     * @return bool Success
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = getDB();
            $license = (string) ($data['license_number'] ?? '');
            $expiry = (string) ($data['license_expiry'] ?? '');
            $experience = (int) ($data['experience_years'] ?? 0);
            $status = (string) ($data['status'] ?? 'available');

            $stmt = $db->prepare(
                'UPDATE drivers
                 SET license_number = ?, license_expiry = ?, experience_years = ?, status = ?
                 WHERE id = ?'
            );
            $stmt->bind_param('ssisi', $license, $expiry, $experience, $status, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Driver::update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update only the driver's operational status.
     *
     * @param int $id Driver ID
     * @param string $status New status (available/on_trip/off_duty)
     * @return bool Success
     */
    public static function updateDriverStatus(int $id, string $status): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('UPDATE drivers SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $status, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Driver::updateDriverStatus failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all drivers who are currently available.
     *
     * @return array List of available drivers
     */
    public static function getAvailable(): array
    {
        try {
            $db = getDB();
            $status = 'available';
            $userStatus = 'active';
            $stmt = $db->prepare(
                'SELECT d.*, u.full_name, u.email, u.phone, u.avatar
                 FROM drivers d
                 INNER JOIN users u ON u.id = d.user_id
                 WHERE d.status = ? AND u.status = ?
                 ORDER BY u.full_name ASC'
            );
            $stmt->bind_param('ss', $status, $userStatus);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Driver::getAvailable failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a driver profile.
     *
     * @param int $id Driver ID
     * @return bool Success
     */
    public static function delete(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM drivers WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Driver::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all drivers with user details.
     *
     * @return array List of drivers
     */
    public static function all(): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT d.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM drivers d
                 INNER JOIN users u ON u.id = d.user_id
                 ORDER BY u.full_name ASC'
            );
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Driver::all failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search drivers with pagination.
     *
     * @param string $query Search term
     * @param string|null $status Optional driver status filter
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array{data:array,pagination:array}
     */
    public static function search(
        string $query = '',
        ?string $status = null,
        int $page = 1,
        int $perPage = ITEMS_PER_PAGE
    ): array {
        $empty = ['data' => [], 'pagination' => paginate(0, $page, $perPage)];

        try {
            $db = getDB();
            $like = '%' . $query . '%';
            $where = '(u.full_name LIKE ? OR u.email LIKE ? OR d.license_number LIKE ?)';
            $types = 'sss';
            $params = [$like, $like, $like];

            if ($status !== null && $status !== '') {
                $where .= ' AND d.status = ?';
                $types .= 's';
                $params[] = $status;
            }

            $countStmt = $db->prepare(
                "SELECT COUNT(*) AS total
                 FROM drivers d
                 INNER JOIN users u ON u.id = d.user_id
                 WHERE {$where}"
            );
            $countStmt->bind_param($types, ...$params);
            $countStmt->execute();
            $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
            $countStmt->close();

            $pagination = paginate($total, $page, $perPage);
            $offset = $pagination['offset'];

            $stmt = $db->prepare(
                "SELECT d.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM drivers d
                 INNER JOIN users u ON u.id = d.user_id
                 WHERE {$where}
                 ORDER BY u.full_name ASC
                 LIMIT ? OFFSET ?"
            );
            $limitTypes = $types . 'ii';
            $limitParams = array_merge($params, [$perPage, $offset]);
            $stmt->bind_param($limitTypes, ...$limitParams);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return ['data' => $rows, 'pagination' => $pagination];
        } catch (Throwable $e) {
            error_log('Driver::search failed: ' . $e->getMessage());
            return $empty;
        }
    }
}
