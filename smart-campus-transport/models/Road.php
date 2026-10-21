<?php
/**
 * Road Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * CRUD for roads between campus locations (graph edges).
 */

declare(strict_types=1);

class Road
{
    /**
     * Find a road by primary key with location names.
     *
     * @param int $id Road ID
     * @return array|null Road row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT r.*,
                        fl.name AS from_name, fl.code AS from_code,
                        tl.name AS to_name, tl.code AS to_code
                 FROM roads r
                 INNER JOIN locations fl ON fl.id = r.from_location_id
                 INNER JOIN locations tl ON tl.id = r.to_location_id
                 WHERE r.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Road::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a road between two locations.
     *
     * @param array $data Road fields
     * @return int|false New road ID or false
     */
    public static function create(array $data)
    {
        try {
            $db = getDB();
            $from = (int) ($data['from_location_id'] ?? 0);
            $to = (int) ($data['to_location_id'] ?? 0);
            $distance = (float) ($data['distance_metres'] ?? 0);
            $travelTime = (int) ($data['travel_time_minutes'] ?? 0);
            $bidirectional = (int) ($data['is_bidirectional'] ?? 1);
            $status = (string) ($data['status'] ?? 'active');

            $stmt = $db->prepare(
                'INSERT INTO roads
                    (from_location_id, to_location_id, distance_metres, travel_time_minutes,
                     is_bidirectional, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->bind_param('iidiis', $from, $to, $distance, $travelTime, $bidirectional, $status);
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('Road::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a road record.
     *
     * @param int $id Road ID
     * @param array $data Fields to update
     * @return bool Success
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = getDB();
            $from = (int) ($data['from_location_id'] ?? 0);
            $to = (int) ($data['to_location_id'] ?? 0);
            $distance = (float) ($data['distance_metres'] ?? 0);
            $travelTime = (int) ($data['travel_time_minutes'] ?? 0);
            $bidirectional = (int) ($data['is_bidirectional'] ?? 1);
            $status = (string) ($data['status'] ?? 'active');

            $stmt = $db->prepare(
                'UPDATE roads
                 SET from_location_id = ?, to_location_id = ?, distance_metres = ?,
                     travel_time_minutes = ?, is_bidirectional = ?, status = ?
                 WHERE id = ?'
            );
            $stmt->bind_param('iidiisi', $from, $to, $distance, $travelTime, $bidirectional, $status, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Road::update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a road.
     *
     * @param int $id Road ID
     * @return bool Success
     */
    public static function delete(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM roads WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Road::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all roads with location names.
     *
     * @param string|null $status Optional status filter
     * @return array List of roads
     */
    public static function all(?string $status = null): array
    {
        try {
            $db = getDB();
            if ($status !== null && $status !== '') {
                $stmt = $db->prepare(
                    'SELECT r.*,
                            fl.name AS from_name, tl.name AS to_name
                     FROM roads r
                     INNER JOIN locations fl ON fl.id = r.from_location_id
                     INNER JOIN locations tl ON tl.id = r.to_location_id
                     WHERE r.status = ?
                     ORDER BY fl.name ASC, tl.name ASC'
                );
                $stmt->bind_param('s', $status);
            } else {
                $stmt = $db->prepare(
                    'SELECT r.*,
                            fl.name AS from_name, tl.name AS to_name
                     FROM roads r
                     INNER JOIN locations fl ON fl.id = r.from_location_id
                     INNER JOIN locations tl ON tl.id = r.to_location_id
                     ORDER BY fl.name ASC, tl.name ASC'
                );
            }
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Road::all failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active graph edges for Dijkstra algorithm consumption.
     * Expands bidirectional roads into two directed edges.
     *
     * @return array List of edges with from, to, weight
     */
    public static function getGraphEdges(): array
    {
        $edges = [];

        try {
            $db = getDB();
            $status = 'active';
            $stmt = $db->prepare(
                'SELECT from_location_id, to_location_id, distance_metres, is_bidirectional
                 FROM roads WHERE status = ?'
            );
            $stmt->bind_param('s', $status);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $from = (int) $row['from_location_id'];
                $to = (int) $row['to_location_id'];
                $weight = (float) $row['distance_metres'];

                $edges[] = [
                    'from' => $from,
                    'to' => $to,
                    'weight' => $weight,
                ];

                if ((int) $row['is_bidirectional'] === 1) {
                    $edges[] = [
                        'from' => $to,
                        'to' => $from,
                        'weight' => $weight,
                    ];
                }
            }
            $stmt->close();
        } catch (Throwable $e) {
            error_log('Road::getGraphEdges failed: ' . $e->getMessage());
        }

        return $edges;
    }
}
