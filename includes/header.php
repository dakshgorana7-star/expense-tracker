<?php /* Shared layout. Set $title, $page (nav highlight) and $auth (true for login/register) before including. */
$auth = $auth ?? false; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= e(csrf()) ?>">
<meta name="description" content="Track expenses, see charts and get a forecast of next month's spending.">
<title><?= e($title) ?> | Expense Tracker</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="<?= e($page ?? '') ?>">
<?php if (!$auth): ?>
<header class="topbar">
  <a class="brand" href="dashboard.php"><span class="logo">₹</span> Expense Tracker</a>
  <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="Toggle menu">
  <label for="nav-toggle" class="burger" aria-hidden="true">&#9776;</label>
  <nav class="nav">
    <?php foreach (['dashboard' => 'Dashboard', 'expenses' => 'Expenses', 'categories' => 'Categories', 'insights' => 'Insights'] as $k => $l): ?>
      <a href="<?= $k ?>.php" class="<?= ($page ?? '') === $k ? 'active' : '' ?>"><?= $l ?></a>
    <?php endforeach; ?>
    <form method="post" action="logout.php" class="logout">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <span class="who"><?= e($_SESSION['user_name'] ?? '') ?></span>
      <button class="btn btn-ghost" type="submit">Log out</button>
    </form>
  </nav>
</header>
<main class="container">
<?php endif; ?>
