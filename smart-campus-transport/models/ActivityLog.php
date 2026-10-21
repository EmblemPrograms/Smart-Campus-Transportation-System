<?php
/**
 * ActivityLog Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Query helpers for the activity_logs audit table.
 */

declare(strict_types=1);

class ActivityLog
{
    /**
     * Get the most recent activity log entries.
     *
     * @param int $limit Maximum number of rows
     * @return array Recent log rows
     */
    public static function recent(int $limit = 20): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT a.*, u.full_name AS user_name, u.role AS user_role
                 FROM activity_logs a
                 LEFT JOIN users u ON u.id = a.user_id
                 ORDER BY a.created_at DESC
                 LIMIT ?'
            );
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('ActivityLog::recent failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search activity logs with filters and pagination.
     *
     * @param string $query Search term for action/description/module
     * @param string|null $module Optional module filter
     * @param int|null $userId Optional user filter
     * @param string|null $startDate Optional start datetime
     * @param string|null $endDate Optional end datetime
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array{data:array,pagination:array}
     */
    public static function search(
        string $query = '',
        ?string $module = null,
        ?int $userId = null,
        ?string $startDate = null,
        ?string $endDate = null,
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
                $where[] = '(a.action LIKE ? OR a.description LIKE ? OR a.module LIKE ? OR a.ip_address LIKE ?)';
                $types .= 'ssss';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            if ($module !== null && $module !== '') {
                $where[] = 'a.module = ?';
                $types .= 's';
                $params[] = $module;
            }
            if ($userId !== null) {
                $where[] = 'a.user_id = ?';
                $types .= 'i';
                $params[] = $userId;
            }
            if ($startDate !== null && $startDate !== '') {
                $where[] = 'a.created_at >= ?';
                $types .= 's';
                $params[] = $startDate;
            }
            if ($endDate !== null && $endDate !== '') {
                $where[] = 'a.created_at <= ?';
                $types .= 's';
                $params[] = $endDate;
            }

            $whereSql = implode(' AND ', $where);

            $countStmt = $db->prepare(
                "SELECT COUNT(*) AS total FROM activity_logs a WHERE {$whereSql}"
            );
            if ($types !== '') {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
            $countStmt->close();

            $pagination = paginate($total, $page, $perPage);
            $offset = $pagination['offset'];

            $stmt = $db->prepare(
                "SELECT a.*, u.full_name AS user_name, u.role AS user_role
                 FROM activity_logs a
                 LEFT JOIN users u ON u.id = a.user_id
                 WHERE {$whereSql}
                 ORDER BY a.created_at DESC
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
            error_log('ActivityLog::search failed: ' . $e->getMessage());
            return $empty;
        }
    }

    /**
     * Get activity logs for a specific user.
     *
     * @param int $userId User ID
     * @param int $limit Maximum rows
     * @return array Log rows
     */
    public static function getByUser(int $userId, int $limit = 50): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT * FROM activity_logs
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT ?'
            );
            $stmt->bind_param('ii', $userId, $limit);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('ActivityLog::getByUser failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete old activity logs older than the given number of days.
     *
     * @param int $days Retention days
     * @return int Number of deleted rows
     */
    public static function purgeOlderThan(int $days = 90): int
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)'
            );
            $stmt->bind_param('i', $days);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return (int) $affected;
        } catch (Throwable $e) {
            error_log('ActivityLog::purgeOlderThan failed: ' . $e->getMessage());
            return 0;
        }
    }
}
