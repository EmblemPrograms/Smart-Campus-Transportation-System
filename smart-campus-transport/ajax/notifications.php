<?php
/**
 * AJAX Notifications Endpoint
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Actions: unread, mark_read, mark_all
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
startAppSession();
requireLogin();

$action = clean($_REQUEST['action'] ?? 'unread');
$userId = (int) currentUserId();

if ($action === 'unread') {
    $notifications = Notification::getUnread($userId, 15);
    foreach ($notifications as &$n) {
        $n['time_ago'] = timeAgo((string) $n['created_at']);
    }
    unset($n);

    jsonResponse([
        'success' => true,
        'count' => Notification::countUnread($userId),
        'notifications' => $notifications,
    ]);
}

if ($action === 'mark_read') {
    requireCsrf();
    $id = (int) ($_POST['id'] ?? $_REQUEST['id'] ?? 0);
    $ok = Notification::markRead($id, $userId);
    jsonResponse([
        'success' => $ok,
        'count' => Notification::countUnread($userId),
        'message' => $ok ? 'Marked as read' : 'Failed',
    ]);
}

if ($action === 'mark_all') {
    requireCsrf();
    $ok = Notification::markAllRead($userId);
    jsonResponse([
        'success' => $ok,
        'count' => 0,
        'message' => $ok ? 'All marked as read' : 'Failed',
    ]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
