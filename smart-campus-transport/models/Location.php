<?php
/**
 * Location Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * CRUD for campus map locations (graph nodes).
 */

declare(strict_types=1);

class Location
{
    /**
     * Find a location by primary key.
     *
     * @param int $id Location ID
     * @return array|null Location row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM locations WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Location::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a location by unique code.
     *
     * @param string $code Location code
     * @return array|null Location row or null
     */
    public static function findByCode(string $code): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM locations WHERE code = ? LIMIT 1');
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Location::findByCode failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a campus location.
     *
     * @param array $data Location fields
     * @return int|false New location ID or false
     */
    public static function create(array $data)
    {
        try {
            $db = getDB();
            $name = (string) ($data['name'] ?? '');
            $code = (string) ($data['code'] ?? '');
            $description = (string) ($data['description'] ?? '');
            $latitude = (float) ($data['latitude'] ?? 0);
            $longitude = (float) ($data['longitude'] ?? 0);
            $type = (string) ($data['location_type'] ?? 'other');
            $status = (string) ($data['status'] ?? 'active');

            $stmt = $db->prepare(
                'INSERT INTO locations
                    (name, code, description, latitude, longitude, location_type, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->bind_param('sssddss', $name, $code, $description, $latitude, $longitude, $type, $status);
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('Location::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a campus location.
     *
     * @param int $id Location ID
     * @param array $data Fields to update
     * @return bool Success
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = getDB();
            $name = (string) ($data['name'] ?? '');
            $code = (string) ($data['code'] ?? '');
            $description = (string) ($data['description'] ?? '');
            $latitude = (float) ($data['latitude'] ?? 0);
            $longitude = (float) ($data['longitude'] ?? 0);
            $type = (string) ($data['location_type'] ?? 'other');
            $status = (string) ($data['status'] ?? 'active');

            $stmt = $db->prepare(
                'UPDATE locations
                 SET name = ?, code = ?, description = ?, latitude = ?, longitude = ?,
                     location_type = ?, status = ?
                 WHERE id = ?'
            );
            $stmt->bind_param('sssddssi', $name, $code, $description, $latitude, $longitude, $type, $status, $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Location::update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a location.
     *
     * @param int $id Location ID
     * @return bool Success
     */
    public static function delete(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM locations WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Location::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all locations, optionally filtered by status.
     *
     * @param string|null $status Optional status filter
     * @return array List of locations
     */
    public static function all(?string $status = null): array
    {
        try {
            $db = getDB();
            if ($status !== null && $status !== '') {
                $stmt = $db->prepare(
                    'SELECT * FROM locations WHERE status = ? ORDER BY name ASC'
                );
                $stmt->bind_param('s', $status);
            } else {
                $stmt = $db->prepare('SELECT * FROM locations ORDER BY name ASC');
            }
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Location::all failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active locations only.
     *
     * @return array List of active locations
     */
    public static function getActive(): array
    {
        return self::all('active');
    }

    /**
     * Get a map of location ID => name for dropdowns and path labels.
     *
     * @return array<int, string>
     */
    public static function getNameMap(): array
    {
        $map = [];
        foreach (self::getActive() as $loc) {
            $map[(int) $loc['id']] = (string) $loc['name'];
        }
        return $map;
    }
}
