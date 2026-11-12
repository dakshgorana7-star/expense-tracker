<?php if (empty($auth)): ?></main>
<footer class="footer">Expense Tracker with Spending Insights &middot; Mini Project</footer>
<?php endif; ?>
<div id="toast" class="toast" role="status" aria-live="polite"></div>
<?php if (empty($auth)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="assets/js/app.js"></script>
</body>
</html>
