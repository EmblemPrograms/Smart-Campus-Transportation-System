<?php
/**
 * User Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * CRUD and query helpers for the users table.
 */

declare(strict_types=1);

class User
{
    /**
     * Find a user by primary key.
     *
     * @param int $id User ID
     * @return array|null User row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT id, full_name, email, phone, password, role, status,
                        email_verified_at, avatar, last_login, created_at, updated_at
                 FROM users WHERE id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('User::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a user by email address.
     *
     * @param string $email Email address
     * @return array|null User row or null
     */
    public static function findByEmail(string $email): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT id, full_name, email, phone, password, role, status,
                        email_verified_at, verification_token, reset_token, reset_token_expires,
                        remember_token, avatar, last_login, created_at, updated_at
                 FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('User::findByEmail failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new user account.
     *
     * @param array $data User fields (full_name, email, phone, password, role, status)
     * @return int|false New user ID or false on failure
     */
    public static function create(array $data)
    {
        try {
            $db = getDB();
            $fullName = (string) ($data['full_name'] ?? '');
            $email = (string) ($data['email'] ?? '');
            $phone = (string) ($data['phone'] ?? '');
            $password = hashPassword((string) ($data['password'] ?? ''));
            $role = (string) ($data['role'] ?? ROLE_STUDENT);
            $status = (string) ($data['status'] ?? 'active');
            $avatar = $data['avatar'] ?? null;

            $stmt = $db->prepare(
                'INSERT INTO users (full_name, email, phone, password, role, status, avatar, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->bind_param('sssssss', $fullName, $email, $phone, $password, $role, $status, $avatar);
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('User::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing user's profile fields.
     *
     * @param int $id User ID
     * @param array $data Fields to update
     * @return bool Success
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = getDB();
            $fullName = (string) ($data['full_name'] ?? '');
            $email = (string) ($data['email'] ?? '');
            $phone = (string) ($data['phone'] ?? '');
            $role = (string) ($data['role'] ?? ROLE_STUDENT);
            $status = (string) ($data['status'] ?? 'active');
            $avatar = $data['avatar'] ?? null;

            $stmt = $db->prepare(
                'UPDATE users
                 SET full_name = ?, email = ?, phone = ?, role = ?, status = ?, avatar = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->bind_param('ssssssi', $fullName, $email, $phone, $role, $status, $avatar, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('User::update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Change a user's password.
     *
     * @param int $id User ID
     * @param string $newPassword Plain-text new password
     * @return bool Success
     */
    public static function changePassword(int $id, string $newPassword): bool
    {
        try {
            $db = getDB();
            $hash = hashPassword($newPassword);
            $stmt = $db->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
            $stmt->bind_param('si', $hash, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('User::changePassword failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a user's account status.
     *
     * @param int $id User ID
     * @param string $status New status (active/inactive/suspended)
     * @return bool Success
     */
    public static function updateStatus(int $id, string $status): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?');
            $stmt->bind_param('si', $status, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('User::updateStatus failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a user by ID.
     *
     * @param int $id User ID
     * @return bool Success
     */
    public static function delete(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('User::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Count users grouped by role.
     *
     * @return array<string, int> Role => count map
     */
    public static function countByRole(): array
    {
        $counts = [
            ROLE_ADMIN => 0,
            ROLE_STUDENT => 0,
            ROLE_STAFF => 0,
            ROLE_DRIVER => 0,
        ];

        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT role, COUNT(*) AS total FROM users GROUP BY role');
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $counts[(string) $row['role']] = (int) $row['total'];
            }
            $stmt->close();
        } catch (Throwable $e) {
            error_log('User::countByRole failed: ' . $e->getMessage());
        }

        return $counts;
    }

    /**
     * Search users with optional filters and pagination.
     *
     * @param string $query Search term for name/email/phone
     * @param string|null $role Optional role filter
     * @param string|null $status Optional status filter
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array{data:array,pagination:array}
     */
    public static function search(
        string $query = '',
        ?string $role = null,
        ?string $status = null,
        int $page = 1,
        int $perPage = ITEMS_PER_PAGE
    ): array {
        $empty = ['data' => [], 'pagination' => paginate(0, $page, $perPage)];

        try {
            $db = getDB();
            $where = ['1=1'];
            $types = '';
            $params = [];

            if ($query !== '') {
                $like = '%' . $query . '%';
                $where[] = '(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
                $types .= 'sss';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            if ($role !== null && $role !== '') {
                $where[] = 'role = ?';
                $types .= 's';
                $params[] = $role;
            }
            if ($status !== null && $status !== '') {
                $where[] = 'status = ?';
                $types .= 's';
                $params[] = $status;
            }

            $whereSql = implode(' AND ', $where);

            $countSql = "SELECT COUNT(*) AS total FROM users WHERE {$whereSql}";
            $countStmt = $db->prepare($countSql);
            if ($types !== '') {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
            $countStmt->close();

            $pagination = paginate($total, $page, $perPage);
            $offset = $pagination['offset'];

            $sql = "SELECT id, full_name, email, phone, role, status, avatar, last_login, created_at
                    FROM users
                    WHERE {$whereSql}
                    ORDER BY created_at DESC
                    LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);
            $limitTypes = $types . 'ii';
            $limitParams = array_merge($params, [$perPage, $offset]);
            $stmt->bind_param($limitTypes, ...$limitParams);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return ['data' => $rows, 'pagination' => $pagination];
        } catch (Throwable $e) {
            error_log('User::search failed: ' . $e->getMessage());
            return $empty;
        }
    }

    /**
     * Get all users, optionally filtered by role.
     *
     * @param string|null $role Optional role filter
     * @return array List of users
     */
    public static function all(?string $role = null): array
    {
        try {
            $db = getDB();
            if ($role !== null && $role !== '') {
                $stmt = $db->prepare(
                    'SELECT id, full_name, email, phone, role, status, avatar, created_at
                     FROM users WHERE role = ? ORDER BY full_name ASC'
                );
                $stmt->bind_param('s', $role);
            } else {
                $stmt = $db->prepare(
                    'SELECT id, full_name, email, phone, role, status, avatar, created_at
                     FROM users ORDER BY full_name ASC'
                );
            }
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('User::all failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update the last_login timestamp for a user.
     *
     * @param int $id User ID
     * @return bool Success
     */
    public static function touchLastLogin(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('UPDATE users SET last_login = NOW(), updated_at = NOW() WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('User::touchLastLogin failed: ' . $e->getMessage());
            return false;
        }
    }
}
