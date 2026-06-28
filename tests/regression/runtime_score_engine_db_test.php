<?php

declare(strict_types=1);

/**
 * Runtime regression test against the real plugin score engine and database tables.
 *
 * Usage:
 * php tests/regression/runtime_score_engine_db_test.php --wp-load=/absolute/path/to/wp-load.php
 *
 * Optional:
 * --fixture=score_engine_multiround_hr.json (or absolute path)
 */

const BSO_RUNTIME_TOLERANCE = 0.0001;

$rootDir = dirname(__DIR__, 2);
$goldenDir = $rootDir . '/tests/golden';

$options = parse_options($argv);
$wpLoadPath = $options['wp-load'] ?? '';
$fixtureFilter = $options['fixture'] ?? '';

if ($wpLoadPath === '') {
    fwrite(STDERR, "Missing required option --wp-load=/path/to/wp-load.php\n");
    exit(1);
}

if (!is_file($wpLoadPath)) {
    fwrite(STDERR, "wp-load.php not found at: {$wpLoadPath}\n");
    exit(1);
}

require_once $wpLoadPath;

if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress bootstrap failed. ABSPATH is not defined.\n");
    exit(1);
}

if (!class_exists('BSO_Plugin')) {
    require_once $rootDir . '/includes/class-bso-plugin.php';
}

if (!class_exists('BSO_Plugin')) {
    fwrite(STDERR, "BSO_Plugin class not found.\n");
    exit(1);
}

global $wpdb;
if (!isset($wpdb)) {
    fwrite(STDERR, "WordPress database object not available.\n");
    exit(1);
}

$files = resolve_fixtures($goldenDir, $fixtureFilter);
if (empty($files)) {
    fwrite(STDERR, "No fixtures selected.\n");
    exit(1);
}

$plugin = new BSO_Plugin();
$reflector = new ReflectionClass($plugin);
$recalculate = $reflector->getMethod('recalculate_round_scores');
$recalculate->setAccessible(true);

$hadFailures = false;

foreach ($files as $file) {
    $fixture = json_decode((string) file_get_contents($file), true);
    if (!is_array($fixture)) {
        fwrite(STDERR, "Invalid fixture JSON: {$file}\n");
        $hadFailures = true;
        continue;
    }

    $context = create_fixture_runtime_context($wpdb, $fixture);

    try {
        foreach ($context['round_sequence'] as $fixtureRoundId) {
            $actualRoundId = $context['round_map'][$fixtureRoundId];
            if (isset($context['round_base_demand'][$fixtureRoundId])) {
                upsert_game_parameter($wpdb, $context['game_id'], 'base_round_demand', (float) $context['round_base_demand'][$fixtureRoundId]);
            }
            $recalculate->invoke($plugin, $context['game_id'], $actualRoundId);
        }

        $actualRows = fetch_runtime_rows($wpdb, $context);
        $expectedRows = normalize_expected_rows($fixture['expected']['rows'] ?? array());

        $failure = compare_rows($expectedRows, $actualRows, basename($file));
        if ($failure !== '') {
            fwrite(STDERR, $failure . "\n");
            $hadFailures = true;
            continue;
        }

        echo "PASS runtime " . basename($file) . "\n";
    } catch (Throwable $e) {
        fwrite(STDERR, basename($file) . ': runtime error: ' . $e->getMessage() . "\n");
        $hadFailures = true;
    } finally {
        cleanup_fixture_runtime_context($wpdb, $context);
    }
}

if ($hadFailures) {
    fwrite(STDERR, "Runtime regression test failed.\n");
    exit(1);
}

echo 'All runtime regression tests passed (' . count($files) . ").\n";
exit(0);

function parse_options(array $argv): array
{
    $options = array();

    foreach ($argv as $arg) {
        if (strpos($arg, '--') !== 0) {
            continue;
        }

        $parts = explode('=', substr($arg, 2), 2);
        $key = $parts[0] ?? '';
        $value = $parts[1] ?? '1';
        if ($key !== '') {
            $options[$key] = $value;
        }
    }

    return $options;
}

function resolve_fixtures(string $goldenDir, string $fixtureFilter): array
{
    if ($fixtureFilter !== '') {
        if (is_file($fixtureFilter)) {
            return array($fixtureFilter);
        }

        $candidate = rtrim($goldenDir, '/') . '/' . ltrim($fixtureFilter, '/');
        if (is_file($candidate)) {
            return array($candidate);
        }

        return array();
    }

    $files = glob($goldenDir . '/*.json');
    if (!$files) {
        return array();
    }

    sort($files);
    return $files;
}

function create_fixture_runtime_context($wpdb, array $fixture): array
{
    $gameId = allocate_runtime_game_id($wpdb);
    $name = 'Runtime Test Game ' . $gameId;

    $gamesTable = $wpdb->prefix . 'bso_games';
    $roundsTable = $wpdb->prefix . 'bso_game_rounds';
    $orgsTable = $wpdb->prefix . 'bso_organizations';
    $commitmentsTable = $wpdb->prefix . 'bso_commitments';
    $hrTable = $wpdb->prefix . 'bso_hr_requests';
    $paramsTable = $wpdb->prefix . 'bso_game_parameters';

    $wpdb->insert(
        $gamesTable,
        array(
            'id' => $gameId,
            'name' => $name,
            'description' => 'Runtime regression fixture execution context',
            'status' => 'active',
        ),
        array('%d', '%s', '%s', '%s')
    );

    $orgMap = array();
    $insertedOrgIds = array();
    $fixtureOrgIds = collect_fixture_organization_ids($fixture);
    foreach ($fixtureOrgIds as $fixtureOrgId) {
        $wpdb->insert(
            $orgsTable,
            array(
                'game_id' => $gameId,
                'name' => 'Fixture Org ' . $fixtureOrgId,
                'status' => 'active',
            ),
            array('%d', '%s', '%s')
        );
        $actualOrgId = (int) $wpdb->insert_id;
        $orgMap[(int) $fixtureOrgId] = $actualOrgId;
        $insertedOrgIds[] = $actualOrgId;
    }

    $roundMap = array();
    $roundSequence = array();
    $roundBaseDemand = array();
    foreach (($fixture['rounds'] ?? array()) as $round) {
        $fixtureRoundId = (int) ($round['round_id'] ?? 0);
        $turnNumber = (int) ($round['turn_number'] ?? 0);
        $baseRoundDemand = (float) ($round['base_round_demand'] ?? 100000.0);
        if ($fixtureRoundId <= 0 || $turnNumber <= 0) {
            continue;
        }

        $wpdb->insert(
            $roundsTable,
            array(
                'game_id' => $gameId,
                'turn_number' => $turnNumber,
                'status' => 'closed',
            ),
            array('%d', '%d', '%s')
        );
        $actualRoundId = (int) $wpdb->insert_id;
        $roundMap[$fixtureRoundId] = $actualRoundId;
        $roundSequence[] = $fixtureRoundId;
        $roundBaseDemand[$fixtureRoundId] = $baseRoundDemand;
    }

    sort($roundSequence);

    foreach (($fixture['parameters'] ?? array()) as $name => $value) {
        $wpdb->insert(
            $paramsTable,
            array(
                'game_id' => $gameId,
                'variable_name' => (string) $name,
                'numeric_value' => (float) $value,
            ),
            array('%d', '%s', '%f')
        );
    }

    $initialEmployees = $fixture['initial_employees'] ?? array();
    $firstFixtureRoundId = !empty($roundSequence) ? (int) $roundSequence[0] : 0;
    if ($firstFixtureRoundId > 0) {
        $anchorRoundId = $roundMap[$firstFixtureRoundId] ?? 0;
        foreach ($initialEmployees as $fixtureOrgId => $employees) {
            $fixtureOrgId = (int) $fixtureOrgId;
            if (!isset($orgMap[$fixtureOrgId])) {
                continue;
            }

            $actualOrgId = $orgMap[$fixtureOrgId];
            $wpdb->insert(
                $commitmentsTable,
                array(
                    'game_id' => $gameId,
                    'round_id' => max(1, $anchorRoundId - 1),
                    'organization_id' => $actualOrgId,
                    'theme' => 'A',
                    'price_jeans' => 0,
                    'distribution_form' => 'seed',
                    'total_employees' => max(0, (int) $employees),
                    'formula_version' => 'v1',
                ),
                array('%d', '%d', '%d', '%s', '%f', '%s', '%d', '%s')
            );
        }
    }

    foreach (($fixture['approved_resignations'] ?? array()) as $request) {
        $fixtureOrgId = (int) ($request['organization_id'] ?? 0);
        $effectiveTurn = (int) ($request['effective_round'] ?? 0);
        $requestedCount = max(0, (int) ($request['requested_count'] ?? 0));
        if ($fixtureOrgId <= 0 || $effectiveTurn <= 0 || !isset($orgMap[$fixtureOrgId])) {
            continue;
        }

        $sourceFixtureRoundId = find_round_id_by_turn_number($fixture, $effectiveTurn - 1);
        $sourceRoundId = $sourceFixtureRoundId > 0 && isset($roundMap[$sourceFixtureRoundId])
            ? (int) $roundMap[$sourceFixtureRoundId]
            : (int) reset($roundMap);

        $wpdb->insert(
            $hrTable,
            array(
                'game_id' => $gameId,
                'round_id' => max(1, $sourceRoundId),
                'organization_id' => $orgMap[$fixtureOrgId],
                'request_type' => 'resignation',
                'requested_count' => $requestedCount,
                'effective_round' => $effectiveTurn,
                'status' => 'approved',
                'decision_note' => 'Runtime fixture seed',
            ),
            array('%d', '%d', '%d', '%s', '%d', '%d', '%s', '%s')
        );
    }

    foreach (($fixture['rounds'] ?? array()) as $round) {
        $fixtureRoundId = (int) ($round['round_id'] ?? 0);
        if (!isset($roundMap[$fixtureRoundId])) {
            continue;
        }

        $actualRoundId = $roundMap[$fixtureRoundId];
        foreach (($round['commitments'] ?? array()) as $c) {
            $fixtureOrgId = (int) ($c['organization_id'] ?? 0);
            if ($fixtureOrgId <= 0 || !isset($orgMap[$fixtureOrgId])) {
                continue;
            }

            $wpdb->insert(
                $commitmentsTable,
                array(
                    'game_id' => $gameId,
                    'round_id' => $actualRoundId,
                    'organization_id' => $orgMap[$fixtureOrgId],
                    'theme' => strtoupper((string) ($c['theme'] ?? 'A')),
                    'price_jeans' => (float) ($c['price_jeans'] ?? 0),
                    'advertisement_tv' => (int) ($c['advertisement_tv'] ?? 0),
                    'advertisement_newspaper' => (int) ($c['advertisement_newspaper'] ?? 0),
                    'advertisement_family_weekly' => (int) ($c['advertisement_family_weekly'] ?? 0),
                    'advertisement_luxury_weekly' => (int) ($c['advertisement_luxury_weekly'] ?? 0),
                    'marketing_research' => (int) ($c['marketing_research'] ?? 0),
                    'production_segment_1' => (int) ($c['production_segment_1'] ?? 0),
                    'production_segment_2' => (int) ($c['production_segment_2'] ?? 0),
                    'production_segment_3' => (int) ($c['production_segment_3'] ?? 0),
                    'distribution_form' => 'fixture',
                    'hiring_staff' => (int) ($c['hiring_staff'] ?? 0),
                    'layoff_staff' => (int) ($c['layoff_staff'] ?? 0),
                    'formula_version' => 'v1',
                ),
                array(
                    '%d','%d','%d','%s','%f','%d','%d','%d','%d','%d','%d','%d','%d','%s','%d','%d','%s'
                )
            );
        }
    }

    return array(
        'game_id' => $gameId,
        'round_map' => $roundMap,
        'org_map' => $orgMap,
        'round_sequence' => $roundSequence,
        'round_base_demand' => $roundBaseDemand,
    );
}

function upsert_game_parameter($wpdb, int $gameId, string $variableName, float $numericValue): void
{
    $paramsTable = $wpdb->prefix . 'bso_game_parameters';

    $existingId = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$paramsTable} WHERE game_id = %d AND variable_name = %s LIMIT 1",
            $gameId,
            $variableName
        )
    );

    if ($existingId > 0) {
        $wpdb->update(
            $paramsTable,
            array('numeric_value' => $numericValue),
            array('id' => $existingId),
            array('%f'),
            array('%d')
        );
        return;
    }

    $wpdb->insert(
        $paramsTable,
        array(
            'game_id' => $gameId,
            'variable_name' => $variableName,
            'numeric_value' => $numericValue,
        ),
        array('%d', '%s', '%f')
    );
}

function allocate_runtime_game_id($wpdb): int
{
    $gamesTable = $wpdb->prefix . 'bso_games';
    $maxId = (int) $wpdb->get_var("SELECT COALESCE(MAX(id), 0) FROM {$gamesTable}");
    return $maxId + 1000;
}

function collect_fixture_organization_ids(array $fixture): array
{
    $ids = array();
    foreach (($fixture['rounds'] ?? array()) as $round) {
        foreach (($round['commitments'] ?? array()) as $commitment) {
            $id = (int) ($commitment['organization_id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
    }

    $keys = array_keys($ids);
    sort($keys);
    return $keys;
}

function find_round_id_by_turn_number(array $fixture, int $turnNumber): int
{
    foreach (($fixture['rounds'] ?? array()) as $round) {
        if ((int) ($round['turn_number'] ?? 0) === $turnNumber) {
            return (int) ($round['round_id'] ?? 0);
        }
    }

    return 0;
}

function fetch_runtime_rows($wpdb, array $context): array
{
    $scoresTable = $wpdb->prefix . 'bso_round_scores';
    $roundsTable = $wpdb->prefix . 'bso_game_rounds';
    $commitmentsTable = $wpdb->prefix . 'bso_commitments';

    $inverseRoundMap = array_flip($context['round_map']);
    $inverseOrgMap = array_flip($context['org_map']);

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT s.round_id, r.turn_number, s.organization_id, s.rank_position, s.turnover, s.profit, s.market_index, s.cumulative_score,
                    c.total_employees, c.sale
             FROM {$scoresTable} s
             INNER JOIN {$roundsTable} r ON r.id = s.round_id
             INNER JOIN {$commitmentsTable} c
                 ON c.game_id = s.game_id AND c.round_id = s.round_id AND c.organization_id = s.organization_id
             WHERE s.game_id = %d
             ORDER BY r.turn_number ASC, s.organization_id ASC",
            $context['game_id']
        ),
        ARRAY_A
    );

    $output = array();
    foreach ($rows as $row) {
        $actualRoundId = (int) $row['round_id'];
        $actualOrgId = (int) $row['organization_id'];

        if (!isset($inverseRoundMap[$actualRoundId]) || !isset($inverseOrgMap[$actualOrgId])) {
            continue;
        }

        $output[] = array(
            'round_id' => (int) $inverseRoundMap[$actualRoundId],
            'turn_number' => (int) $row['turn_number'],
            'organization_id' => (int) $inverseOrgMap[$actualOrgId],
            'rank_position' => $row['rank_position'] === null ? null : (int) $row['rank_position'],
            'turnover' => (float) $row['turnover'],
            'profit' => (float) $row['profit'],
            'market_index' => round((float) $row['market_index'], 6),
            'cumulative_score' => (float) $row['cumulative_score'],
            'total_employees' => (int) $row['total_employees'],
            'sale' => (int) $row['sale'],
        );
    }

    return $output;
}

function normalize_expected_rows(array $rows): array
{
    $normalized = array();
    foreach ($rows as $row) {
        $normalized[] = array(
            'round_id' => (int) ($row['round_id'] ?? 0),
            'turn_number' => (int) ($row['turn_number'] ?? 0),
            'organization_id' => (int) ($row['organization_id'] ?? 0),
            'rank_position' => isset($row['rank_position']) ? (int) $row['rank_position'] : null,
            'turnover' => (float) ($row['turnover'] ?? 0),
            'profit' => (float) ($row['profit'] ?? 0),
            'market_index' => round((float) ($row['market_index'] ?? 0), 6),
            'cumulative_score' => (float) ($row['cumulative_score'] ?? 0),
            'total_employees' => (int) ($row['total_employees'] ?? 0),
            'sale' => (int) ($row['sale'] ?? 0),
        );
    }
    return $normalized;
}

function compare_rows(array $expectedRows, array $actualRows, string $fixtureName): string
{
    if (count($expectedRows) !== count($actualRows)) {
        return "{$fixtureName}: row count mismatch expected=" . count($expectedRows) . " actual=" . count($actualRows);
    }

    $keys = array(
        'round_id',
        'turn_number',
        'organization_id',
        'rank_position',
        'turnover',
        'profit',
        'market_index',
        'cumulative_score',
        'total_employees',
        'sale',
    );

    foreach ($expectedRows as $i => $expected) {
        $actual = $actualRows[$i] ?? null;
        if (!is_array($actual)) {
            return "{$fixtureName}: missing actual row at index {$i}";
        }

        foreach ($keys as $key) {
            if (!array_key_exists($key, $expected) || !array_key_exists($key, $actual)) {
                return "{$fixtureName}: missing key {$key} at row {$i}";
            }

            if ($expected[$key] === null || $actual[$key] === null) {
                if ($expected[$key] !== $actual[$key]) {
                    return "{$fixtureName}: mismatch row {$i}, key {$key}, expected=null actual=" . var_export($actual[$key], true);
                }
                continue;
            }

            if (is_numeric($expected[$key]) || is_numeric($actual[$key])) {
                $e = (float) $expected[$key];
                $a = (float) $actual[$key];
                if (abs($e - $a) > BSO_RUNTIME_TOLERANCE) {
                    return "{$fixtureName}: mismatch row {$i}, key {$key}, expected={$e} actual={$a}";
                }
                continue;
            }

            if ((string) $expected[$key] !== (string) $actual[$key]) {
                return "{$fixtureName}: mismatch row {$i}, key {$key}, expected={$expected[$key]} actual={$actual[$key]}";
            }
        }
    }

    return '';
}

function cleanup_fixture_runtime_context($wpdb, array $context): void
{
    $gameId = (int) ($context['game_id'] ?? 0);
    if ($gameId <= 0) {
        return;
    }

    $gameScopedTables = array(
        $wpdb->prefix . 'bso_round_scores',
        $wpdb->prefix . 'bso_commitments',
        $wpdb->prefix . 'bso_hr_requests',
        $wpdb->prefix . 'bso_game_parameters',
        $wpdb->prefix . 'bso_game_rounds',
        $wpdb->prefix . 'bso_organizations',
    );

    foreach ($gameScopedTables as $table) {
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE game_id = %d", $gameId));
    }

    $gamesTable = $wpdb->prefix . 'bso_games';
    $wpdb->query($wpdb->prepare("DELETE FROM {$gamesTable} WHERE id = %d", $gameId));
}
