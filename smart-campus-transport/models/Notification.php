<?php
/**
 * Notification Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * In-app notification management.
 */

declare(strict_types=1);

class Notification
{
    /**
     * Create a notification for a user.
     *
     * @param int $userId Recipient user ID
     * @param string $title Notification title
     * @param string $message Notification body
     * @param string $type Type (info/success/warning/danger)
     * @param string|null $link Optional relative/absolute link
     * @return int|false New notification ID or false
     */
    public static function create(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $link = null
    ) {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at)
                 VALUES (?, ?, ?, ?, ?, 0, NOW())'
            );
            $stmt->bind_param('issss', $userId, $title, $message, $type, $link);
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('Notification::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark a single notification as read.
     *
     * @param int $id Notification ID
     * @param int|null $userId Optional owner check
     * @return bool Success
     */
    public static function markRead(int $id, ?int $userId = null): bool
    {
        try {
            $db = getDB();
            if ($userId !== null) {
                $stmt = $db->prepare(
                    'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
                );
                $stmt->bind_param('ii', $id, $userId);
            } else {
                $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');
                $stmt->bind_param('i', $id);
            }
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Notification::markRead failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param int $userId User ID
     * @return bool Success
     */
    public static function markAllRead(int $userId): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
            );
            $stmt->bind_param('i', $userId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Notification::markAllRead failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get unread notifications for a user.
     *
     * @param int $userId User ID
     * @param int $limit Maximum rows
     * @return array List of unread notifications
     */
    public static function getUnread(int $userId, int $limit = 20): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT * FROM notifications
                 WHERE user_id = ? AND is_read = 0
                 ORDER BY created_at DESC
                 LIMIT ?'
            );
            $stmt->bind_param('ii', $userId, $limit);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Notification::getUnread failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all notifications for a user with optional pagination.
     *
     * @param int $userId User ID
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array{data:array,pagination:array,unread_count:int}
     */
    public static function getForUser(int $userId, int $page = 1, int $perPage = ITEMS_PER_PAGE): array
    {
        $empty = [
            'data' => [],
            'pagination' => paginate(0, $page, $perPage),
            'unread_count' => 0,
        ];

        try {
            $db = getDB();

            $countStmt = $db->prepare(
                'SELECT COUNT(*) AS total,
                        SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread_count
                 FROM notifications WHERE user_id = ?'
            );
            $countStmt->bind_param('i', $userId);
            $countStmt->execute();
            $counts = $countStmt->get_result()->fetch_assoc();
            $countStmt->close();

            $total = (int) ($counts['total'] ?? 0);
            $unread = (int) ($counts['unread_count'] ?? 0);
            $pagination = paginate($total, $page, $perPage);
            $offset = $pagination['offset'];

            $stmt = $db->prepare(
                'SELECT * FROM notifications
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?'
            );
            $stmt->bind_param('iii', $userId, $perPage, $offset);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return [
                'data' => $rows,
                'pagination' => $pagination,
                'unread_count' => $unread,
            ];
        } catch (Throwable $e) {
            error_log('Notification::getForUser failed: ' . $e->getMessage());
            return $empty;
        }
    }

    /**
     * Delete a notification.
     *
     * @param int $id Notification ID
     * @param int|null $userId Optional owner check
     * @return bool Success
     */
    public static function delete(int $id, ?int $userId = null): bool
    {
        try {
            $db = getDB();
            if ($userId !== null) {
                $stmt = $db->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
                $stmt->bind_param('ii', $id, $userId);
            } else {
                $stmt = $db->prepare('DELETE FROM notifications WHERE id = ?');
                $stmt->bind_param('i', $id);
            }
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Notification::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Count unread notifications for a user.
     *
     * @param int $userId User ID
     * @return int Unread count
     */
    public static function countUnread(int $userId): int
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0'
            );
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
            $stmt->close();
            return $total;
        } catch (Throwable $e) {
            error_log('Notification::countUnread failed: ' . $e->getMessage());
            return 0;
        }
    }
}
