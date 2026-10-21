<?php
/**
 * Route Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * CRUD for optimized campus transport routes.
 */

declare(strict_types=1);

class Route
{
    /**
     * Find a route by primary key with location names.
     *
     * @param int $id Route ID
     * @return array|null Route row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT r.*,
                        sl.name AS start_name, el.name AS end_name
                 FROM routes r
                 INNER JOIN locations sl ON sl.id = r.start_location_id
                 INNER JOIN locations el ON el.id = r.end_location_id
                 WHERE r.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Route::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a route by unique code.
     *
     * @param string $code Route code
     * @return array|null Route row or null
     */
    public static function findByCode(string $code): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM routes WHERE code = ? LIMIT 1');
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Route::findByCode failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a route record.
     *
     * @param array $data Route fields
     * @return int|false New route ID or false
     */
    public static function create(array $data)
    {
        try {
            $db = getDB();
            $name = (string) ($data['name'] ?? '');
            $code = (string) ($data['code'] ?? '');
            $startId = (int) ($data['start_location_id'] ?? 0);
            $endId = (int) ($data['end_location_id'] ?? 0);
            $pathJson = is_array($data['path_json'] ?? null)
                ? json_encode($data['path_json'])
                : (string) ($data['path_json'] ?? '[]');
            $totalDistance = (float) ($data['total_distance'] ?? 0);
            $estimatedTime = (int) ($data['estimated_time'] ?? 0);
            $description = (string) ($data['description'] ?? '');
            $status = (string) ($data['status'] ?? 'active');

            $stmt = $db->prepare(
                'INSERT INTO routes
                    (name, code, start_location_id, end_location_id, path_json,
                     total_distance, estimated_time, description, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->bind_param(
                'ssiisdiss',
                $name,
                $code,
                $startId,
                $endId,
                $pathJson,
                $totalDistance,
                $estimatedTime,
                $description,
                $status
            );
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('Route::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a route record.
     *
     * @param int $id Route ID
     * @param array $data Fields to update
     * @return bool Success
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = getDB();
            $name = (string) ($data['name'] ?? '');
            $code = (string) ($data['code'] ?? '');
            $startId = (int) ($data['start_location_id'] ?? 0);
            $endId = (int) ($data['end_location_id'] ?? 0);
            $pathJson = is_array($data['path_json'] ?? null)
                ? json_encode($data['path_json'])
                : (string) ($data['path_json'] ?? '[]');
            $totalDistance = (float) ($data['total_distance'] ?? 0);
            $estimatedTime = (int) ($data['estimated_time'] ?? 0);
            $description = (string) ($data['description'] ?? '');
            $status = (string) ($data['status'] ?? 'active');

            $stmt = $db->prepare(
                'UPDATE routes
                 SET name = ?, code = ?, start_location_id = ?, end_location_id = ?, path_json = ?,
                     total_distance = ?, estimated_time = ?, description = ?, status = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->bind_param(
                'ssiisdissi',
                $name,
                $code,
                $startId,
                $endId,
                $pathJson,
                $totalDistance,
                $estimatedTime,
                $description,
                $status,
                $id
            );
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Route::update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Compute and save an optimized path using Dijkstra between start and end locations.
     *
     * @param array $data Route metadata plus start_location_id and end_location_id
     * @return int|false New or updated route ID, or false on failure
     */
    public static function saveOptimizedPath(array $data)
    {
        $startId = (int) ($data['start_location_id'] ?? 0);
        $endId = (int) ($data['end_location_id'] ?? 0);

        $dijkstra = new Dijkstra(true);
        $result = $dijkstra->shortestPath($startId, $endId);

        if (!$result['success']) {
            error_log('Route::saveOptimizedPath: ' . $result['message']);
            return false;
        }

        $details = $dijkstra->getPathDetails();
        $estimatedTime = max(1, (int) ceil($details['distance'] / 250)); // ~15 km/h campus speed

        $payload = [
            'name' => (string) ($data['name'] ?? ($details['path_names'][0] . ' → ' . end($details['path_names']))),
            'code' => (string) ($data['code'] ?? ('RT-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)))),
            'start_location_id' => $startId,
            'end_location_id' => $endId,
            'path_json' => $details['path'],
            'total_distance' => $details['distance'],
            'estimated_time' => (int) ($data['estimated_time'] ?? $estimatedTime),
            'description' => (string) ($data['description'] ?? implode(' → ', $details['path_names'])),
            'status' => (string) ($data['status'] ?? 'active'),
        ];

        if (!empty($data['id'])) {
            $ok = self::update((int) $data['id'], $payload);
            return $ok ? (int) $data['id'] : false;
        }

        return self::create($payload);
    }

    /**
     * Delete a route.
     *
     * @param int $id Route ID
     * @return bool Success
     */
    public static function delete(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM routes WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Route::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all routes with location names.
     *
     * @param string|null $status Optional status filter
     * @return array List of routes
     */
    public static function all(?string $status = null): array
    {
        try {
            $db = getDB();
            if ($status !== null && $status !== '') {
                $stmt = $db->prepare(
                    'SELECT r.*, sl.name AS start_name, el.name AS end_name
                     FROM routes r
                     INNER JOIN locations sl ON sl.id = r.start_location_id
                     INNER JOIN locations el ON el.id = r.end_location_id
                     WHERE r.status = ?
                     ORDER BY r.name ASC'
                );
                $stmt->bind_param('s', $status);
            } else {
                $stmt = $db->prepare(
                    'SELECT r.*, sl.name AS start_name, el.name AS end_name
                     FROM routes r
                     INNER JOIN locations sl ON sl.id = r.start_location_id
                     INNER JOIN locations el ON el.id = r.end_location_id
                     ORDER BY r.name ASC'
                );
            }
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Route::all failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Decode path_json into an array of location IDs.
     *
     * @param array $route Route row
     * @return array<int> Location IDs
     */
    public static function getPathIds(array $route): array
    {
        $raw = $route['path_json'] ?? '[]';
        if (is_array($raw)) {
            return array_map('intval', $raw);
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }
}
