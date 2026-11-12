<?php require 'config.php'; require_login();
$title = 'Prediction & Insights'; $page = 'insights'; include 'includes/header.php'; ?>
<h1 class="page-title">Prediction &amp; Insights</h1>
<p class="muted" id="fc-note">Forecast based on your completed months.</p>
<section class="stats two">
  <div class="card stat"><span class="label">Method 1 &middot; Moving average</span><strong id="fc-ma">&ndash;</strong><small id="fc-ma-sub">Average of the last 3 months</small></div>
  <div class="card stat"><span class="label">Method 2 &middot; Linear regression</span><strong id="fc-lr">&ndash;</strong><small id="fc-lr-sub">y = m&middot;x + b</small></div>
</section>
<section class="card"><h2>Monthly history and forecast</h2><div class="chart-box tall"><canvas id="c-fc"></canvas></div></section>
<section class="card"><h2>Insights</h2><ul class="insights" id="insights"></ul></section>
<?php include 'includes/footer.php'; ?>
