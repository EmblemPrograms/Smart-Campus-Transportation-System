<?php
/**
 * Staff Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * CRUD for staff profiles linked to users.
 */

declare(strict_types=1);

class Staff
{
    /**
     * Find a staff profile by primary key with user details.
     *
     * @param int $id Staff ID
     * @return array|null Staff row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT s.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM staff s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE s.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Staff::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a staff profile by linked user ID.
     *
     * @param int $userId User ID
     * @return array|null Staff row or null
     */
    public static function findByUserId(int $userId): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT s.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM staff s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE s.user_id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Staff::findByUserId failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a staff profile by staff ID number.
     *
     * @param string $staffId Staff ID number
     * @return array|null Staff row or null
     */
    public static function findByStaffId(string $staffId): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT s.*, u.full_name, u.email, u.phone, u.status AS user_status
                 FROM staff s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE s.staff_id = ? LIMIT 1'
            );
            $stmt->bind_param('s', $staffId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Staff::findByStaffId failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a staff profile linked to a user.
     *
     * @param array $data Staff fields
     * @return int|false New staff ID or false
     */
    public static function create(array $data)
    {
        try {
            $db = getDB();
            $userId = (int) ($data['user_id'] ?? 0);
            $staffId = (string) ($data['staff_id'] ?? '');
            $department = (string) ($data['department'] ?? '');
            $position = (string) ($data['position'] ?? '');
            $priority = (int) ($data['priority_booking'] ?? 1);

            $stmt = $db->prepare(
                'INSERT INTO staff (user_id, staff_id, department, position, priority_booking, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->bind_param('isssi', $userId, $staffId, $department, $position, $priority);
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('Staff::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a staff profile.
     *
     * @param int $id Staff ID
     * @param array $data Fields to update
     * @return bool Success
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = getDB();
            $staffId = (string) ($data['staff_id'] ?? '');
            $department = (string) ($data['department'] ?? '');
            $position = (string) ($data['position'] ?? '');
            $priority = (int) ($data['priority_booking'] ?? 1);

            $stmt = $db->prepare(
                'UPDATE staff
                 SET staff_id = ?, department = ?, position = ?, priority_booking = ?
                 WHERE id = ?'
            );
            $stmt->bind_param('sssii', $staffId, $department, $position, $priority, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Staff::update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a staff profile.
     *
     * @param int $id Staff ID
     * @return bool Success
     */
    public static function delete(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM staff WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Staff::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all staff with user details.
     *
     * @return array List of staff
     */
    public static function all(): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT s.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM staff s
                 INNER JOIN users u ON u.id = s.user_id
                 ORDER BY u.full_name ASC'
            );
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Staff::all failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search staff with pagination.
     *
     * @param string $query Search term
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array{data:array,pagination:array}
     */
    public static function search(string $query = '', int $page = 1, int $perPage = ITEMS_PER_PAGE): array
    {
        $empty = ['data' => [], 'pagination' => paginate(0, $page, $perPage)];

        try {
            $db = getDB();
            $like = '%' . $query . '%';

            $countStmt = $db->prepare(
                'SELECT COUNT(*) AS total
                 FROM staff s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE u.full_name LIKE ? OR u.email LIKE ? OR s.staff_id LIKE ? OR s.department LIKE ?'
            );
            $countStmt->bind_param('ssss', $like, $like, $like, $like);
            $countStmt->execute();
            $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
            $countStmt->close();

            $pagination = paginate($total, $page, $perPage);
            $offset = $pagination['offset'];

            $stmt = $db->prepare(
                'SELECT s.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM staff s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE u.full_name LIKE ? OR u.email LIKE ? OR s.staff_id LIKE ? OR s.department LIKE ?
                 ORDER BY u.full_name ASC
                 LIMIT ? OFFSET ?'
            );
            $stmt->bind_param('ssssii', $like, $like, $like, $like, $perPage, $offset);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return ['data' => $rows, 'pagination' => $pagination];
        } catch (Throwable $e) {
            error_log('Staff::search failed: ' . $e->getMessage());
            return $empty;
        }
    }
}
