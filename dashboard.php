<?php require 'config.php'; require_login();
$title = 'Dashboard'; $page = 'dashboard'; include 'includes/header.php'; ?>
<h1 class="page-title">Dashboard</h1>
<p class="muted" id="sub">Your spending at a glance.</p>

<section class="stats">
  <div class="card stat"><span class="label">Spent this month</span><strong id="st-total">&ndash;</strong><small id="st-month"></small></div>
  <div class="card stat"><span class="label">Top category</span><strong id="st-top">&ndash;</strong><small>this month</small></div>
  <div class="card stat"><span class="label">Forecast (3-mo average)</span><strong id="st-fc">&ndash;</strong><small id="st-fc-sub"></small></div>
</section>

<section class="grid-2">
  <div class="card"><h2>Spending by category</h2><div class="chart-box"><canvas id="c-pie"></canvas></div><p class="empty" id="e-pie" hidden>No expenses this month yet.</p></div>
  <div class="card"><h2>Monthly totals</h2><div class="chart-box"><canvas id="c-line"></canvas></div></div>
</section>
<section class="grid-2">
  <div class="card"><h2>Budget vs actual (this month)</h2><div class="chart-box"><canvas id="c-bar"></canvas></div><p class="empty" id="e-bar" hidden>Set budgets in Categories to compare.</p></div>
  <div class="card"><h2>Insights</h2><ul class="insights" id="insights"></ul><a class="btn btn-ghost" href="insights.php">Full forecast &rarr;</a></div>
</section>
<?php include 'includes/footer.php'; ?>
