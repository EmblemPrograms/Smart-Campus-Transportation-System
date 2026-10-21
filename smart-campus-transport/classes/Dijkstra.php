<?php
/**
 * Dijkstra Shortest Path Algorithm
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Represents the campus as a weighted graph where nodes are locations
 * and edges are roads with distance (metres) as weights.
 */

declare(strict_types=1);

class Dijkstra
{
    /** @var array<int, array<int, float>> Adjacency list: fromId => [toId => weight] */
    private array $graph = [];

    /** @var array<int, string> Location ID => name map */
    private array $locationNames = [];

    /** @var array<int, array> Location ID => full location row */
    private array $locations = [];

    /** @var array<int> Ordered location IDs of the last computed path */
    private array $path = [];

    /** @var float Total distance of the last computed path in metres */
    private float $distance = 0.0;

    /** @var bool Whether the last shortestPath call found a route */
    private bool $found = false;

    /**
     * Construct and optionally build the graph from the database immediately.
     *
     * @param bool $autoBuild When true, load graph from MySQL on construct
     */
    public function __construct(bool $autoBuild = true)
    {
        if ($autoBuild) {
            $this->buildGraph();
        }
    }

    /**
     * Load all active locations and roads from MySQL and build the weighted graph.
     * Bidirectional roads are added in both directions.
     *
     * @return bool True on success, false on failure
     */
    public function buildGraph(): bool
    {
        $this->graph = [];
        $this->locationNames = [];
        $this->locations = [];

        try {
            $db = getDB();

            $locStmt = $db->prepare(
                'SELECT id, name, code, description, latitude, longitude, location_type, status
                 FROM locations
                 WHERE status = ?
                 ORDER BY name ASC'
            );
            $active = 'active';
            $locStmt->bind_param('s', $active);
            $locStmt->execute();
            $locResult = $locStmt->get_result();

            while ($row = $locResult->fetch_assoc()) {
                $id = (int) $row['id'];
                $this->locations[$id] = $row;
                $this->locationNames[$id] = (string) $row['name'];
                $this->graph[$id] = [];
            }
            $locStmt->close();

            $roadStmt = $db->prepare(
                'SELECT from_location_id, to_location_id, distance_metres, is_bidirectional
                 FROM roads
                 WHERE status = ?'
            );
            $roadStmt->bind_param('s', $active);
            $roadStmt->execute();
            $roadResult = $roadStmt->get_result();

            while ($road = $roadResult->fetch_assoc()) {
                $from = (int) $road['from_location_id'];
                $to = (int) $road['to_location_id'];
                $weight = (float) $road['distance_metres'];
                $bidirectional = (int) $road['is_bidirectional'] === 1;

                $this->addEdge($from, $to, $weight);

                if ($bidirectional) {
                    $this->addEdge($to, $from, $weight);
                }
            }
            $roadStmt->close();

            return true;
        } catch (Throwable $e) {
            error_log('Dijkstra::buildGraph failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a directed weighted edge to the graph.
     *
     * @param int $from Source location ID
     * @param int $to Destination location ID
     * @param float $weight Distance weight in metres
     * @return void
     */
    public function addEdge(int $from, int $to, float $weight): void
    {
        if (!isset($this->graph[$from])) {
            $this->graph[$from] = [];
        }
        if (!isset($this->graph[$to])) {
            $this->graph[$to] = [];
        }

        // Keep the smaller weight if multiple edges exist between the same pair
        if (!isset($this->graph[$from][$to]) || $weight < $this->graph[$from][$to]) {
            $this->graph[$from][$to] = $weight;
        }
    }

    /**
     * Compute the shortest path between two locations using Dijkstra's algorithm.
     *
     * @param int $startId Starting location ID
     * @param int $endId Ending location ID
     * @return array{success:bool,path:array,distance:float,message:string} Result payload
     */
    public function shortestPath(int $startId, int $endId): array
    {
        $this->path = [];
        $this->distance = 0.0;
        $this->found = false;

        if ($startId === $endId) {
            $this->path = [$startId];
            $this->distance = 0.0;
            $this->found = true;
            return [
                'success' => true,
                'path' => $this->path,
                'distance' => 0.0,
                'message' => 'Start and end locations are the same.',
            ];
        }

        if (!isset($this->graph[$startId]) || !isset($this->graph[$endId])) {
            return [
                'success' => false,
                'path' => [],
                'distance' => 0.0,
                'message' => 'Start or end location is not in the campus graph.',
            ];
        }

        $nodes = array_keys($this->graph);
        $dist = [];
        $prev = [];
        $visited = [];

        foreach ($nodes as $node) {
            $dist[$node] = INF;
            $prev[$node] = null;
            $visited[$node] = false;
        }
        $dist[$startId] = 0.0;

        while (true) {
            $u = null;
            $minDist = INF;

            foreach ($nodes as $node) {
                if (!$visited[$node] && $dist[$node] < $minDist) {
                    $minDist = $dist[$node];
                    $u = $node;
                }
            }

            if ($u === null) {
                break;
            }

            if ($u === $endId) {
                break;
            }

            $visited[$u] = true;

            foreach ($this->graph[$u] as $v => $weight) {
                if ($visited[$v]) {
                    continue;
                }
                $alt = $dist[$u] + $weight;
                if ($alt < $dist[$v]) {
                    $dist[$v] = $alt;
                    $prev[$v] = $u;
                }
            }
        }

        if ($dist[$endId] === INF) {
            return [
                'success' => false,
                'path' => [],
                'distance' => 0.0,
                'message' => 'No path found between the selected locations.',
            ];
        }

        // Reconstruct path from end to start
        $path = [];
        $current = $endId;
        while ($current !== null) {
            array_unshift($path, $current);
            $current = $prev[$current];
        }

        $this->path = $path;
        $this->distance = (float) $dist[$endId];
        $this->found = true;

        return [
            'success' => true,
            'path' => $this->path,
            'distance' => $this->distance,
            'message' => 'Shortest path computed successfully.',
        ];
    }

    /**
     * Get the total distance of the last computed shortest path.
     *
     * @return float Distance in metres (0 if no path found)
     */
    public function getDistance(): float
    {
        return $this->distance;
    }

    /**
     * Get the last computed path as an ordered array of location IDs.
     *
     * @return array<int> Location IDs
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * Get the last computed path as an ordered array of location names.
     *
     * @return array<string> Location names
     */
    public function getPathNames(): array
    {
        $names = [];
        foreach ($this->path as $id) {
            $names[] = $this->locationNames[$id] ?? ('Location #' . $id);
        }
        return $names;
    }

    /**
     * Get detailed information about the last computed path.
     *
     * @return array{
     *   success:bool,
     *   path:array<int>,
     *   path_names:array<string>,
     *   distance:float,
     *   distance_formatted:string,
     *   hops:int,
     *   locations:array,
     *   segments:array
     * }
     */
    public function getPathDetails(): array
    {
        $segments = [];
        for ($i = 0; $i < count($this->path) - 1; $i++) {
            $from = $this->path[$i];
            $to = $this->path[$i + 1];
            $weight = $this->graph[$from][$to] ?? 0.0;
            $segments[] = [
                'from_id' => $from,
                'to_id' => $to,
                'from_name' => $this->locationNames[$from] ?? ('Location #' . $from),
                'to_name' => $this->locationNames[$to] ?? ('Location #' . $to),
                'distance' => (float) $weight,
                'distance_formatted' => formatDistance($weight),
            ];
        }

        $locationDetails = [];
        foreach ($this->path as $id) {
            $locationDetails[] = $this->locations[$id] ?? [
                'id' => $id,
                'name' => $this->locationNames[$id] ?? ('Location #' . $id),
            ];
        }

        return [
            'success' => $this->found,
            'path' => $this->path,
            'path_names' => $this->getPathNames(),
            'distance' => $this->distance,
            'distance_formatted' => formatDistance($this->distance),
            'hops' => max(0, count($this->path) - 1),
            'locations' => $locationDetails,
            'segments' => $segments,
        ];
    }

    /**
     * Get a copy of the internal adjacency list graph.
     *
     * @return array<int, array<int, float>>
     */
    public function getGraph(): array
    {
        return $this->graph;
    }

    /**
     * Get all loaded location names keyed by ID.
     *
     * @return array<int, string>
     */
    public function getLocationNames(): array
    {
        return $this->locationNames;
    }
}
