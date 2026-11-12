<?php
// Dashboard data: this month's totals, category split, 6-month trend, budget vs actual.
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/analytics.php';
$uid = api_user(); $db = db(); $cur = date('Y-m');
try {
    $st = $db->prepare("SELECT c.name, c.budget_limit, COALESCE(SUM(e.amount),0) spent
                        FROM categories c LEFT JOIN expenses e ON e.category_id=c.category_id AND DATE_FORMAT(e.expense_date,'%Y-%m')=?
                        WHERE c.user_id=? GROUP BY c.category_id, c.name, c.budget_limit ORDER BY spent DESC");
    $st->execute([$cur, $uid]); $cats = $st->fetchAll();

    $totals = monthly_totals($uid); $labels = []; $values = [];
    for ($i = 5; $i >= 0; $i--) { $k = date('Y-m', strtotime("first day of -$i month")); $labels[] = date('M Y', strtotime("$k-01")); $values[] = $totals[$k] ?? 0; }

    $split = array_values(array_filter($cats, fn($c) => $c['spent'] > 0));
    json_out([
        'month_label'  => date('F Y'),
        'month_total'  => (float)($totals[$cur] ?? 0),
        'top_category' => $split ? $split[0]['name'] : null,
        'categories'   => array_map(fn($c) => ['name' => $c['name'], 'total' => (float)$c['spent']], $split),
        'trend'        => ['labels' => $labels, 'values' => $values],
        'budget'       => array_values(array_map(fn($c) => ['name' => $c['name'], 'budget' => (float)$c['budget_limit'], 'spent' => (float)$c['spent']],
                           array_filter($cats, fn($c) => (float)$c['budget_limit'] > 0))),
    ]);
} catch (Throwable $ex) { json_out(['error' => 'Could not load summary.'], 500); }
