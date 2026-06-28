<?php

declare(strict_types=1);

/**
 * Standalone regression test for BSO score formulas using golden fixtures.
 *
 * Usage:
 * - php tests/regression/score_engine_golden_test.php
 * - php tests/regression/score_engine_golden_test.php --formula-version=v1
 * - php tests/regression/score_engine_golden_test.php --update-golden --formula-version=v1 --accept-golden-update=yes --update-reason="why"
 */

const BSO_TOLERANCE = 0.0001;

$rootDir = dirname(__DIR__, 2);
$goldenDir = $rootDir . '/tests/golden';
$options = parse_options($argv);

$updateGolden = array_key_exists('update-golden', $options);
$formulaVersion = isset($options['formula-version']) ? trim((string) $options['formula-version']) : 'v1';
$acceptGoldenUpdate = isset($options['accept-golden-update']) && $options['accept-golden-update'] === 'yes';
$updateReason = isset($options['update-reason']) ? trim((string) $options['update-reason']) : '';

if ($formulaVersion === '') {
    fwrite(STDERR, "Option --formula-version mag niet leeg zijn.\n");
    exit(1);
}

if ($updateGolden) {
    if (!$acceptGoldenUpdate) {
        fwrite(STDERR, "Gebruik voor golden updates: --accept-golden-update=yes\n");
        exit(1);
    }

    if ($updateReason === '') {
        fwrite(STDERR, "Gebruik voor golden updates: --update-reason=\"...\"\n");
        exit(1);
    }
}

$files = glob($goldenDir . '/*.json');
if (!$files) {
    fwrite(STDERR, "No golden fixtures found in tests/golden\n");
    exit(1);
}

$hadFailures = false;
$fixtureCount = 0;

foreach ($files as $file) {
    $fixtureCount++;
    $fixture = json_decode((string) file_get_contents($file), true);
    if (!is_array($fixture)) {
        fwrite(STDERR, "Invalid JSON fixture: {$file}\n");
        $hadFailures = true;
        continue;
    }

    $metaError = validate_fixture_meta($fixture, $formulaVersion, $file);
    if ($metaError !== '') {
        fwrite(STDERR, $metaError . "\n");
        $hadFailures = true;
        continue;
    }

    $computed = compute_expected_rows($fixture);

    if ($updateGolden) {
        $fixture['meta']['last_updated_at'] = gmdate('c');
        $fixture['meta']['last_update_reason'] = $updateReason;
        $fixture['meta']['updated_by'] = 'score_engine_golden_test.php';
        $fixture['expected'] = array('rows' => $computed);
        file_put_contents($file, json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        echo "UPDATED {$file}\n";
        continue;
    }

    $expectedRows = $fixture['expected']['rows'] ?? null;
    if (!is_array($expectedRows)) {
        fwrite(STDERR, "Missing expected.rows in fixture: {$file}\n");
        $hadFailures = true;
        continue;
    }

    $failure = compare_rows($expectedRows, $computed, $file);
    if ($failure !== '') {
        fwrite(STDERR, $failure . "\n");
        $hadFailures = true;
    } else {
        echo "PASS {$file}\n";
    }
}

if ($updateGolden) {
    echo "Golden fixtures updated ({$fixtureCount}).\n";
    exit(0);
}

if ($hadFailures) {
    fwrite(STDERR, "Regression test failed.\n");
    exit(1);
}

echo "All golden regression tests passed ({$fixtureCount}).\n";
exit(0);

function parse_options(array $argv): array
{
    $options = array();

    foreach ($argv as $arg) {
        if (strpos($arg, '--') !== 0) {
            continue;
        }

        $token = substr($arg, 2);
        if ($token === '') {
            continue;
        }

        if (strpos($token, '=') === false) {
            $options[$token] = '1';
            continue;
        }

        $parts = explode('=', $token, 2);
        $key = $parts[0] ?? '';
        $value = $parts[1] ?? '';
        if ($key !== '') {
            $options[$key] = $value;
        }
    }

    return $options;
}

function validate_fixture_meta(array $fixture, string $formulaVersion, string $file): string
{
    $meta = $fixture['meta'] ?? null;
    if (!is_array($meta)) {
        return "{$file}: missing meta block";
    }

    $fixtureVersion = isset($meta['formula_version']) ? (string) $meta['formula_version'] : '';
    if ($fixtureVersion === '') {
        return "{$file}: meta.formula_version ontbreekt";
    }

    if ($fixtureVersion !== $formulaVersion) {
        return "{$file}: formula_version mismatch fixture={$fixtureVersion} runner={$formulaVersion}";
    }

    return '';
}

function compare_rows(array $expectedRows, array $actualRows, string $file): string
{
    if (count($expectedRows) !== count($actualRows)) {
        return "{$file}: row count mismatch expected=" . count($expectedRows) . " actual=" . count($actualRows);
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
            return "{$file}: missing actual row at index {$i}";
        }

        foreach ($keys as $key) {
            if (!array_key_exists($key, $expected) || !array_key_exists($key, $actual)) {
                return "{$file}: missing key {$key} at row {$i}";
            }

            if (is_numeric($expected[$key]) || is_numeric($actual[$key])) {
                $e = (float) $expected[$key];
                $a = (float) $actual[$key];
                if (abs($e - $a) > BSO_TOLERANCE) {
                    return "{$file}: mismatch row {$i}, key {$key}, expected={$e} actual={$a}";
                }
                continue;
            }

            if ((string) $expected[$key] !== (string) $actual[$key]) {
                return "{$file}: mismatch row {$i}, key {$key}, expected={$expected[$key]} actual={$actual[$key]}";
            }
        }
    }

    return '';
}

function compute_expected_rows(array $fixture): array
{
    $parameters = $fixture['parameters'] ?? array();
    $targetPrices = array(
        'A' => (float) ($parameters['target_price_theme_a'] ?? 75.0),
        'B' => (float) ($parameters['target_price_theme_b'] ?? 95.0),
        'C' => (float) ($parameters['target_price_theme_c'] ?? 115.0),
    );

    $productionCost = (float) ($parameters['production_cost'] ?? 15.0);
    $hiringCost = (float) ($parameters['hiring_cost'] ?? 50.0);
    $layoffCost = (float) ($parameters['layoff_cost'] ?? 20.0);
    $defaultEmployees = (int) ($parameters['default_employees'] ?? 10);

    $initialEmployees = $fixture['initial_employees'] ?? array();
    $approvedResignations = $fixture['approved_resignations'] ?? array();

    $previousEmployees = array();
    $cumulativeScores = array();
    $outputRows = array();

    foreach (($fixture['rounds'] ?? array()) as $round) {
        $roundId = (int) ($round['round_id'] ?? 0);
        $turnNumber = (int) ($round['turn_number'] ?? 0);
        $baseRoundDemand = (float) ($round['base_round_demand'] ?? 100000.0);
        $commitments = $round['commitments'] ?? array();

        if ($roundId <= 0 || $turnNumber <= 0 || !is_array($commitments) || $commitments === array()) {
            continue;
        }

        $attractiveness = array();
        $sumAttr = 0.0;

        foreach ($commitments as $c) {
            $orgId = (int) ($c['organization_id'] ?? 0);
            if ($orgId <= 0) {
                continue;
            }

            $theme = strtoupper((string) ($c['theme'] ?? 'A'));
            if (!isset($targetPrices[$theme])) {
                $theme = 'A';
            }

            $price = (float) ($c['price_jeans'] ?? 0.0);
            $targetPrice = max(1.0, $targetPrices[$theme]);
            $difference = abs($price - $targetPrice);
            $priceEffect = 1.0 / (1.0 + ($difference / $targetPrice));

            $mediaTotal = (float) ($c['advertisement_tv'] ?? 0)
                + (float) ($c['advertisement_newspaper'] ?? 0)
                + (float) ($c['advertisement_family_weekly'] ?? 0)
                + (float) ($c['advertisement_luxury_weekly'] ?? 0);
            $adFactor = 1.0 + ($mediaTotal / 1000.0);

            $attr = max(0.0001, $priceEffect * $adFactor);
            $attractiveness[$orgId] = array(
                'attr' => $attr,
                'media_total' => $mediaTotal,
            );
            $sumAttr += $attr;
        }

        if ($sumAttr <= 0.0) {
            $sumAttr = 1.0;
        }

        $roundRows = array();
        foreach ($commitments as $c) {
            $orgId = (int) ($c['organization_id'] ?? 0);
            if ($orgId <= 0 || !isset($attractiveness[$orgId])) {
                continue;
            }

            $productionTotal = (int) ($c['production_segment_1'] ?? 0)
                + (int) ($c['production_segment_2'] ?? 0)
                + (int) ($c['production_segment_3'] ?? 0);

            $baseEmployees = isset($previousEmployees[$orgId])
                ? (int) $previousEmployees[$orgId]
                : (int) ($initialEmployees[(string) $orgId] ?? $defaultEmployees);

            $resignationEffect = 0;
            foreach ($approvedResignations as $request) {
                if ((int) ($request['organization_id'] ?? 0) !== $orgId) {
                    continue;
                }
                if ((int) ($request['effective_round'] ?? 0) !== $turnNumber) {
                    continue;
                }
                $resignationEffect += max(0, (int) ($request['requested_count'] ?? 0));
            }

            $hiring = (int) ($c['hiring_staff'] ?? 0);
            $layoff = (int) ($c['layoff_staff'] ?? 0);

            $totalEmployees = max(0, $baseEmployees + $hiring - $layoff - $resignationEffect);
            $maxProductionCapacity = $totalEmployees * 2500;
            $effectiveProduction = min($productionTotal, $maxProductionCapacity);

            $marketIndex = $attractiveness[$orgId]['attr'] / $sumAttr;
            $potentialSale = (int) round($baseRoundDemand * $marketIndex);
            $sale = min($effectiveProduction, $potentialSale);

            $price = (float) ($c['price_jeans'] ?? 0.0);
            $turnover = round($sale * $price, 2);

            $mediaTotal = (float) $attractiveness[$orgId]['media_total'];
            $marketingResearch = (float) ($c['marketing_research'] ?? 0);
            $staffCost = ($hiring * $hiringCost) + ($layoff * $layoffCost);
            $productionCostTotal = $effectiveProduction * $productionCost;
            $totalAmount = round($productionCostTotal + $mediaTotal + $staffCost + $marketingResearch, 2);
            $profit = round($turnover - $totalAmount, 2);

            $previousCumulative = (float) ($cumulativeScores[$orgId] ?? 0.0);
            $cumulativeScore = round($previousCumulative + $profit, 4);

            $roundRows[] = array(
                'round_id' => $roundId,
                'turn_number' => $turnNumber,
                'organization_id' => $orgId,
                'rank_position' => 0,
                'turnover' => $turnover,
                'profit' => $profit,
                'market_index' => round($marketIndex, 6),
                'cumulative_score' => $cumulativeScore,
                'total_employees' => $totalEmployees,
                'sale' => $sale,
            );

            $previousEmployees[$orgId] = $totalEmployees;
            $cumulativeScores[$orgId] = $cumulativeScore;
        }

        usort($roundRows, static function (array $a, array $b): int {
            if ($a['cumulative_score'] === $b['cumulative_score']) {
                if ($a['profit'] === $b['profit']) {
                    return $b['market_index'] <=> $a['market_index'];
                }
                return $b['profit'] <=> $a['profit'];
            }
            return $b['cumulative_score'] <=> $a['cumulative_score'];
        });

        $rank = 1;
        foreach ($roundRows as &$row) {
            $row['rank_position'] = $rank;
            $rank++;
        }
        unset($row);

        usort($roundRows, static function (array $a, array $b): int {
            if ($a['round_id'] === $b['round_id']) {
                return $a['organization_id'] <=> $b['organization_id'];
            }
            return $a['round_id'] <=> $b['round_id'];
        });

        foreach ($roundRows as $row) {
            $outputRows[] = $row;
        }
    }

    return $outputRows;
}
