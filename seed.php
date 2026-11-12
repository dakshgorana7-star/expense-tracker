<?php
// Demo data: creates demo@example.com / demo1234 with 6 completed months + this month's expenses.
// Run from the browser (http://localhost/expense-tracker/seed.php) or CLI: php seed.php
require __DIR__ . '/config.php';
$db = db();
$email = 'demo@example.com';

// re-runnable: remove the old demo user's data first (expenses before categories because of the foreign keys)
$old = $db->prepare('SELECT user_id FROM users WHERE email=?'); $old->execute([$email]);
if ($oid = $old->fetchColumn()) {
    foreach (['expenses', 'categories'] as $t) $db->exec("DELETE FROM $t WHERE user_id=" . (int)$oid);
    $db->exec('DELETE FROM users WHERE user_id=' . (int)$oid);
}
$db->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)')->execute(['Demo User', $email, password_hash('demo1234', PASSWORD_BCRYPT)]);
$uid = (int)$db->lastInsertId();
default_categories($uid);

$cat = [];
foreach ($db->query("SELECT category_id,name FROM categories WHERE user_id=$uid") as $r) $cat[$r['name']] = (int)$r['category_id'];

mt_srand(42);                                                                  // same data every run
// category => [min, max, how many expenses per month, notes]
$plan = [
  'Food'          => [120, 650, 14, ['Groceries', 'Lunch', 'Dinner out', 'Snacks', 'Coffee']],
  'Transport'     => [40, 380, 8,  ['Metro card', 'Cab', 'Fuel', 'Auto']],
  'Shopping'      => [300, 1800, 3, ['Clothes', 'Accessories', 'Online order']],
  'Bills'         => [350, 1500, 3, ['Electricity', 'Internet', 'Phone recharge']],
  'Entertainment' => [150, 700, 3, ['Movie', 'Subscription', 'Games']],
  'Other'         => [100, 900, 2, ['Gift', 'Stationery', 'Miscellaneous']],
];
$ins = $db->prepare('INSERT INTO expenses (user_id,category_id,amount,expense_date,note) VALUES (?,?,?,?,?)');
$count = 0;
for ($back = 6; $back >= 0; $back--) {
    $first = new DateTime('first day of -' . $back . ' month');
    $days  = $back === 0 ? (int)date('j') : (int)$first->format('t');          // current month: only up to today
    $growth = 1 + (6 - $back) * 0.04;                                           // gentle upward trend for the regression line
    foreach ($plan as $name => [$lo, $hi, $n, $notes]) {
        $k = $back === 0 ? max(1, (int)round($n * $days / 30)) : $n;
        for ($i = 0; $i < $k; $i++) {
            $d = (clone $first)->modify('+' . mt_rand(0, $days - 1) . ' day')->format('Y-m-d');
            $amt = round(mt_rand($lo * 100, $hi * 100) / 100 * $growth, 2);
            $ins->execute([$uid, $cat[$name], $amt, $d, $notes[array_rand($notes)]]);
            $count++;
        }
    }
}
$msg = "Seeded $count expenses for $email (password: demo1234).";
if (PHP_SAPI === 'cli') echo $msg . PHP_EOL; else echo '<p style="font-family:sans-serif">' . e($msg) . ' <a href="login.php">Go to login</a></p>';
