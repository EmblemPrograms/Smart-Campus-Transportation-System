<?php
/**
 * Dijkstra Unit Tests (CLI)
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Self-contained tests: builds a graph with addEdge() — no database required.
 *
 * Usage:
 *   php tests/DijkstraTest.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/classes/Dijkstra.php';

$passed = 0;
$failed = 0;

/**
 * Simple assertion helper.
 */
function assertTrue(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] {$message}\n";
        $passed++;
    } else {
        echo "[FAIL] {$message}\n";
        $failed++;
    }
}

function assertEquals($expected, $actual, string $message): void
{
    $ok = $expected === $actual;
    if (!$ok && is_float($expected) && is_float($actual)) {
        $ok = abs($expected - $actual) < 0.0001;
    }
    assertTrue($ok, $message . ' (expected=' . var_export($expected, true) . ', actual=' . var_export($actual, true) . ')');
}

function assertArrayEquals(array $expected, array $actual, string $message): void
{
    assertTrue($expected === $actual, $message . ' (expected=' . json_encode($expected) . ', actual=' . json_encode($actual) . ')');
}

echo "=== DijkstraTest (standalone graph, no DB) ===\n\n";

// -------------------------------------------------------------------------
// Graph under test (campus-like):
//   1 --450-- 2 --300-- 3
//   1 -------900------- 3
//   3 --200-- 4
//   5 (disconnected)
// Shortest 1→3 should be 1-2-3 (750), not direct 900.
// -------------------------------------------------------------------------

$d = new Dijkstra(false);

$d->addEdge(1, 2, 450.0);
$d->addEdge(2, 1, 450.0);
$d->addEdge(2, 3, 300.0);
$d->addEdge(3, 2, 300.0);
$d->addEdge(1, 3, 900.0);
$d->addEdge(3, 1, 900.0);
$d->addEdge(3, 4, 200.0);
$d->addEdge(4, 3, 200.0);
// Node 5 exists only as destination of no edges until we touch it via addEdge self-init
$d->addEdge(5, 5, 0.0); // ensure node 5 in graph but no useful external edges
// Remove self-loop effect for path tests by not using 5↔5; add orphan via empty neighbour from another approach:
// Re-build cleaner orphan: create node 6 with no outbound useful path from 1
$d->addEdge(6, 7, 100.0);
$d->addEdge(7, 6, 100.0);

// Test 1: same start and end
$r1 = $d->shortestPath(1, 1);
assertTrue($r1['success'] === true, 'Same start/end succeeds');
assertArrayEquals([1], $r1['path'], 'Same start/end path is [1]');
assertEquals(0.0, (float) $r1['distance'], 'Same start/end distance is 0');

// Test 2: shortest path chooses lower cost multi-hop
$r2 = $d->shortestPath(1, 3);
assertTrue($r2['success'] === true, 'Path 1→3 found');
assertArrayEquals([1, 2, 3], $r2['path'], 'Path 1→3 is [1,2,3] not [1,3]');
assertEquals(750.0, (float) $r2['distance'], 'Distance 1→3 is 750');

// Test 3: longer path accumulates correctly
$r3 = $d->shortestPath(1, 4);
assertTrue($r3['success'] === true, 'Path 1→4 found');
assertArrayEquals([1, 2, 3, 4], $r3['path'], 'Path 1→4 is [1,2,3,4]');
assertEquals(950.0, (float) $r3['distance'], 'Distance 1→4 is 950');

// Test 4: accessors after successful compute
assertEquals(950.0, $d->getDistance(), 'getDistance matches last result');
assertArrayEquals([1, 2, 3, 4], $d->getPath(), 'getPath matches last result');

// Test 5: unknown / unreachable from campus component
$r5 = $d->shortestPath(1, 6);
assertTrue($r5['success'] === false, 'Unreachable 1→6 fails');
assertArrayEquals([], $r5['path'], 'Unreachable path empty');

// Test 6: missing node
$r6 = $d->shortestPath(1, 99);
assertTrue($r6['success'] === false, 'Missing end node fails');

// Test 7: addEdge keeps smaller weight
$d2 = new Dijkstra(false);
$d2->addEdge(10, 11, 500.0);
$d2->addEdge(10, 11, 200.0); // better edge
$d2->addEdge(11, 10, 200.0);
$r7 = $d2->shortestPath(10, 11);
assertTrue($r7['success'] === true, 'Duplicate edge path succeeds');
assertEquals(200.0, (float) $r7['distance'], 'Smaller weight retained (200)');

// Test 8: graph structure contains expected adjacency
$graph = $d->getGraph();
assertTrue(isset($graph[1][2]) && $graph[1][2] === 450.0, 'Graph has edge 1→2 = 450');
assertTrue(isset($graph[1][3]) && $graph[1][3] === 900.0, 'Graph has edge 1→3 = 900');

echo "\n=== Summary: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
