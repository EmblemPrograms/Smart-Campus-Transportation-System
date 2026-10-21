<?php
/**
 * Setting Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Key/value system settings stored in system_settings.
 */

declare(strict_types=1);

class Setting
{
    /**
     * Get a setting value by key.
     *
     * @param string $key Setting key
     * @param mixed $default Default value when key is missing
     * @return mixed Setting value or default
     */
    public static function get(string $key, $default = null)
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1'
            );
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                return $default;
            }

            return $row['setting_value'];
        } catch (Throwable $e) {
            error_log('Setting::get failed: ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * Set (insert or update) a setting value.
     *
     * @param string $key Setting key
     * @param mixed $value Setting value (stored as string/text)
     * @param string|null $description Optional description
     * @return bool Success
     */
    public static function set(string $key, $value, ?string $description = null): bool
    {
        try {
            $db = getDB();
            $stringValue = is_scalar($value) || $value === null
                ? (string) $value
                : json_encode($value);

            $existsStmt = $db->prepare(
                'SELECT id FROM system_settings WHERE setting_key = ? LIMIT 1'
            );
            $existsStmt->bind_param('s', $key);
            $existsStmt->execute();
            $exists = $existsStmt->get_result()->fetch_assoc();
            $existsStmt->close();

            if ($exists) {
                if ($description !== null) {
                    $stmt = $db->prepare(
                        'UPDATE system_settings
                         SET setting_value = ?, description = ?, updated_at = NOW()
                         WHERE setting_key = ?'
                    );
                    $stmt->bind_param('sss', $stringValue, $description, $key);
                } else {
                    $stmt = $db->prepare(
                        'UPDATE system_settings
                         SET setting_value = ?, updated_at = NOW()
                         WHERE setting_key = ?'
                    );
                    $stmt->bind_param('ss', $stringValue, $key);
                }
            } else {
                $desc = $description ?? '';
                $stmt = $db->prepare(
                    'INSERT INTO system_settings (setting_key, setting_value, description, updated_at)
                     VALUES (?, ?, ?, NOW())'
                );
                $stmt->bind_param('sss', $key, $stringValue, $desc);
            }

            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Setting::set failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all system settings as key => value map (or full rows).
     *
     * @param bool $asMap When true, return key=>value; otherwise full rows
     * @return array Settings
     */
    public static function getAll(bool $asMap = true): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT id, setting_key, setting_value, description, updated_at
                 FROM system_settings
                 ORDER BY setting_key ASC'
            );
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (!$asMap) {
                return $rows;
            }

            $map = [];
            foreach ($rows as $row) {
                $map[(string) $row['setting_key']] = $row['setting_value'];
            }
            return $map;
        } catch (Throwable $e) {
            error_log('Setting::getAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a setting by key.
     *
     * @param string $key Setting key
     * @return bool Success
     */
    public static function delete(string $key): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM system_settings WHERE setting_key = ?');
            $stmt->bind_param('s', $key);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Setting::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a setting value cast to boolean.
     *
     * @param string $key Setting key
     * @param bool $default Default value
     * @return bool
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, null);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Get a setting value cast to integer.
     *
     * @param string $key Setting key
     * @param int $default Default value
     * @return int
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key, null);
        if ($value === null || $value === '') {
            return $default;
        }
        return (int) $value;
    }
}
