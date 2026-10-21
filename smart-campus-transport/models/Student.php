<?php
/**
 * Student Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * CRUD for student profiles linked to users.
 */

declare(strict_types=1);

class Student
{
    /**
     * Find a student by primary key with user details.
     *
     * @param int $id Student ID
     * @return array|null Student row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT s.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM students s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE s.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Student::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a student profile by linked user ID.
     *
     * @param int $userId User ID
     * @return array|null Student row or null
     */
    public static function findByUserId(int $userId): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT s.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM students s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE s.user_id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Student::findByUserId failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a student by matric number.
     *
     * @param string $matric Matric number
     * @return array|null Student row or null
     */
    public static function findByMatric(string $matric): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT s.*, u.full_name, u.email, u.phone, u.status AS user_status
                 FROM students s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE s.matric_number = ? LIMIT 1'
            );
            $stmt->bind_param('s', $matric);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Student::findByMatric failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a student profile linked to a user.
     *
     * @param array $data Student fields
     * @return int|false New student ID or false
     */
    public static function create(array $data)
    {
        try {
            $db = getDB();
            $userId = (int) ($data['user_id'] ?? 0);
            $matric = (string) ($data['matric_number'] ?? '');
            $department = (string) ($data['department'] ?? '');
            $level = (string) ($data['level'] ?? '');
            $faculty = (string) ($data['faculty'] ?? '');

            $stmt = $db->prepare(
                'INSERT INTO students (user_id, matric_number, department, level, faculty, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->bind_param('issss', $userId, $matric, $department, $level, $faculty);
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('Student::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a student profile.
     *
     * @param int $id Student ID
     * @param array $data Fields to update
     * @return bool Success
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = getDB();
            $matric = (string) ($data['matric_number'] ?? '');
            $department = (string) ($data['department'] ?? '');
            $level = (string) ($data['level'] ?? '');
            $faculty = (string) ($data['faculty'] ?? '');

            $stmt = $db->prepare(
                'UPDATE students
                 SET matric_number = ?, department = ?, level = ?, faculty = ?
                 WHERE id = ?'
            );
            $stmt->bind_param('ssssi', $matric, $department, $level, $faculty, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Student::update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a student profile.
     *
     * @param int $id Student ID
     * @return bool Success
     */
    public static function delete(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM students WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Student::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all students with user details.
     *
     * @return array List of students
     */
    public static function all(): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT s.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM students s
                 INNER JOIN users u ON u.id = s.user_id
                 ORDER BY u.full_name ASC'
            );
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Student::all failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search students with pagination.
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
                 FROM students s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE u.full_name LIKE ? OR u.email LIKE ? OR s.matric_number LIKE ? OR s.department LIKE ?'
            );
            $countStmt->bind_param('ssss', $like, $like, $like, $like);
            $countStmt->execute();
            $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
            $countStmt->close();

            $pagination = paginate($total, $page, $perPage);
            $offset = $pagination['offset'];

            $stmt = $db->prepare(
                'SELECT s.*, u.full_name, u.email, u.phone, u.status AS user_status, u.avatar
                 FROM students s
                 INNER JOIN users u ON u.id = s.user_id
                 WHERE u.full_name LIKE ? OR u.email LIKE ? OR s.matric_number LIKE ? OR s.department LIKE ?
                 ORDER BY u.full_name ASC
                 LIMIT ? OFFSET ?'
            );
            $stmt->bind_param('ssssii', $like, $like, $like, $like, $perPage, $offset);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return ['data' => $rows, 'pagination' => $pagination];
        } catch (Throwable $e) {
            error_log('Student::search failed: ' . $e->getMessage());
            return $empty;
        }
    }
}
