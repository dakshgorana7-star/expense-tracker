<?php require 'config.php'; require_login();
$title = 'Expenses'; $page = 'expenses'; include 'includes/header.php'; ?>
<h1 class="page-title">Expenses</h1>
<section class="card">
  <h2>Add expense</h2>
  <form id="exp-form" class="form-row" novalidate>
    <label>Amount (₹)<input type="number" name="amount" step="0.01" min="0.01" required></label>
    <label>Category<select name="category_id" required></select></label>
    <label>Date<input type="date" name="expense_date" required></label>
    <label class="grow">Note<input type="text" name="note" maxlength="255" placeholder="Optional"></label>
    <button class="btn btn-primary" type="submit">Add</button>
  </form>
  <div class="form-error" id="exp-error" aria-live="polite"></div>
</section>
<section class="card">
  <div class="card-head"><h2>Your expenses</h2>
    <label class="inline">Month <input type="month" id="f-month"></label></div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Date</th><th>Category</th><th>Note</th><th class="num">Amount</th><th></th></tr></thead>
    <tbody id="exp-body"></tbody>
  </table></div>
  <p class="empty" id="exp-empty" hidden>No expenses found.</p>
</section>

<dialog id="edit-dlg"><form id="edit-form" novalidate>
  <h2>Edit expense</h2>
  <label>Amount (₹)<input type="number" name="amount" step="0.01" min="0.01" required></label>
  <label>Category<select name="category_id" required></select></label>
  <label>Date<input type="date" name="expense_date" required></label>
  <label>Note<input type="text" name="note" maxlength="255"></label>
  <div class="form-error" id="edit-error"></div>
  <div class="dlg-actions"><button type="button" class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary" type="submit">Save</button></div>
</form></dialog>
<?php include 'includes/footer.php'; ?>
