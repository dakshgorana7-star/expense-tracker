<?php
// Analytics: monthly totals, next-month prediction and readable insights.

function money(float $n): string { return '₹' . number_format($n, 0); }

/** [ 'YYYY-MM' => total ] for every month that has expenses. */
function monthly_totals(int $uid): array {
    $st = db()->prepare("SELECT DATE_FORMAT(expense_date,'%Y-%m') m, SUM(amount) t FROM expenses WHERE user_id=? GROUP BY m ORDER BY m");
    $st->execute([$uid]);
    $out = [];
    foreach ($st as $r) $out[$r['m']] = (float)$r['t'];
    return $out;
}

/**
 * Forecast next month using COMPLETED months only (the running month is partial).
 *  1) moving average of the last 3 months
 *  2) least-squares line  y = m*x + b  (x = month number 1..n)
 */
function forecast(int $uid): array {
    $cur  = date('Y-m');
    $done = array_filter(monthly_totals($uid), fn($k) => $k < $cur, ARRAY_FILTER_USE_KEY);
    $res  = ['months' => [], 'totals' => [], 'n' => 0, 'moving_avg' => null, 'ma_months' => 0, 'regression' => null,
             'slope' => null, 'intercept' => null, 'target_label' => null, 'message' => ''];
    if (!$done) { $res['message'] = 'Add expenses from previous months to see a forecast.'; return $res; }

    // fill months with no spending with 0 so x values stay consecutive
    $keys = array_keys($done);
    $series = [];
    for ($d = new DateTime($keys[0] . '-01'), $end = new DateTime(end($keys) . '-01'); $d <= $end; $d->modify('+1 month')) {
        $k = $d->format('Y-m'); $series[$k] = $done[$k] ?? 0.0;
    }
    $series = array_slice($series, -6, null, true);
    $y = array_values($series); $n = count($y);
    $res['months'] = array_keys($series); $res['totals'] = $y; $res['n'] = $n;
    $res['target_label'] = date('F Y', strtotime(end($res['months']) . '-01 +1 month'));

    $last = array_slice($y, -3);
    $res['ma_months']   = count($last);
    $res['moving_avg']  = round(array_sum($last) / count($last), 2);

    if ($n >= 2) {
        $sx = $n * ($n + 1) / 2; $sxx = $n * ($n + 1) * (2 * $n + 1) / 6;
        $sy = array_sum($y); $sxy = 0;
        foreach ($y as $i => $v) $sxy += ($i + 1) * $v;
        $m = ($n * $sxy - $sx * $sy) / ($n * $sxx - $sx * $sx);
        $b = ($sy - $m * $sx) / $n;
        $res['slope'] = round($m, 2); $res['intercept'] = round($b, 2);
        $res['regression'] = max(0, round($m * ($n + 1) + $b, 2));
    }
    if ($n < 3) $res['message'] = "Only $n completed month" . ($n > 1 ? 's' : '') . ' of data, so the forecast is a rough estimate.';
    return $res;
}

/** Short readable insight sentences. */
function insights(int $uid): array {
    $db = db(); $cur = date('Y-m'); $out = [];
    $st = $db->prepare("SELECT c.name, c.budget_limit, DATE_FORMAT(e.expense_date,'%Y-%m') m, SUM(e.amount) t
                        FROM expenses e JOIN categories c ON c.category_id=e.category_id
                        WHERE e.user_id=? GROUP BY c.category_id, c.name, c.budget_limit, m");
    $st->execute([$uid]);
    $data = []; $budget = [];
    foreach ($st as $r) { $data[$r['name']][$r['m']] = (float)$r['t']; $budget[$r['name']] = $r['budget_limit']; }
    if (!$data) return [['type' => 'info', 'text' => 'No data yet. Add a few expenses to unlock insights.']];

    // top category (this month, else the latest month with data)
    $months = array_unique(array_merge(...array_values(array_map('array_keys', $data)))); sort($months);
    $done = array_values(array_filter($months, fn($m) => $m < $cur));
    $useMonth = end($months);
    foreach ($data as $mm) if (isset($mm[$cur])) { $useMonth = $cur; break; }
    $top = null; $topV = 0;
    foreach ($data as $c => $mm) if (($mm[$useMonth] ?? 0) > $topV) { $top = $c; $topV = $mm[$useMonth]; }
    if ($top) $out[] = ['type' => 'info', 'text' => "$top is your top spending category " . ($useMonth === $cur ? 'this month' : 'in ' . date('F', strtotime("$useMonth-01"))) . ' (' . money($topV) . ').'];

    // budget alerts for the current month
    foreach ($data as $c => $mm) {
        $b = (float)($budget[$c] ?? 0); $s = $mm[$cur] ?? 0;
        if ($b > 0 && $s > $b)            $out[] = ['type' => 'bad',  'text' => "$c is over budget: " . money($s) . ' spent of ' . money($b) . ' (' . round($s / $b * 100) . '%).'];
        elseif ($b > 0 && $s >= 0.8 * $b) $out[] = ['type' => 'warn', 'text' => "$c is close to its budget: " . money($s) . ' of ' . money($b) . ' (' . round($s / $b * 100) . '%).'];
    }

    // month-over-month change between the last two completed months
    if (count($done) >= 2) {
        [$p, $l] = array_slice($done, -2); $chg = [];
        foreach ($data as $c => $mm) {
            $a = $mm[$p] ?? 0; $z = $mm[$l] ?? 0;
            if ($a > 0) $chg[$c] = [($z - $a) / $a * 100, $a, $z];
        }
        uasort($chg, fn($x, $y) => abs($y[0]) <=> abs($x[0]));
        foreach (array_slice($chg, 0, 4, true) as $c => [$pct, $a, $z]) {
            if (abs($pct) < 1) continue;
            $out[] = ['type' => $pct > 0 ? 'warn' : 'good',
                      'text' => "$c " . ($pct > 0 ? 'rose ' : 'fell ') . round(abs($pct)) . '% in ' . date('F', strtotime("$l-01")) . ' vs ' . date('F', strtotime("$p-01")) . ' (' . money($a) . ' to ' . money($z) . ').'];
        }
    }
    return $out;
}
